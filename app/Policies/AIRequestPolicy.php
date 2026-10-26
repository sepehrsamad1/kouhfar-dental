<?php

declare(strict_types=1);

namespace App\Policies;

use App\AIRequest;
use App\StaffUser;

class AIRequestPolicy
{
    public function view(StaffUser $user, AIRequest $request): bool { return $user->role === 'admin'; }
    public function create(StaffUser $user): bool { return $user->role === 'admin'; }
}
