<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdempotencyRecord extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'owner_scope',
        'key',
        'request_hash',
        'response_status',
        'response_body',
        'created_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'response_body' => 'array',
            'created_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
