<?php

declare(strict_types=1);

namespace App\Policies;

use App\Resource;
use App\StaffUser;

class ResourcePolicy
{
    public function view(StaffUser $user): bool
    {
        return in_array($user->role, ['admin', 'reception', 'practitioner'], true);
    }

    public function create(StaffUser $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(StaffUser $user, Resource $resource): bool
    {
        return $user->role === 'admin';
    }

    public function delete(StaffUser $user, Resource $resource): bool
    {
        return $user->role === 'admin';
    }
}
