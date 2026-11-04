<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $phone_hash
 * @property string $ip_hash
 * @property string|null $code_hash
 * @property string $status
 * @property int $attempts
 * @property \\Carbon\\CarbonImmutable $expires_at
 * @property \\Carbon\\CarbonImmutable|null $last_sent_at
 * @property \\Carbon\\CarbonImmutable|null $verified_at
 * @property \\Carbon\\CarbonImmutable|null $consumed_at
 */
class BookingOtpChallenge extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'phone_hash',
        'ip_hash',
        'code_hash',
        'status',
        'attempts',
        'expires_at',
        'last_sent_at',
        'verified_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'immutable_datetime',
            'last_sent_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }
}
