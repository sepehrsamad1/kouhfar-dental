<?php

declare(strict_types=1);

namespace App;

use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'content_key',
        'kind',
        'locale',
        'version',
        'body',
        'status',
        'approval_scope',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'owner_approved_by',
        'owner_approved_at',
        'published_by',
        'published_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ContentKind::class,
            'body' => 'array',
            'status' => ContentItemStatus::class,
            'version' => 'integer',
            'reviewed_at' => 'immutable_datetime',
            'owner_approved_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', ContentItemStatus::Published->value)
            ->whereNotNull('published_at');
    }

    public function statusValue(): string
    {
        return (string) $this->getRawOriginal('status');
    }

    public function kindValue(): string
    {
        return (string) $this->getRawOriginal('kind');
    }

    /** @return array<string, mixed> */
    public function bodyData(): array
    {
        $body = $this->getAttribute('body');

        return is_array($body) ? $body : [];
    }

    public function requiresOwnerApproval(): bool
    {
        return $this->approval_scope !== 'general';
    }

    public function isEditable(): bool
    {
        return in_array($this->statusValue(), [
            ContentItemStatus::Draft->value,
            ContentItemStatus::Review->value,
        ], true);
    }
}
