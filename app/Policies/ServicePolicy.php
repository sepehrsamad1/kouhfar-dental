<?php

declare(strict_types=1);

namespace App\Policies;

use App\Service;
use App\StaffUser;

class ServicePolicy
{
    public function view(StaffUser $user): bool
    {
        return in_array($user->role, ['admin', 'reception', 'practitioner'], true);
    }

    public function create(StaffUser $user): bool { return $user->role === 'admin'; }
    public function update(StaffUser $user, Service $service): bool { return $user->role === 'admin'; }
    public function delete(StaffUser $user, Service $service): bool { return $user->role === 'admin'; }
}
