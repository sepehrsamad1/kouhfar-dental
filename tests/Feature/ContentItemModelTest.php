<?php

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;

it('allows the planned content item fields', function () {
    expect((new ContentItem)->getFillable())->toBe([
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
        ->and($content->content_key)->toBe('test-faq')
        ->and($content->version)->toBe(1)
        ->and($content->kind)->toBe(ContentKind::Faq)
        ->and($content->locale)->toBe('fa')
        ->and($content->status)->toBe(ContentItemStatus::Draft)
        ->and($content->published_at)->toBeNull()
        ->and($content->body['title'])->toBe('Synthetic test content');
});
