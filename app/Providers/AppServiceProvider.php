<?php

namespace App\Providers;

use App\Models\Order;
use App\Policies\OrderPolicy;
use App\Services\StorefrontViewData;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);

        $this->configureRateLimiters();

        View::composer('layouts.app', function ($view): void {
            $data = $view->getData();

            $missing = array_diff_key(
                app(StorefrontViewData::class)->layoutData(request()),
                $data
            );

            if ($missing !== []) {
                $view->with($missing);
            }
        });
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('customer-login', fn (Request $request) => Limit::perMinute(5)
            ->by($this->emailIpKey('customer-login', $request)));
        RateLimiter::for('customer-login-2fa-show', fn (Request $request) => Limit::perMinute(10)
            ->by($this->twoFactorKey('customer-login-2fa-show', $request)));
        RateLimiter::for('customer-login-2fa', fn (Request $request) => Limit::perMinute(5)
            ->by($this->twoFactorKey('customer-login-2fa', $request)));
        RateLimiter::for('customer-login-2fa-resend', fn (Request $request) => Limit::perMinute(3)
            ->by($this->twoFactorKey('customer-login-2fa-resend', $request)));
        RateLimiter::for('customer-login-2fa-cancel', fn (Request $request) => Limit::perMinute(10)
            ->by($this->twoFactorKey('customer-login-2fa-cancel', $request)));

        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)
            ->by($this->emailIpKey('admin-login', $request)));
        RateLimiter::for('admin-login-2fa-show', fn (Request $request) => Limit::perMinute(10)
            ->by($this->twoFactorKey('admin-login-2fa-show', $request)));
        RateLimiter::for('admin-login-2fa', fn (Request $request) => Limit::perMinute(5)
            ->by($this->twoFactorKey('admin-login-2fa', $request)));
        RateLimiter::for('admin-login-2fa-resend', fn (Request $request) => Limit::perMinute(3)
            ->by($this->twoFactorKey('admin-login-2fa-resend', $request)));
        RateLimiter::for('admin-login-2fa-cancel', fn (Request $request) => Limit::perMinute(10)
            ->by($this->twoFactorKey('admin-login-2fa-cancel', $request)));

        RateLimiter::for('registration', fn (Request $request) => Limit::perMinutes(10, 3)
            ->by($this->emailIpKey('registration', $request)));
        RateLimiter::for('registration-otp-verify', fn (Request $request) => Limit::perMinute(5)
            ->by($this->userSessionIpKey('registration-otp-verify', $request)));
        RateLimiter::for('registration-otp-resend', fn (Request $request) => Limit::perMinute(3)
            ->by($this->userSessionIpKey('registration-otp-resend', $request)));

        RateLimiter::for('guest-checkout-otp-show', fn (Request $request) => Limit::perMinute(10)
            ->by($this->guestCheckoutKey('guest-checkout-otp-show', $request)));
        RateLimiter::for('guest-checkout-otp-verify', fn (Request $request) => Limit::perMinute(5)
            ->by($this->guestCheckoutKey('guest-checkout-otp-verify', $request)));
        RateLimiter::for('guest-checkout-otp-resend', fn (Request $request) => Limit::perMinute(3)
            ->by($this->guestCheckoutKey('guest-checkout-otp-resend', $request)));

        RateLimiter::for('forgot-password', fn (Request $request) => Limit::perMinutes(10, 3)
            ->by($this->emailIpKey('forgot-password', $request)));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinutes(10, 3)
            ->by($this->emailIpKey('password-reset', $request)));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinutes(10, 3)
            ->by($this->emailIpKey('contact', $request)));
        RateLimiter::for('about', fn (Request $request) => Limit::perMinutes(10, 3)
            ->by($this->emailIpKey('about', $request)));
    }

    private function emailIpKey(string $flow, Request $request): string
    {
        $email = strtolower((string) $request->input('email', ''));
        $emailHash = $email !== '' ? hash('sha256', $email) : 'no-email';

        return implode('|', [$flow, $emailHash, $request->ip()]);
    }

    private function twoFactorKey(string $flow, Request $request): string
    {
        $pending = $request->session()->get('login_2fa');
        $userId = is_array($pending) && isset($pending['user_id'])
            ? (string) $pending['user_id']
            : $request->session()->getId();
        $context = is_array($pending) && isset($pending['context'])
            ? (string) $pending['context']
            : 'no-context';

        return implode('|', [
            $flow,
            $context,
            $userId,
            $request->ip(),
        ]);
    }

    private function userSessionIpKey(string $flow, Request $request): string
    {
        return implode('|', [
            $flow,
            $request->user()?->getAuthIdentifier() ?? 'guest',
            $request->ip(),
        ]);
    }

    private function guestCheckoutKey(string $flow, Request $request): string
    {
        $pending = $request->session()->get('guest_checkout.pending');
        $otpId = is_array($pending) && isset($pending['otp_id'])
            ? (string) $pending['otp_id']
            : $request->session()->getId();

        return implode('|', [
            $flow,
            $otpId,
            $request->ip(),
        ]);
    }
}
