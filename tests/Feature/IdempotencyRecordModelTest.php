<?php

use App\IdempotencyRecord;

it('uses the expected idempotency records table', function () {
    expect((new IdempotencyRecord)->getTable())->toBe('idempotency_records');
});

it('disables automatic timestamps and casts record fields', function () {
    $record = new IdempotencyRecord;

    expect($record->usesTimestamps())->toBeFalse()
        ->and($record->getFillable())->toBe([
            'owner_scope',
            'key',
            'request_hash',
            'response_status',
            'response_body',
            'created_at',
            'expires_at',
        ])
        ->and($record->getCasts())
        ->toMatchArray([
            'response_status' => 'integer',
            'response_body' => 'array',
            'created_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ]);
});

it('makes synthetic idempotency data without saving it', function () {
    $record = IdempotencyRecord::factory()->make();

    expect($record->exists)->toBeFalse()
        ->and($record->owner_scope)->toBe('synthetic-test-only')
        ->and($record->response_status)->toBe(200)
        ->and($record->response_body)->toBe(['test' => true])
        ->and($record->created_at)->not->toBeNull()
        ->and($record->expires_at)->not->toBeNull();
});
