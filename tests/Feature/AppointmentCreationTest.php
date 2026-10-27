<?php

declare(strict_types=1);

use App\Appointment;
use App\AppointmentEvent;
use App\ClinicWorkingHour;
use App\Domain\Booking\AppointmentBookingException;
use App\Domain\Booking\AppointmentCreator;
use App\Domain\Booking\AppointmentStateMachine;
use App\Enums\AppointmentStatus;
use App\IdempotencyRecord;
use App\Practitioner;
use App\Resource;
use App\Service;
use App\WorkingHour;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function bookingFixture(): array
{
    $service = Service::factory()->create([
        'duration_minutes' => 30,
        'buffer_before_minutes' => 10,
        'buffer_after_minutes' => 10,
        'is_active' => true,
        'is_public_bookable' => true,
        'required_resource_kind' => 'chair',
    ]);
    $practitioner = Practitioner::factory()->create(['is_active' => true]);
    $resource = Resource::factory()->create(['kind' => 'chair', 'is_active' => true]);

    $service->practitioners()->attach($practitioner);
    $service->resources()->attach($resource);

    ClinicWorkingHour::query()->create([
        'weekday' => 4,
        'start_local' => '12:00:00',
        'end_local' => '20:00:00',
        'timezone' => 'Asia/Tehran',
        'version' => 1,
    ]);
    WorkingHour::query()->create([
        'practitioner_id' => $practitioner->getKey(),
        'weekday' => 4,
        'start_local' => '12:00:00',
        'end_local' => '20:00:00',
        'version' => 1,
    ]);

    return [$service, $practitioner, $resource];
}

function bookingPayload(Service $service, Practitioner $practitioner, Resource $resource, string $name = 'Synthetic Booker'): array
{
    return [
        'patient_name' => $name,
        'mobile' => '09120000001',
        'service_id' => $service->getKey(),
        'practitioner_id' => $practitioner->getKey(),
        'resource_id' => $resource->getKey(),
        'start_at' => '2026-10-29T09:30:00Z',
    ];
}

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-27T08:00:00Z');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('creates one pending appointment transactionally and replays the same idempotency key', function () {
    [$service, $practitioner, $resource] = bookingFixture();
    $payload = bookingPayload($service, $practitioner, $resource);
    $creator = app(AppointmentCreator::class);

    $first = $creator->create($payload, 'idem-task14-000001');
    $second = $creator->create($payload, 'idem-task14-000001');

    expect($first['status'])->toBe(201)
        ->and($second['status'])->toBe($first['status'])
        ->and($second['body'])->toMatchArray($first['body'])
        ->and(Appointment::query()->count())->toBe(1)
        ->and(AppointmentEvent::query()->where('to_status', AppointmentStatus::Pending)->count())->toBe(1)
        ->and(IdempotencyRecord::query()->count())->toBe(1);

    $appointment = Appointment::query()->firstOrFail();
    expect($appointment->status)->toBe(AppointmentStatus::Pending)
        ->and($appointment->expires_at?->toIso8601String())->toBe('2026-10-27T09:00:00+00:00')
        ->and(DB::table('appointment_practitioners')->where('appointment_id', $appointment->id)->where('active', true)->exists())->toBeTrue()
        ->and(DB::table('appointment_resources')->where('appointment_id', $appointment->id)->where('active', true)->exists())->toBeTrue();
});

it('rejects the same idempotency key when the normalized request body differs', function () {
    [$service, $practitioner, $resource] = bookingFixture();
    $payload = bookingPayload($service, $practitioner, $resource);
    $creator = app(AppointmentCreator::class);

    $creator->create($payload, 'idem-task14-000002');

    expect(fn () => $creator->create(
        [...$payload, 'patient_name' => 'Different Synthetic Booker'],
        'idem-task14-000002',
    ))->toThrow(AppointmentBookingException::class);

    expect(Appointment::query()->count())->toBe(1);
});

