<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $hidden = [
        'token_hash',
    ];

    protected $fillable = [
        'user_id',
        'token_hash',
        'created_at',
        'expires_at',
        'revoked_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class, 'user_id');
    }
}
