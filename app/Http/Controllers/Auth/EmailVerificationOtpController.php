<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmailVerificationOtpController extends Controller
{
    public function show(
        Request $request,
        EmailVerificationOtpService $otpService
    ): View|RedirectResponse {
        $user = $request->user();

        if ($user->email_verified_at) {
            return redirect()->route('home');
        }

        return view('auth.verify-email-otp', [
            'email' => $user->email,
            'resendCooldownSeconds' => $otpService->secondsUntilResendAvailable($user),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if ($user->email_verified_at) {
            return redirect()->route('home');
        }

        $otp = $user->emailVerificationOtp;

        if (! $otp) {
            return back()->withErrors([
                'code' => 'No active verification code was found. Please request a new code.',
            ]);
        }

        if ($otp->expires_at->isPast()) {
            $otp->delete();

            return back()->withErrors([
                'code' => 'This verification code has expired. Please request a new code.',
            ]);
        }

        if ($otp->attempts >= 5) {
            return back()->withErrors([
                'code' => 'Too many incorrect attempts. Please request a new verification code.',
            ]);
        }

        if (! Hash::check($attributes['code'], $otp->code_hash)) {
            $otp->increment('attempts');

            return back()->withErrors([
                'code' => 'The verification code is incorrect.',
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $otp->delete();

        return redirect()
            ->route('home')
            ->with('status', 'Your email has been verified successfully.');
    }

    public function resend(
        Request $request,
        EmailVerificationOtpService $otpService
    ): RedirectResponse {
        $user = $request->user();

        if ($user->email_verified_at) {
            return redirect()->route('home');
        }

        $otpService->resend($user);

        return back()->with(
            'status',
            'A new verification code was sent. Your previous code is no longer valid.'
        );
    }
}
