<?php

namespace App\Services;

use App\Mail\GuestCheckoutOtpMail;
use App\Models\GuestCheckoutOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class GuestCheckoutOtpService
{
    public const RESEND_COOLDOWN_SECONDS = 15;
    public const MAX_ATTEMPTS = 5;

    public function generateAndSend(string $sessionId, string $email): GuestCheckoutOtp
    {
        $code = (string) random_int(100000, 999999);

        $otp = GuestCheckoutOtp::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'email' => $email,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
                'last_sent_at' => now(),
            ]
        );

        Mail::to($email)->queue((new GuestCheckoutOtpMail($code))->afterCommit());

        return $otp;
    }

    public function resend(int $otpId, string $sessionId): void
    {
        $otp = $this->challenge($otpId, $sessionId);

        if (! $otp) {
            throw ValidationException::withMessages([
                'resend' => 'No active checkout verification was found. Please return to checkout.',
            ]);
        }

        $seconds = $this->secondsUntilResendAvailable($otpId, $sessionId);

        if ($seconds > 0) {
            throw ValidationException::withMessages([
                'resend' => "Please wait {$seconds} seconds before requesting another checkout code.",
            ]);
        }

        $this->generateAndSend($otp->session_id, $otp->email);
    }

    public function verify(int $otpId, string $sessionId, string $code): void
    {
        $otp = $this->challenge($otpId, $sessionId);

        if (! $otp) {
            throw ValidationException::withMessages([
                'code' => 'No active checkout verification was found. Please return to checkout.',
            ]);
        }

        if ($otp->expires_at->isPast()) {
            $otp->delete();

            throw ValidationException::withMessages([
                'code' => 'This checkout code has expired. Please return to checkout.',
            ]);
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Too many incorrect attempts. Please request a new checkout code.',
            ]);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'The checkout code is incorrect.',
            ]);
        }

        $otp->delete();
    }

    public function secondsUntilResendAvailable(?int $otpId, string $sessionId): int
    {
        if (! $otpId) {
            return 0;
        }

        $lastSentAt = $this->challenge($otpId, $sessionId)?->last_sent_at;

        if (! $lastSentAt) {
            return 0;
        }

        $availableAt = $lastSentAt
            ->copy()
            ->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        if ($availableAt->isPast()) {
            return 0;
        }

        return (int) ceil(now()->diffInMilliseconds($availableAt) / 1000);
    }

    public function deleteChallenge(string $sessionId): void
    {
        GuestCheckoutOtp::where('session_id', $sessionId)->delete();
    }

    private function challenge(int $otpId, string $sessionId): ?GuestCheckoutOtp
    {
        return GuestCheckoutOtp::whereKey($otpId)
            ->where('session_id', $sessionId)
            ->first();
    }
}
