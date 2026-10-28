<?php

declare(strict_types=1);

namespace App\Services;

use App\ContentItem;
use Illuminate\Support\Collection;

final class PublishedContentProvider
{
    /**
     * Return only content that has passed the publication gate.
     *
     * This is the single knowledge-source query for future knowledge-based AI features.
     */
    public function forKnowledge(string $locale = 'fa'): Collection
    {
        return ContentItem::query()
            ->published()
            ->where('locale', $locale)
            ->orderBy('content_key')
            ->orderByDesc('version')
            ->get()
            ->map(static fn (ContentItem $item): array => [
                'content_key' => $item->content_key,
                'kind' => $item->kind->value,
                'locale' => $item->locale,
                'version' => $item->version,
                'title' => (string) ($item->body['title'] ?? ''),
                'text' => (string) ($item->body['text'] ?? ''),
            ]);
    }
}
