<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginTwoFactorChallenge;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\GuestCommerceService;
use App\Services\LoginTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginTwoFactorController extends Controller
{
    public function show(
        Request $request,
        LoginTwoFactorService $twoFactorService
    ): View|RedirectResponse {
        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route($this->fallbackRoute($request));
        }

        $user = $this->pendingUser($pending);

        if (! $user) {
            $request->session()->forget('login_2fa');

            return redirect()->route($this->fallbackRoute($request));
        }

        return view('auth.login-two-factor', [
            'context' => $pending['context'],
            'email' => $this->maskEmail($user->email),
            'resendCooldownSeconds' => $twoFactorService
                ->secondsUntilResendAvailable($user, $pending['context']),
        ]);
    }

    public function verify(
        Request $request,
        LoginTwoFactorService $twoFactorService,
        GuestCommerceService $guestCommerce
    ): RedirectResponse {
        $attributes = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route($this->fallbackRoute($request));
        }

        $user = $this->pendingUser($pending);

        if (! $user) {
            $request->session()->forget('login_2fa');

            return redirect()->route($this->fallbackRoute($request));
        }

        try {
            $twoFactorService->verify($user, $pending['context'], $attributes['code']);
        } catch (ValidationException $exception) {
            AuditLogService::log(
                $request,
                $pending['context'] === LoginTwoFactorChallenge::CONTEXT_ADMIN
                    ? 'admin.login_2fa_failed'
                    : 'customer.login_2fa_failed',
                $user
            );

            throw $exception;
        }

        Auth::login($user, (bool) ($pending['remember'] ?? false));

        if ($pending['context'] === LoginTwoFactorChallenge::CONTEXT_CUSTOMER) {
            $guestCommerce->attachToUser($request, $user);
        }

        $request->session()->forget('login_2fa');
        $request->session()->regenerate();

        if ($pending['context'] === LoginTwoFactorChallenge::CONTEXT_ADMIN) {
            AuditLogService::log($request, 'admin.login_2fa_success');
            AuditLogService::log($request, 'admin.login');
        } else {
            AuditLogService::log($request, 'customer.login_2fa_success');
            AuditLogService::log($request, 'customer.login');
        }

        return redirect()->to($this->safeIntendedRedirect($request, $pending));
    }

    public function resend(
        Request $request,
        LoginTwoFactorService $twoFactorService
    ): RedirectResponse {
        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route($this->fallbackRoute($request));
        }

        $user = $this->pendingUser($pending);

        if (! $user) {
            $request->session()->forget('login_2fa');

            return redirect()->route($this->fallbackRoute($request));
        }

        $twoFactorService->resend($user, $pending['context']);

        AuditLogService::log(
            $request,
            $pending['context'] === LoginTwoFactorChallenge::CONTEXT_ADMIN
                ? 'admin.login_2fa_resent'
                : 'customer.login_2fa_resent',
            $user
        );

        return back()->with(
            'status',
            'A new security code was sent. Your previous code is no longer valid.'
        );
    }

    public function cancel(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);

        if ($pending) {
            $user = User::find($pending['user_id']);

            if ($user) {
                app(LoginTwoFactorService::class)
                    ->deleteChallenge($user, $pending['context']);
            }
        }

        $request->session()->forget('login_2fa');

        return redirect()->route($this->fallbackRoute($request));
    }

    /**
     * @return array{user_id: int, context: string, remember?: bool, intended?: string}|null
     */
    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get('login_2fa');

        if (! is_array($pending) || ! isset($pending['user_id'], $pending['context'])) {
            return null;
        }

        if (! in_array($pending['context'], [
            LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
            LoginTwoFactorChallenge::CONTEXT_ADMIN,
        ], true)) {
            return null;
        }

        $expected = $request->is('admin/*')
            ? LoginTwoFactorChallenge::CONTEXT_ADMIN
            : LoginTwoFactorChallenge::CONTEXT_CUSTOMER;

        if ($pending['context'] !== $expected) {
            return null;
        }

        return $pending;
    }

    private function pendingUser(array $pending): ?User
    {
        $user = User::find($pending['user_id']);

        if (! $user) {
            return null;
        }

        if ($pending['context'] === LoginTwoFactorChallenge::CONTEXT_ADMIN) {
            return $user->is_admin ? $user : null;
        }

        if ($user->is_admin || ! $user->email_verified_at) {
            return null;
        }

        return $user;
    }

    private function fallbackRoute(Request $request): string
    {
        return $request->is('admin/*')
            ? 'admin.login'
            : 'login';
    }

    /**
     * @param  array{user_id: int, context: string, remember?: bool, intended?: string}  $pending
     */
    private function safeIntendedRedirect(Request $request, array $pending): string
    {
        $fallback = $pending['context'] === LoginTwoFactorChallenge::CONTEXT_ADMIN
            ? route('admin.dashboard')
            : route('home');

        $path = $this->localPathFromIntended($request, $pending['intended'] ?? null);

        if (! $path) {
            return $fallback;
        }

        if ($pending['context'] === LoginTwoFactorChallenge::CONTEXT_ADMIN) {
            if (! $this->isAllowedAdminPath($path)) {
                return $fallback;
            }
        } elseif ($this->isAdminPath($path)) {
            return $fallback;
        }

        return url($path);
    }

    private function localPathFromIntended(Request $request, mixed $intended): ?string
    {
        if (! is_string($intended) || trim($intended) === '') {
            return null;
        }

        $intended = trim($intended);

        if (preg_match('/[\x00-\x1F\x7F]/', $intended) || str_starts_with($intended, '//')) {
            return null;
        }

        $parts = parse_url($intended);

        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        if (isset($parts['scheme']) || isset($parts['host'])) {
            $scheme = strtolower((string) ($parts['scheme'] ?? ''));

            if (! in_array($scheme, ['http', 'https'], true)) {
                return null;
            }

            if ($scheme !== $request->getScheme()) {
                return null;
            }

            if (strtolower((string) ($parts['host'] ?? '')) !== strtolower($request->getHost())) {
                return null;
            }

            if (isset($parts['port']) && (int) $parts['port'] !== $request->getPort()) {
                return null;
            }
        }

        $path = $parts['path'] ?? '/';

        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        return $path
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    private function isAllowedAdminPath(string $path): bool
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '';

        return $this->isAdminPath($path)
            && $path !== '/admin/login'
            && ! str_starts_with($path, '/admin/login/');
    }

    private function isAdminPath(string $path): bool
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '';

        return $path === '/admin' || str_starts_with($path, '/admin/');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, 2);

        return $visible.str_repeat('*', max(3, mb_strlen($local) - 2)).'@'.$domain;
    }
}
