<?php

use App\AppointmentEvent;
use App\Enums\AppointmentStatus;
use Illuminate\Support\Str;

it('allows only the planned appointment event fields', function () {
    expect((new AppointmentEvent)->getFillable())->toBe([
        'appointment_id',
        'actor_type',
        'actor_id',
        'from_status',
        'to_status',
        'occurred_at',
    ]);
});

it('casts appointment event statuses and timestamp', function () {
    expect((new AppointmentEvent)->getCasts())
        ->toMatchArray([
            'from_status' => AppointmentStatus::class,
            'to_status' => AppointmentStatus::class,
            'occurred_at' => 'immutable_datetime',
        ]);
});

it('makes synthetic appointment event data without saving it', function () {
    $event = AppointmentEvent::factory()->make([
        'appointment_id' => (string) Str::uuid(),
    ]);

    expect($event->exists)->toBeFalse()
        ->and($event->actor_type)->toBe('system')
        ->and($event->from_status)->toBe(AppointmentStatus::Pending)
        ->and($event->to_status)->toBe(AppointmentStatus::Cancelled)
        ->and($event->occurred_at)->not->toBeNull();
});
