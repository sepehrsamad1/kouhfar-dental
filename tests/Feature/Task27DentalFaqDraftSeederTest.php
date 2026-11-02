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

it('contains 100 distinct bilingual questions and answers with valid sources', function (): void {
    $document = json_decode(
        (string) file_get_contents(resource_path('knowledge/dental-faq-v1.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($document['faqs'])->toHaveCount(100)
        ->and(collect($document['faqs'])->pluck('question_fa')->unique())->toHaveCount(100)
        ->and(collect($document['faqs'])->pluck('question_en')->unique())->toHaveCount(100)
        ->and(collect($document['faqs'])->pluck('answer_fa')->unique())->toHaveCount(100)
        ->and(collect($document['faqs'])->pluck('answer_en')->unique())->toHaveCount(100);

    foreach ($document['faqs'] as $faq) {
        expect($faq['review_status'])->toBe('draft_for_clinician_review')
            ->and($faq['sources'])->not->toBeEmpty();

        foreach ($faq['sources'] as $source) {
            expect($document['sources'])->toHaveKey($source);
        }
    }
});
