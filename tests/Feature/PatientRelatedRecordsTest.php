<?php

use App\Appointment;
use App\ConsentRecord;
use App\MediaAsset;
use App\Patient;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines patient appointment consent and media relationships', function () {
    $patient = new Patient;

    expect($patient->appointments())
        ->toBeInstanceOf(HasMany::class)
        ->and($patient->appointments()->getForeignKeyName())->toBe('patient_id')
        ->and($patient->appointments()->getRelated())->toBeInstanceOf(Appointment::class)
        ->and($patient->consentRecords())
        ->toBeInstanceOf(HasMany::class)
        ->and($patient->consentRecords()->getForeignKeyName())->toBe('patient_id')
        ->and($patient->consentRecords()->getRelated())->toBeInstanceOf(ConsentRecord::class)
        ->and($patient->mediaAssets())
        ->toBeInstanceOf(HasMany::class)
        ->and($patient->mediaAssets()->getForeignKeyName())->toBe('owner_patient_id')
        ->and($patient->mediaAssets()->getRelated())->toBeInstanceOf(MediaAsset::class);
});
