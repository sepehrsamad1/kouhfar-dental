<?php

declare(strict_types=1);

namespace App\Policies;

use App\ContentItem;
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
        return $user->role === 'admin' && $contentItem->status->value === 'draft';
    }

    public function approve(StaffUser $user, ContentItem $contentItem): bool
    {
        return $user->role === 'admin' && $contentItem->status->value === 'review';
    }

    public function publish(StaffUser $user, ContentItem $contentItem): bool
    {
        if ($user->role !== 'admin' || $contentItem->status->value !== 'review') {
            return false;
        }

        return ! $contentItem->requiresOwnerApproval() || $contentItem->owner_approved_at !== null;
    }

    public function archive(StaffUser $user, ContentItem $contentItem): bool
    {
        return $user->role === 'admin' && $contentItem->status->value === 'published';
    }

    public function createVersion(StaffUser $user, ContentItem $contentItem): bool
    {
        return $user->role === 'admin' && $contentItem->status->value === 'published';
    }
}
