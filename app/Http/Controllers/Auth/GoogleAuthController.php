<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\GuestCommerceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()
                ->route('home')
                ->with('account_modal', true)
                ->withErrors(['email' => 'Google login is not configured yet.']);
        }

        return Socialite::driver('google')
            ->redirect();
    }

    public function callback(
        Request $request,
        GuestCommerceService $guestCommerce
    ): RedirectResponse {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            AuditLogService::log(
                $request,
                'oauth.google_failed',
                null,
                [],
                ['reason' => 'invalid_state'],
            );

            return redirect()
                ->route('home')
                ->with('account_modal', true)
                ->withErrors(['email' => 'Google sign-in expired. Please try again.']);
        }

        $email = $googleUser->getEmail();
        $emailVerified = (bool) ($googleUser->user['email_verified'] ?? false);

        if (! $email || ! $emailVerified) {
            AuditLogService::log(
                $request,
                'oauth.google_failed',
                null,
                [],
                ['reason' => $email ? 'unverified_email' : 'missing_email'],
            );

            return redirect()
                ->route('home')
                ->with('account_modal', true)
                ->withErrors(['email' => 'Google must confirm your email before sign-in can continue.']);
        }

        $user = User::where('email', $email)->first();

        if ($user?->is_admin) {
            AuditLogService::log(
                $request,
                'oauth.google_admin_blocked',
                $user,
                [],
                ['email_hash' => hash('sha256', strtolower($email))],
            );

            return redirect()
                ->route('home')
                ->with('account_modal', true)
                ->withErrors(['email' => 'Admin accounts must use the dedicated admin login.']);
        }

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'password' => Str::password(32),
                'is_admin' => false,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        } elseif (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->emailVerificationOtp()->delete();
        }

        Auth::login($user);
        $guestCommerce->attachToUser($request, $user);
        $request->session()->regenerate();

        AuditLogService::log(
            $request,
            'customer.oauth_google_login',
            $user,
            [],
            ['email_hash' => hash('sha256', strtolower($user->email))],
        );

        return redirect()->to($this->safeIntendedRedirect($request));
    }

    private function isConfigured(): bool
    {
        $config = config('services.google');

        return filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['redirect'] ?? null);
    }

    private function safeIntendedRedirect(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || trim($intended) === '') {
            return route('home');
        }

        $intended = trim($intended);

        if (preg_match('/[\x00-\x1F\x7F]/', $intended) || str_starts_with($intended, '//')) {
            return route('home');
        }

        $parts = parse_url($intended);

        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return route('home');
        }

        if (isset($parts['scheme']) || isset($parts['host'])) {
            $scheme = strtolower((string) ($parts['scheme'] ?? ''));

            if (! in_array($scheme, ['http', 'https'], true)
                || $scheme !== $request->getScheme()
                || strtolower((string) ($parts['host'] ?? '')) !== strtolower($request->getHost())
                || (isset($parts['port']) && (int) $parts['port'] !== $request->getPort())
            ) {
                return route('home');
            }
        }

        $path = $parts['path'] ?? '/';

        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return route('home');
        }

        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            return route('home');
        }

        return url($path
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : ''));
    }
}
