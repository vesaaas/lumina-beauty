<?php

namespace App\Services;

use App\Mail\EmailVerificationOtpMail;
use App\Models\EmailVerificationOtp;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationOtpService
{
    public const RESEND_COOLDOWN_SECONDS = 15;

    public function generateAndSend(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        EmailVerificationOtp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
                'last_sent_at' => now(),
            ]
        );

        $user->unsetRelation('emailVerificationOtp');

        Mail::to($user->email)
            ->queue(new EmailVerificationOtpMail($code));
    }

    public function resend(User $user): void
    {
        $seconds = $this->secondsUntilResendAvailable($user);

        if ($seconds > 0) {
            throw ValidationException::withMessages([
                'resend' => "Please wait {$seconds} seconds before requesting another verification code.",
            ]);
        }

        $this->generateAndSend($user);
    }

    public function ensureActive(User $user): void
    {
        $otp = $user->emailVerificationOtp;

        if (! $otp) {
            $this->generateAndSend($user);

            return;
        }

        if ($otp->expires_at->isPast()) {
            $otp->delete();
            $this->generateAndSend($user);
        }
    }

    public function secondsUntilResendAvailable(User $user): int
    {
        $lastSentAt = $user->emailVerificationOtp?->last_sent_at;

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
}
