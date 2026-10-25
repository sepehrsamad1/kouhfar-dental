<?php

use App\Session;
use App\StaffUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('uses the planned session fields and hides its token hash', function () {
    $session = new Session;

    expect($session->getFillable())->toBe([
        'user_id',
        'token_hash',
        'created_at',
        'expires_at',
        'revoked_at',
        'last_seen_at',
    ])
        ->and($session->getHidden())->toContain('token_hash')
        ->and($session->timestamps)->toBeFalse()
        ->and($session->getCasts())
        ->toMatchArray([
            'created_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
        ]);
});

it('defines the staff user relationship', function () {
    $session = new Session;

    expect($session->user())
        ->toBeInstanceOf(BelongsTo::class)
        ->and($session->user()->getRelated())
        ->toBeInstanceOf(StaffUser::class)
        ->and($session->user()->getForeignKeyName())
        ->toBe('user_id');
});

it('makes synthetic session data without saving it', function () {
    $session = Session::factory()->make();

    expect($session->exists)->toBeFalse()
        ->and($session->token_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($session->revoked_at)->toBeNull()
        ->and($session->last_seen_at)->toBeNull();
});
