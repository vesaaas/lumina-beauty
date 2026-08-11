<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestCheckoutOtp extends Model
{
    protected $fillable = [
        'session_id',
        'email',
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
}
