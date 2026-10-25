<?php

use App\Appointment;
use App\FollowUpToken;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('uses the planned follow-up token fields and hides its token hash', function () {
    $token = new FollowUpToken;

    expect($token->getFillable())->toBe([
        'appointment_id',
        'token_hash',
        'action_scope',
        'expires_at',
        'used_at',
    ])
        ->and($token->getHidden())->toContain('token_hash')
        ->and($token->timestamps)->toBeFalse()
        ->and($token->getCasts())
        ->toMatchArray([
            'expires_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
        ]);
});

it('defines the appointment relationship', function () {
    $token = new FollowUpToken;

    expect($token->appointment())
        ->toBeInstanceOf(BelongsTo::class)
        ->and($token->appointment()->getRelated())
        ->toBeInstanceOf(Appointment::class)
        ->and($token->appointment()->getForeignKeyName())
        ->toBe('appointment_id');
});

it('makes synthetic follow-up token data without saving it', function () {
    $token = FollowUpToken::factory()->make();

    expect($token->exists)->toBeFalse()
        ->and($token->token_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($token->action_scope)->toBeIn(['view', 'cancel', 'reschedule'])
        ->and($token->used_at)->toBeNull();
});
