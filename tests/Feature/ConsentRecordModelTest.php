<?php

use App\ConsentRecord;
use App\Patient;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('allows the planned consent record fields', function () {
    expect((new ConsentRecord)->getFillable())->toBe([
        'patient_id',
        'scope',
        'granted_at',
        'revoked_at',
        'document_ref',
    ]);
});

it('casts consent timestamps as immutable', function () {
    expect((new ConsentRecord)->getCasts())
        ->toMatchArray([
            'granted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ]);
});

it('defines the patient relationship', function () {
    $relationship = (new ConsentRecord)->patient();

    expect($relationship)
        ->toBeInstanceOf(BelongsTo::class)
        ->and($relationship->getForeignKeyName())->toBe('patient_id')
        ->and($relationship->getRelated())->toBeInstanceOf(Patient::class);
});

it('makes synthetic ungranted consent data without saving it', function () {
    $consent = ConsentRecord::factory()->make();

    expect($consent->exists)->toBeFalse()
        ->and($consent->scope)->toBe('synthetic-test-only')
        ->and($consent->granted_at)->toBeNull()
        ->and($consent->revoked_at)->toBeNull();
});
