<?php

namespace App\Services;

use App\Mail\LoginTwoFactorCodeMail;
use App\Models\LoginTwoFactorChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class LoginTwoFactorService
{
    public const RESEND_COOLDOWN_SECONDS = 15;
    public const MAX_ATTEMPTS = 5;

    public function generateAndSend(User $user, string $context): void
    {
        $code = (string) random_int(100000, 999999);

        LoginTwoFactorChallenge::updateOrCreate(
            [
                'user_id' => $user->id,
                'context' => $context,
            ],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
                'last_sent_at' => now(),
            ]
        );

        $user->unsetRelation('loginTwoFactorChallenge');

        Mail::to($user->email)->queue(
            new LoginTwoFactorCodeMail($code, $context)
        );
    }

    public function resend(User $user, string $context): void
    {
        $seconds = $this->secondsUntilResendAvailable($user, $context);

        if ($seconds > 0) {
            throw ValidationException::withMessages([
                'resend' => "Please wait {$seconds} seconds before requesting another security code.",
            ]);
        }

        $this->generateAndSend($user, $context);
    }

    public function verify(User $user, string $context, string $code): void
    {
        $challenge = $this->challenge($user, $context);

        if (! $challenge) {
            throw ValidationException::withMessages([
                'code' => 'No active security challenge was found. Please sign in again.',
            ]);
        }

        if ($challenge->expires_at->isPast()) {
            $challenge->delete();

            throw ValidationException::withMessages([
                'code' => 'This security code has expired. Please sign in again.',
            ]);
        }

        if ($challenge->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Too many incorrect attempts. Please request a new security code.',
            ]);
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            $challenge->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'The security code is incorrect.',
            ]);
        }

        $challenge->delete();
    }

    public function secondsUntilResendAvailable(User $user, string $context): int
    {
        $lastSentAt = $this->challenge($user, $context)?->last_sent_at;

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

    public function deleteChallenge(User $user, string $context): void
    {
        LoginTwoFactorChallenge::where('user_id', $user->id)
            ->where('context', $context)
            ->delete();
    }

    private function challenge(
        User $user,
        string $context
    ): ?LoginTwoFactorChallenge {
        return LoginTwoFactorChallenge::where('user_id', $user->id)
            ->where('context', $context)
            ->first();
    }
}
