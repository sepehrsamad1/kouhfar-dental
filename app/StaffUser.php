<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffUser extends Model
{
    use HasFactory;
    use HasUuids;

    protected $hidden = [
        'password_hash',
    ];

    protected $fillable = [
        'email',
        'display_name',
        'role',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
