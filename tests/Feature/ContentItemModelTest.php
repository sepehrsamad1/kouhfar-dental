<?php

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;

it('allows the planned content item fields', function () {
    expect((new ContentItem)->getFillable())->toBe([
        'kind',
        'locale',
        'body',
        'status',
        'published_at',
    ]);
});

it('casts content kind status body and publication time', function () {
    expect((new ContentItem)->getCasts())
        ->toMatchArray([
            'kind' => ContentKind::class,
            'body' => 'array',
            'status' => ContentItemStatus::class,
            'published_at' => 'immutable_datetime',
        ]);
});

it('makes synthetic draft content without saving it', function () {
    $content = ContentItem::factory()->make();

    expect($content->exists)->toBeFalse()
        ->and($content->kind)->toBe(ContentKind::Faq)
        ->and($content->locale)->toBe('fa')
        ->and($content->status)->toBe(ContentItemStatus::Draft)
        ->and($content->published_at)->toBeNull()
        ->and($content->body['title'])->toBe('Synthetic test content');
});
