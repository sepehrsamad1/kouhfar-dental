<?php

declare(strict_types=1);

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffUser extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasFactory;
    use HasUuids;

    protected $hidden = [
        'password_hash',
        'mfa_totp_secret',
        'mfa_recovery_codes',
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
            'mfa_totp_secret' => 'encrypted',
            'mfa_recovery_codes' => 'encrypted:array',
            'mfa_enabled_at' => 'immutable_datetime',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }
}
