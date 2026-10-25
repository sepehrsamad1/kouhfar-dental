<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $hidden = [
        'identifier_hash',
        'ip_hash',
    ];

    protected $fillable = [
        'identifier_hash',
        'ip_hash',
        'created_at',
        'success',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'success' => 'boolean',
        ];
    }
}
