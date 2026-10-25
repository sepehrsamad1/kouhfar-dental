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
            'kind' => ContentKind::Faq,
            'locale' => 'fa',
            'body' => [
                'title' => 'Synthetic test content',
                'text' => 'Placeholder only; not clinic copy.',
            ],
            'status' => ContentItemStatus::Draft,
            'published_at' => null,
        ];
    }
}
