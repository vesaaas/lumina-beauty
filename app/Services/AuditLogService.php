<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'reset_token',
        'code',
        'otp',
        'otp_code',
        'code_hash',
        'client_secret',
        'oauth_secret',
        'two_factor_secret',
        '2fa_secret',
    ];

    public static function log(
        Request $request,
        string $action,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => self::sanitize($oldValues) ?: null,
            'new_values' => self::sanitize($newValues) ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    public static function changedValues(array $before, array $after): array
    {
        $before = self::sanitize($before);
        $after = self::sanitize($after);
        $oldValues = [];
        $newValues = [];

        foreach ($after as $key => $value) {
            if (in_array($key, ['created_at', 'updated_at'], true)) {
                continue;
            }

            if (($before[$key] ?? null) === $value) {
                continue;
            }

            $oldValues[$key] = $before[$key] ?? null;
            $newValues[$key] = $value;
        }

        return [$oldValues, $newValues];
    }

    private static function sanitize(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            if (self::isSensitiveKey((string) $key)) {
                continue;
            }

            $sanitized[$key] = is_array($value) ? self::sanitize($value) : $value;
        }

        return $sanitized;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($normalized === $sensitiveKey || str_contains($normalized, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
