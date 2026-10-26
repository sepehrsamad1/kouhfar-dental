<?php

use App\Patient;

it('allows only the approved patient fields for mass assignment', function () {
    expect((new Patient)->getFillable())->toBe([
        'full_name',
        'mobile_normalized',
    ]);
});

it('casts the archive timestamp as immutable', function () {
    expect((new Patient)->getCasts()['archived_at'])
        ->toBe('immutable_datetime');
});

it('creates synthetic patient data without saving to the database', function () {
    $patient = Patient::factory()->make();

    expect($patient->exists)->toBeFalse()
        ->and($patient->full_name)->not->toBeEmpty()
        ->and($patient->mobile_normalized)->toMatch('/^09\\d{9}$/');
});
