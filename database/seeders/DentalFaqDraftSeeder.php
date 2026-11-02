<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class DentalFaqDraftSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Dental FAQ draft import is disabled in production.');
        }

        $path = resource_path('knowledge/dental-faq-v1.json');
        $document = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $faqs = $document['faqs'] ?? [];
        $sources = $document['sources'] ?? [];

        if (($document['version'] ?? null) !== '1.0.0' || count($faqs) !== 100 || is_array($sources) === false) {
            throw new RuntimeException('Dental FAQ dataset version, source registry, or item count is invalid.');
        }

        foreach (['question_fa', 'question_en', 'answer_fa', 'answer_en'] as $field) {
            $values = array_column($faqs, $field);
            if (count($values) !== 100 || count(array_unique($values)) !== 100) {
                throw new RuntimeException("Dental FAQ {$field} values must be 100 distinct entries.");
            }
        }

        foreach ($faqs as $faq) {
            if (($faq['review_status'] ?? null) !== 'draft_for_clinician_review'
                || ! is_array($faq['sources'] ?? null)
                || $faq['sources'] === []
                || array_diff($faq['sources'], array_keys($sources)) !== []) {
                throw new RuntimeException('Every dental FAQ must remain a sourced clinician-review draft.');
            }
        }

        DB::transaction(function () use ($document): void {
            foreach ($document['faqs'] as $faq) {
                foreach (['fa' => ['question_fa', 'answer_fa'], 'en' => ['question_en', 'answer_en']] as $locale => [$questionKey, $answerKey]) {
                    ContentItem::query()->updateOrCreate(
                        [
                            'content_key' => 'dental-faq-'.$faq['id'],
                            'locale' => $locale,
                            'version' => 1,
                        ],
                        [
                            'kind' => ContentKind::Faq,
                            'body' => [
                                'title' => $faq[$questionKey],
                                'text' => $faq[$answerKey],
                                'sources' => $faq['sources'],
                                'source_references' => array_map(
                                    static fn (string $sourceId): array => ['id' => $sourceId, ...$document['sources'][$sourceId]],
                                    $faq['sources'],
                                ),
                                'confidence' => $faq['confidence'],
                                'review_status' => $faq['review_status'],
                                'warning' => $locale === 'fa' ? $faq['warning_fa'] : $faq['warning_en'],
                                'dataset_version' => $document['version'],
                            ],
                            'status' => ContentItemStatus::Draft,
                            'approval_scope' => 'clinical',
                            'created_by' => null,
                            'reviewed_by' => null,
                            'reviewed_at' => null,
                            'owner_approved_by' => null,
                            'owner_approved_at' => null,
                            'published_by' => null,
                            'published_at' => null,
                            'archived_at' => null,
                        ],
                    );
                }
            }
        });
    }
}
