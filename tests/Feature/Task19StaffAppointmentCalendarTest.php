<?php

declare(strict_types=1);

use App\Appointment;
use App\ClinicWorkingHour;
use App\Enums\AppointmentStatus;
use App\Practitioner;
use App\Resource;
use App\Service;
use App\StaffUser;
use App\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function task19Staff(string $role = 'reception'): StaffUser
{
    $staff = StaffUser::factory()->active()->create(['role' => $role]);
    $staff->forceFill(['password_hash' => Hash::make('task19-password')])->save();

    test()->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'task19-password',
    ])->assertRedirect();

    return $staff;
}

function task19BookingFixture(): array
{
    $service = Service::factory()->create([
        'duration_minutes' => 60,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
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
    WorkingHour::factory()->create([
        'practitioner_id' => $practitioner->id,
        'weekday' => 4,
        'start_local' => '12:00:00',
        'end_local' => '20:00:00',
        'version' => 1,
    ]);

    return [$service, $practitioner, $resource];
}

function task19Appointment(
    Service $service,
    Practitioner $practitioner,
    Resource $resource,
    AppointmentStatus $status,
    string $startUtc,
): Appointment {
    $start = CarbonImmutable::parse($startUtc, 'UTC');
    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'status' => $status,
        'start_at' => $start,
        'end_at' => $start->addHour(),
        'version' => 1,
        'expires_at' => null,
    ]);

    $range = sprintf(
        "tstzrange('%s'::timestamptz, '%s'::timestamptz, '[)')",
        $start->format('Y-m-d H:i:sP'),
        $start->addHour()->format('Y-m-d H:i:sP'),
    );

    DB::table('appointment_practitioners')->insert([
        'appointment_id' => $appointment->id,
        'practitioner_id' => $practitioner->id,
        'active' => in_array($status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true),
        'occupied' => DB::raw($range),
    ]);
    DB::table('appointment_resources')->insert([
        'appointment_id' => $appointment->id,
        'resource_id' => $resource->id,
        'active' => in_array($status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true),
        'occupied' => DB::raw($range),
    ]);

    return $appointment->refresh();
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow('2026-10-29T08:00:00Z');
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('renders day week and accessible list calendar views in clinic local time', function (): void {
    task19Staff();

    $response = $this->get(route('staff.appointments.index', [
        'view' => 'day',
        'date' => '2026-10-29',
    ]));

    $response->assertOk()
        ->assertSee('تقویم نوبت‌ها')
        ->assertSee('Asia/Tehran')
        ->assertSee('فهرست دسترس‌پذیر');

    $this->get(route('staff.appointments.index', [
        'view' => 'week',
        'date' => '2026-10-29',
    ]))->assertOk()->assertSee('تقویم هفتگی');

    $this->get(route('staff.appointments.index', [
        'view' => 'list',
        'date' => '2026-10-29',
    ]))->assertOk()->assertSee('فهرست قابل دسترس');
});

it('enforces role separation for appointment actions', function (): void {
    [$service, $practitioner, $resource] = task19BookingFixture();
    $appointment = task19Appointment($service, $practitioner, $resource, AppointmentStatus::Confirmed, '2026-11-05T08:30:00Z');

    task19Staff('practitioner');

    $this->post(route('staff.appointments.cancel', $appointment), ['version' => $appointment->version])
        ->assertForbidden();

    $this->post(route('staff.appointments.complete', $appointment), ['version' => $appointment->version])
        ->assertForbidden();
});

it('rejects stale appointment versions without changing the appointment', function (): void {
    [$service, $practitioner, $resource] = task19BookingFixture();
    $appointment = task19Appointment($service, $practitioner, $resource, AppointmentStatus::Confirmed, '2026-11-05T08:30:00Z');

    task19Staff('reception');

    $response = $this->postJson(route('staff.appointments.cancel', $appointment), [
        'version' => $appointment->version - 1,
    ]);

    $response->assertStatus(409)->assertJsonPath('code', 'APPOINTMENT_CONFLICT');

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->version)->toBe(1);
});

it('enforces the approved 24-hour cancellation and rescheduling cutoff', function (): void {
    [$service, $practitioner, $resource] = task19BookingFixture();

    $appointment = task19Appointment(
        $service,
        $practitioner,
        $resource,
        AppointmentStatus::Confirmed,
        '2026-10-30T09:00:00Z'
    );

    task19Staff('reception');

    $cancel = $this->postJson(route('staff.appointments.cancel', $appointment), [
        'version' => $appointment->version,
    ]);

    $cancel->assertStatus(409)
        ->assertJsonPath('code', 'APPOINTMENT_CONFLICT');

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed);
});

it('allows a started confirmed appointment to be completed and prevents illegal transitions', function (): void {
    [$service, $practitioner, $resource] = task19BookingFixture();

    $appointment = task19Appointment(
        $service,
        $practitioner,
        $resource,
        AppointmentStatus::Confirmed,
        '2026-10-29T07:00:00Z'
    );

    task19Staff('reception');

    $response = $this->postJson(route('staff.appointments.complete', $appointment), [
        'version' => $appointment->version,
    ]);

    $response->assertOk();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Completed);

    $illegal = $this->postJson(route('staff.appointments.complete', $appointment), [
        'version' => $appointment->version,
    ]);

    $illegal->assertStatus(409)->assertJsonPath('code', 'APPOINTMENT_CONFLICT');
});

it('rechecks availability and safely rejects a reschedule into an occupied slot', function (): void {
    [$service, $practitioner, $resource] = task19BookingFixture();

    $original = task19Appointment(
        $service,
        $practitioner,
        $resource,
        AppointmentStatus::Confirmed,
        '2026-11-05T08:30:00Z'
    );
    task19Appointment(
        $service,
        $practitioner,
        $resource,
        AppointmentStatus::Confirmed,
        '2026-11-05T09:30:00Z'
    );

    task19Staff('reception');

    $response = $this->patchJson(route('staff.appointments.reschedule', $original), [
        'version' => $original->version,
        'start_at' => '2026-11-05T13:00',
    ]);

    $response->assertStatus(409)->assertJsonPath('code', 'APPOINTMENT_CONFLICT');

    expect($original->refresh()->start_at->toIso8601String())->toBe('2026-11-05T08:30:00+00:00')
        ->and($original->status)->toBe(AppointmentStatus::Confirmed);
});

it('confirms a pending appointment only after a fresh availability check', function (): void {
    [$service, $practitioner, $resource] = task19BookingFixture();

    $pending = task19Appointment(
        $service,
        $practitioner,
        $resource,
        AppointmentStatus::Pending,
        '2026-11-05T08:30:00Z'
    );
    $pending->forceFill(['expires_at' => now('UTC')->addHour()])->save();

    task19Staff('reception');

    $response = $this->postJson(route('staff.appointments.confirm', $pending), [
        'version' => $pending->version,
    ]);

    $response->assertOk();

    expect($pending->refresh()->status)->toBe(AppointmentStatus::Confirmed)
        ->and($pending->version)->toBe(2);
});
