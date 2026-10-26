<?php

declare(strict_types=1);

namespace App\Policies;

use App\ContentItem;
use App\StaffUser;

class ContentItemPolicy
{
    public function view(StaffUser $user): bool { return $user->role === 'admin'; }
    public function create(StaffUser $user): bool { return $user->role === 'admin'; }
    public function update(StaffUser $user, ContentItem $contentItem): bool { return $user->role === 'admin'; }
    public function delete(StaffUser $user, ContentItem $contentItem): bool { return $user->role === 'admin'; }
}
