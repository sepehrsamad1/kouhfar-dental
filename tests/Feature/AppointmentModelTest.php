<?php

use App\Appointment;
use App\AppointmentEvent;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('allows the planned appointment fields for mass assignment', function () {
    expect((new Appointment)->getFillable())->toBe([
        'patient_id',
        'service_id',
        'status',
        'start_at',
        'end_at',
        'source',
        'expires_at',
        'created_by',
        'version',
    ]);
});

it('casts appointment status and timestamps', function () {
    expect((new Appointment)->getCasts())
        ->toMatchArray([
            'status' => AppointmentStatus::class,
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'version' => 'integer',
        ]);
});

it('defines patient and service belongs-to relationships', function () {
    $appointment = new Appointment;

    expect($appointment->patient())
        ->toBeInstanceOf(BelongsTo::class)
        ->and($appointment->patient()->getForeignKeyName())->toBe('patient_id')
        ->and($appointment->service())
        ->toBeInstanceOf(BelongsTo::class)
        ->and($appointment->service()->getForeignKeyName())->toBe('service_id');
});

it('defines the appointment event relationships', function () {
    $appointment = new Appointment;
    $event = new AppointmentEvent;

    expect($appointment->events())
        ->toBeInstanceOf(HasMany::class)
        ->and($appointment->events()->getForeignKeyName())->toBe('appointment_id')
        ->and($event->appointment())
        ->toBeInstanceOf(BelongsTo::class)
        ->and($event->appointment()->getForeignKeyName())->toBe('appointment_id');
});
