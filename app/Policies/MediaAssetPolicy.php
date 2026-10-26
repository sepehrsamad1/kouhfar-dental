<?php

declare(strict_types=1);

namespace App\Policies;

use App\MediaAsset;
use App\StaffUser;

class MediaAssetPolicy
{
    public function view(StaffUser $user, MediaAsset $mediaAsset): bool
    {
        return $user->role === 'admin';
    }

    public function create(StaffUser $user): bool
    {
        return $user->role === 'admin';
    }

    public function delete(StaffUser $user, MediaAsset $mediaAsset): bool
    {
        return $user->role === 'admin';
    }
}
