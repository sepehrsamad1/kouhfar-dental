<?php

declare(strict_types=1);

namespace App\Policies;

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\StaffUser;

class ContentItemPolicy
{
    public function viewAny(StaffUser $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(StaffUser $user, ContentItem $contentItem): bool
    {
        return $user->role === 'admin';
    }

    public function create(StaffUser $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(StaffUser $user, ContentItem $contentItem): bool
    {
        return $user->role === 'admin' && $contentItem->isEditable();
    }

    public function submitReview(StaffUser $user, ContentItem $contentItem): bool
    {
        return $user->role === 'admin' && $contentItem->statusValue() === ContentItemStatus::Draft->value;
    }

    public function approve(StaffUser $user, ContentItem $contentItem): bool
    {
        return $user->role === 'admin' && $contentItem->statusValue() === ContentItemStatus::Review->value;
    }

    public function publish(StaffUser $user, ContentItem $contentItem): bool
    {
        if ($user->role !== 'admin' || $contentItem->statusValue() !== ContentItemStatus::Review->value) {
            return false;
        }

        return ! $contentItem->requiresOwnerApproval() || $contentItem->owner_approved_at !== null;
    }

    public function archive(StaffUser $user, ContentItem $contentItem): bool
    {
        return $user->role === 'admin' && $contentItem->statusValue() === ContentItemStatus::Published->value;
    }

    public function createVersion(StaffUser $user, ContentItem $contentItem): bool
    {
        return $user->role === 'admin' && $contentItem->statusValue() === ContentItemStatus::Published->value;
    }
}
