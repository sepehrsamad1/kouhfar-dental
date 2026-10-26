<?php

use App\AuditLog;

it('allows only the planned audit log fields', function () {
    expect((new AuditLog)->getFillable())->toBe([
        'actor_id',
        'action',
        'target_type',
        'target_id',
        'occurred_at',
        'request_id',
        'meta',
    ]);
});

it('disables automatic timestamps and casts audit values', function () {
    $auditLog = new AuditLog;

    expect($auditLog->usesTimestamps())->toBeFalse()
        ->and($auditLog->getCasts())
        ->toMatchArray([
            'occurred_at' => 'immutable_datetime',
            'meta' => 'array',
        ]);
});

it('makes synthetic audit data without saving it', function () {
    $auditLog = AuditLog::factory()->make();

    expect($auditLog->exists)->toBeFalse()
        ->and($auditLog->action)->toBe('test.event')
        ->and($auditLog->target_type)->toBe('TestRecord')
        ->and($auditLog->meta)->toBeNull();
});
