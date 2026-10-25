<?php

use App\Appointment;
use App\Patient;
use App\Service;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines patient and service has-many appointment relationships', function () {
    $patientAppointments = (new Patient)->appointments();
    $serviceAppointments = (new Service)->appointments();

    expect($patientAppointments)
        ->toBeInstanceOf(HasMany::class)
        ->and($patientAppointments->getForeignKeyName())->toBe('patient_id')
        ->and($patientAppointments->getRelated())->toBeInstanceOf(Appointment::class)
        ->and($serviceAppointments)
        ->toBeInstanceOf(HasMany::class)
        ->and($serviceAppointments->getForeignKeyName())->toBe('service_id')
        ->and($serviceAppointments->getRelated())->toBeInstanceOf(Appointment::class);
});
