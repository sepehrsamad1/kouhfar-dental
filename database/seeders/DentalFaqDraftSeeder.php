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

        if (($document['version'] ?? null) !== '1.0.0' || count($document['faqs'] ?? []) !== 100) {
            throw new RuntimeException('Dental FAQ dataset version or item count is invalid.');
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
