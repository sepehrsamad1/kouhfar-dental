<?php

use App\LoginAttempt;

it('uses the planned login attempt fields and hides identifier hashes', function () {
    $attempt = new LoginAttempt;

    expect($attempt->getFillable())->toBe([
        'identifier_hash',
        'ip_hash',
        'created_at',
        'success',
    ])
        ->and($attempt->getHidden())->toContain('identifier_hash', 'ip_hash')
        ->and($attempt->timestamps)->toBeFalse()
        ->and($attempt->getCasts())
        ->toMatchArray([
            'created_at' => 'immutable_datetime',
            'success' => 'boolean',
        ]);
});

it('makes synthetic hashed login attempt data without saving it', function () {
    $attempt = LoginAttempt::factory()->make();

    expect($attempt->exists)->toBeFalse()
        ->and($attempt->identifier_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($attempt->ip_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($attempt->success)->toBeFalse();
});
