<?php

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;
use Database\Seeders\DentalFaqDraftSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('imports the bilingual FAQ package as draft clinical content only', function (): void {
    $this->seed(DentalFaqDraftSeeder::class);

    expect(ContentItem::query()->where('kind', ContentKind::Faq)->count())->toBe(200)
        ->and(ContentItem::query()->where('kind', ContentKind::Faq)->where('status', ContentItemStatus::Published)->count())->toBe(0)
        ->and(ContentItem::query()->where('kind', ContentKind::Faq)->where('approval_scope', 'clinical')->count())->toBe(200);
});
