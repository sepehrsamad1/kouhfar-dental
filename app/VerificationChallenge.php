<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class VerificationChallenge extends Model
{
    use HasUuids;

    protected $fillable = [
        'mobile_normalized', 'purpose', 'intent_hash', 'code_hash',
        'grant_hash', 'attempts', 'expires_at', 'verified_at',
        'consumed_at', 'consumed_key_hash',
    ];

    protected $hidden = ['code_hash', 'grant_hash', 'intent_hash', 'consumed_key_hash'];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }
}
