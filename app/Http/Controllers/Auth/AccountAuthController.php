<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginTwoFactorChallenge;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\EmailVerificationOtpService;
use App\Services\GuestCommerceService;
use App\Services\LoginTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountAuthController extends Controller
{
    public function register(
        Request $request,
        EmailVerificationOtpService $otpService,
        GuestCommerceService $guestCommerce
    ): RedirectResponse {
        $attributes = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => [
                'required',
                'string',
                'max:30',
                'regex:/^[0-9+\s().-]{7,30}$/',
            ],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $user = User::create([
            'name' => trim($attributes['first_name'].' '.$attributes['last_name']),
            'email' => $attributes['email'],
            'phone' => $attributes['phone'],
            'password' => $attributes['password'],
            'is_admin' => false,
        ]);

        Auth::login($user);

        $guestCommerce->attachToUser($request, $user);

        $request->session()->regenerate();

        $otpService->generateAndSend($user);

        AuditLogService::log(
            $request,
            'registration.completed',
            $user,
            [],
            ['email_hash' => hash('sha256', strtolower($user->email))],
        );

        return redirect()
            ->route('verification.otp.show')
            ->with('status', 'We sent a 6-digit verification code to your email.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $attributes['email'])->first();

        if ($user?->is_admin) {
            throw ValidationException::withMessages([
                'email' => 'Password reset is available for customer accounts only.',
            ]);
        }

        $status = Password::sendResetLink($attributes);

        if ($status === Password::RESET_LINK_SENT && $user) {
            AuditLogService::log(
                $request,
                'password_reset.requested',
                $user,
            );
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)])
                ->onlyInput('email');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $user = User::where('email', $attributes['email'])->first();

        if ($user?->is_admin) {
            throw ValidationException::withMessages([
                'email' => 'Password reset is available for customer accounts only.',
            ]);
        }

        $status = Password::reset(
            $attributes,
            function (User $user, string $password) use ($request): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                AuditLogService::log(
                    $request,
                    'password_reset.completed',
                    $user,
                );
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()
                ->route('home')
                ->with('account_modal', true)
                ->with('status', __($status))
            : back()
                ->withErrors(['email' => __($status)])
                ->onlyInput('email');
    }

    public function login(
        Request $request,
        EmailVerificationOtpService $emailVerificationOtpService,
        LoginTwoFactorService $twoFactorService
    ): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            AuditLogService::log(
                $request,
                'customer.login_failed',
                null,
                [],
                ['email_hash' => hash('sha256', strtolower($credentials['email']))],
            );

            throw ValidationException::withMessages([
                'email' => 'The email or password is incorrect.',
            ])->redirectTo(url()->previous().'#account');
        }

        if ($user->is_admin) {
            AuditLogService::log(
                $request,
                'customer.login_admin_blocked',
                $user,
            );

            throw ValidationException::withMessages([
                'email' => 'Please use the admin login page for admin access.',
            ])->redirectTo(url()->previous().'#account');
        }

        if (! $user->email_verified_at) {
            Auth::login($user);
            $request->session()->regenerate();
            $emailVerificationOtpService->ensureActive($user);

            return redirect()
                ->route('verification.otp.show')
            ->with('status', 'Please verify your email before signing in fully.');
        }

        $request->session()->regenerate();

        $request->session()->put('login_2fa', [
            'user_id' => $user->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_CUSTOMER,
            'remember' => $request->boolean('remember'),
            'intended' => redirect()->intended(route('home'))->getTargetUrl(),
        ]);

        $twoFactorService->generateAndSend(
            $user,
            LoginTwoFactorChallenge::CONTEXT_CUSTOMER
        );

        AuditLogService::log(
            $request,
            'customer.login_2fa_challenge_initiated',
            $user,
        );

        return redirect()
            ->route('login.2fa.show')
            ->with('status', 'We sent a 6-digit security code to your email.');
    }

    public function showAdminLogin(): View|RedirectResponse
    {
        if (Auth::user()?->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function adminLogin(
        Request $request,
        LoginTwoFactorService $twoFactorService
    ): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = Auth::getProvider()->retrieveByCredentials($credentials);

        if (! ($user instanceof User)
            || ! Auth::getProvider()->validateCredentials($user, $credentials)
            || ! $user->is_admin
        ) {
            AuditLogService::log(
                $request,
                'admin.login_failed',
                null,
                [],
                ['email' => $credentials['email']],
            );

            throw ValidationException::withMessages([
                'email' => 'The provided admin credentials are invalid.',
            ])->redirectTo(route('admin.login'));
        }

        $request->session()->regenerate();

        $request->session()->put('login_2fa', [
            'user_id' => $user->id,
            'context' => LoginTwoFactorChallenge::CONTEXT_ADMIN,
            'remember' => $request->boolean('remember'),
            'intended' => route('admin.dashboard'),
        ]);

        $twoFactorService->generateAndSend(
            $user,
            LoginTwoFactorChallenge::CONTEXT_ADMIN
        );

        AuditLogService::log(
            $request,
            'admin.login_2fa_challenge_initiated',
            $user,
            [],
            ['email' => $user->email]
        );

        return redirect()
            ->route('admin.login.2fa.show')
            ->with('status', 'We sent a 6-digit admin security code to your email.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            AuditLogService::log(
                $request,
                $user->is_admin ? 'admin.logout' : 'customer.logout',
                $user,
            );
        }

        Auth::logout();

        $request->session()->forget([
            'login_2fa',
            'guest_checkout.pending',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

}
