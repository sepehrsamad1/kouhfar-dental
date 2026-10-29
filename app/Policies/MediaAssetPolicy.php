<?php

declare(strict_types=1);

namespace App\Policies;

use App\MediaAsset;
use App\StaffUser;

class MediaAssetPolicy
{
    public function view(StaffUser $user, MediaAsset $mediaAsset): bool
    {
        return $user->is_active && $user->role === 'admin' && $mediaAsset->deleted_at === null;
    }

    public function create(StaffUser $user): bool
    {
        return $user->is_active && $user->role === 'admin';
    }

    public function delete(StaffUser $user, MediaAsset $mediaAsset): bool
    {
        return $user->is_active && $user->role === 'admin' && $mediaAsset->deleted_at === null;
    }
}
