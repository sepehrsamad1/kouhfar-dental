<?php

declare(strict_types=1);

namespace Database\Factories;

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentItem>
 */
class ContentItemFactory extends Factory
{
    protected $model = ContentItem::class;

    public function definition(): array
    {
        return [
            'content_key' => 'test-faq',
            'kind' => ContentKind::Faq,
            'version' => 1,
            'locale' => 'fa',
            'body' => [
                'title' => 'Synthetic test content',
                'text' => 'Placeholder only; not clinic copy.',
            ],
            'status' => ContentItemStatus::Draft,
            'approval_scope' => 'general',
            'created_by' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'owner_approved_by' => null,
            'owner_approved_at' => null,
            'published_by' => null,
            'archived_at' => null,
            'published_at' => null,
        ];
    }
}
