<?php

declare(strict_types=1);

use App\Appointment;
use App\ClinicWorkingHour;
use App\Enums\AppointmentStatus;
use App\Http\Middleware\EnsureStaffSessionIsFresh;
use App\Patient;
use App\ScheduleException;
use App\Service;
use App\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(EnsureStaffSessionIsFresh::class);
});

function configurationStaff(string $role): StaffUser
{
    return StaffUser::factory()->active()->create(['role' => $role]);
}

it('allows authorized staff to view configuration but only admin to change it', function (): void {
    $reception = configurationStaff('reception');

    $this->actingAs($reception, 'staff')
        ->get(route('staff.configuration.index'))
        ->assertOk();

    $this->actingAs($reception, 'staff')
        ->post(route('staff.configuration.services.store'), [
            'name_fa' => 'خدمت آزمایشی',
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ])
        ->assertForbidden();

    $admin = configurationStaff('admin');
    $this->actingAs($admin, 'staff')
        ->post(route('staff.configuration.services.store'), [
            'name_fa' => 'خدمت آزمایشی',
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ])
        ->assertRedirect();

    expect(Service::query()->where('name_fa', 'خدمت آزمایشی')->firstOrFail()->is_active)->toBeFalse();
});

it('rejects cross-midnight clinic schedule periods', function (): void {
    $admin = configurationStaff('admin');

    $this->actingAs($admin, 'staff')
        ->from(route('staff.configuration.index'))
        ->post(route('staff.configuration.clinic-hours.store'), [
            'weekday' => 6,
            'start_local' => '20:00',
            'end_local' => '12:00',
            'timezone' => 'Asia/Tehran',
        ])
        ->assertSessionHasErrors(['end_local']);

    expect(ClinicWorkingHour::query()->count())->toBe(0);
});

it('rejects stale service configuration versions', function (): void {
    $admin = configurationStaff('admin');
    $service = Service::factory()->create([
        'name_fa' => 'خدمت قدیمی',
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'is_active' => false,
    ]);

    $this->actingAs($admin, 'staff')
        ->from(route('staff.configuration.index'))
        ->patch(route('staff.configuration.services.update', $service), [
            'version' => 999,
            'name_fa' => 'تغییر قدیمی',
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'is_active' => 0,
        ])
        ->assertSessionHasErrors(['version']);

    expect($service->refresh()->name_fa)->toBe('خدمت قدیمی')
        ->and($service->version)->toBe(1);
});

it('blocks service duration changes that could affect confirmed appointments and preserves history on deactivation', function (): void {
    $admin = configurationStaff('admin');
    $service = Service::factory()->create([
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'is_active' => true,
    ]);
    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'status' => AppointmentStatus::Confirmed,
        'start_at' => now('UTC')->addDays(30),
        'end_at' => now('UTC')->addDays(30)->addMinutes(30),
    ]);

    $this->actingAs($admin, 'staff')
        ->from(route('staff.configuration.index'))
        ->patch(route('staff.configuration.services.update', $service), [
            'version' => $service->version,
            'name_fa' => $service->name_fa,
            'duration_minutes' => 45,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'is_active' => 1,
        ])
        ->assertSessionHasErrors(['service']);

    expect($service->refresh()->duration_minutes)->toBe(30)
        ->and(Appointment::query()->whereKey($appointment->id)->exists())->toBeTrue();

    $this->actingAs($admin, 'staff')
        ->from(route('staff.configuration.index'))
        ->patch(route('staff.configuration.services.update', $service), [
            'version' => $service->version,
            'name_fa' => $service->name_fa,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'is_active' => 0,
        ])
        ->assertRedirect();

    expect($service->refresh()->is_active)->toBeFalse()
        ->and(Appointment::query()->whereKey($appointment->id)->exists())->toBeTrue();
});

it('blocks a closure that overlaps a confirmed appointment without changing the appointment', function (): void {
    $admin = configurationStaff('admin');
    $service = Service::factory()->create();
    $patient = Patient::factory()->create();
    $start = Carbon::parse('2026-11-28 10:30', 'Asia/Tehran')->utc();
    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'status' => AppointmentStatus::Confirmed,
        'start_at' => $start,
        'end_at' => $start->copy()->addHour(),
    ]);

    $this->actingAs($admin, 'staff')
        ->from(route('staff.configuration.index'))
        ->post(route('staff.configuration.exceptions.store'), [
            'starts_at' => '2026-11-28T10:00',
            'ends_at' => '2026-11-28T12:00',
            'kind' => 'holiday',
        ])
        ->assertSessionHasErrors(['schedule']);

    expect(ScheduleException::query()->count())->toBe(0)
        ->and($appointment->refresh()->status)->toBe(AppointmentStatus::Confirmed);
});

it('stores schedule exception instants in UTC from Tehran local time', function (): void {
    $admin = configurationStaff('admin');

    $this->actingAs($admin, 'staff')
        ->post(route('staff.configuration.exceptions.store'), [
            'starts_at' => '2026-11-28T10:00',
            'ends_at' => '2026-11-28T11:00',
            'kind' => 'special_opening',
        ])
        ->assertRedirect();

    $exception = ScheduleException::query()->firstOrFail();
    expect($exception->starts_at->toIso8601String())
        ->toBe(Carbon::parse('2026-11-28 10:00', 'Asia/Tehran')->utc()->toIso8601String());
});
