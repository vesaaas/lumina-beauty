<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            return redirect()
                ->route('home')
                ->with('account_modal', true)
                ->withErrors(['email' => 'Google sign-in expired. Please try again.']);
        }

        $email = $googleUser->getEmail();
        $emailVerified = (bool) ($googleUser->user['email_verified'] ?? false);

        if (! $email || ! $emailVerified) {
            return redirect()
                ->route('home')
                ->with('account_modal', true)
                ->withErrors(['email' => 'Google must confirm your email before sign-in can continue.']);
        }

        $user = User::where('email', $email)->first();

        if ($user?->is_admin) {
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

        return redirect()->intended(route('home'));
    }
}
