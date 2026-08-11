<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginTwoFactorChallenge extends Model
{
    public const CONTEXT_CUSTOMER = 'customer';
    public const CONTEXT_ADMIN = 'admin';

    protected $fillable = [
        'user_id',
        'context',
        'code_hash',
        'expires_at',
        'attempts',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
            'last_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