it('returns SLOT_UNAVAILABLE when the selected slot is no longer available', function () {
    [$service, $practitioner, $resource] = bookingFixture();
    $creator = app(AppointmentCreator::class);
    $payload = bookingPayload($service, $practitioner, $resource);

    $first = $creator->create($payload, 'idem-task14-000003');
    $second = $creator->create([...$payload, 'mobile' => '09120000002'], 'idem-task14-000004');

    expect($first['status'])->toBe(201)
        ->and($second['status'])->toBe(409)
        ->and($second['body'])->toBe([
            'code' => 'SLOT_UNAVAILABLE',
            'message' => 'The selected slot is no longer available.',
        ])
        ->and(Appointment::query()->count())->toBe(1);
});

it('expires pending holds conditionally and releases capacity', function () {
    [$service, $practitioner, $resource] = bookingFixture();
    $creator = app(AppointmentCreator::class);
    $payload = bookingPayload($service, $practitioner, $resource);

    $created = $creator->create($payload, 'idem-task14-000005');
    $appointment = Appointment::query()->findOrFail($created['body']['appointment_id']);

    Carbon::setTestNow('2026-10-27T09:00:01Z');
    expect(app(AppointmentStateMachine::class)->expirePendingHolds())->toBe(1);

    $appointment->refresh();
    expect($appointment->status)->toBe(AppointmentStatus::Expired)
        ->and(DB::table('appointment_practitioners')->where('appointment_id', $appointment->id)->value('active'))->toBeFalse()
        ->and(DB::table('appointment_resources')->where('appointment_id', $appointment->id)->value('active'))->toBeFalse();

    $created = $creator->create(
        [...$payload, 'mobile' => '09120000002'],
        'idem-task14-000006',
    );
    $confirmed = Appointment::query()->findOrFail($created['body']['appointment_id']);

    app(AppointmentStateMachine::class)->transition(
        $confirmed,
        AppointmentStatus::Confirmed,
        'system',
        null,
        'test_confirmed',
    );

    expect(app(AppointmentStateMachine::class)->expirePendingHolds())->toBe(0)
        ->and($confirmed->refresh()->status)->toBe(AppointmentStatus::Confirmed);
});

it('allows only the defined appointment state transitions', function () {
    [$service, $practitioner, $resource] = bookingFixture();
    $creator = app(AppointmentCreator::class);
    $created = $creator->create(bookingPayload($service, $practitioner, $resource), 'idem-task14-000007');
    $appointment = Appointment::query()->findOrFail($created['body']['appointment_id']);

    app(AppointmentStateMachine::class)->transition(
        $appointment,
        AppointmentStatus::Confirmed,
        'system',
        null,
        'test_confirmed',
    );

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed);

    expect(fn () => app(AppointmentStateMachine::class)->transition(
        $appointment->refresh(),
        AppointmentStatus::Expired,
        'system',
    ))->toThrow(LogicException::class);

    expect(AppointmentEvent::query()->where('appointment_id', $appointment->id)->count())->toBe(2);
});

it('rejects an invalid direct database status transition', function () {
    [$service, $practitioner, $resource] = bookingFixture();
    $creator = app(AppointmentCreator::class);
    $created = $creator->create(bookingPayload($service, $practitioner, $resource), 'idem-task14-000008');
    $appointment = Appointment::query()->findOrFail($created['body']['appointment_id']);

    expect(fn () => DB::table('appointments')
        ->where('id', $appointment->id)
        ->update(['status' => AppointmentStatus::Completed->value]))
        ->toThrow(QueryException::class);
});

it('releases an expired pending hold before a later booking is checked', function () {
    [$service, $practitioner, $resource] = bookingFixture();
    $creator = app(AppointmentCreator::class);
    $payload = bookingPayload($service, $practitioner, $resource);

    $creator->create($payload, 'idem-task14-000009');
    Carbon::setTestNow('2026-10-27T09:00:01Z');

    $result = $creator->create([...$payload, 'mobile' => '09120000003'], 'idem-task14-000010');

    expect($result['status'])->toBe(201)
        ->and(Appointment::query()->where('status', AppointmentStatus::Expired)->count())->toBe(1)
        ->and(Appointment::query()->where('status', AppointmentStatus::Pending)->count())->toBe(1);
});
