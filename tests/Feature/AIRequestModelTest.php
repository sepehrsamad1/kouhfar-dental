<?php

use App\AIRequest;

it('uses the explicit ai requests table name', function () {
    expect((new AIRequest)->getTable())->toBe('ai_requests');
});

it('disables automatic timestamps and casts request metadata', function () {
    $request = new AIRequest;

    expect($request->usesTimestamps())->toBeFalse()
        ->and($request->getFillable())->toBe([
            'feature',
            'actor_type',
            'actor_id',
            'status',
            'created_at',
            'content_ids',
        ])
        ->and($request->getCasts())
        ->toMatchArray([
            'created_at' => 'immutable_datetime',
            'content_ids' => 'array',
        ]);
});

it('makes synthetic AI request metadata without saving it', function () {
    $request = AIRequest::factory()->make();

    expect($request->exists)->toBeFalse()
        ->and($request->feature)->toBe('assistant')
        ->and($request->status)->toBe('synthetic-test-only')
        ->and($request->content_ids)->toBe([]);
});
