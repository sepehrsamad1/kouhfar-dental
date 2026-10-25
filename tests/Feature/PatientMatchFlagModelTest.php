<?php

use App\Enums\PatientMatchFlagStatus;
use App\Patient;
use App\PatientMatchFlag;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('casts patient match status and defines both patient relationships', function () {
    $flag = new PatientMatchFlag;

    expect($flag->getCasts()['status'])->toBe(PatientMatchFlagStatus::class)
        ->and($flag->patientA())
        ->toBeInstanceOf(BelongsTo::class)
        ->and($flag->patientA()->getForeignKeyName())->toBe('patient_a_id')
        ->and($flag->patientA()->getRelated())->toBeInstanceOf(Patient::class)
        ->and($flag->patientB())
        ->toBeInstanceOf(BelongsTo::class)
        ->and($flag->patientB()->getForeignKeyName())->toBe('patient_b_id')
        ->and($flag->patientB()->getRelated())->toBeInstanceOf(Patient::class);
});

it('makes synthetic open match data without saving it', function () {
    $flag = PatientMatchFlag::factory()->make();

    expect($flag->exists)->toBeFalse()
        ->and($flag->reason)->toBe('synthetic-test-only')
        ->and($flag->status)->toBe(PatientMatchFlagStatus::Open)
        ->and($flag->patient_a_id < $flag->patient_b_id)->toBeTrue();
});
