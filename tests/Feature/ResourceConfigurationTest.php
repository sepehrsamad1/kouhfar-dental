<?php

declare(strict_types=1);

use App\Appointment;
use App\AuditLog;
use App\Enums\AppointmentStatus;
use App\Http\Middleware\EnsureStaffSessionIsFresh;
use App\Resource;
use App\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(EnsureStaffSessionIsFresh::class);

    $this->admin = StaffUser::factory()->active()->create([
        'role' => 'admin',
    ]);
});

it('creates a resource as inactive and records the audit log', function (): void {
    $this->actingAs($this->admin, 'staff')
        ->post(route('staff.configuration.resources.store'), [
            'name_fa' => 'Test resource',
            'kind' => 'dental_unit',
        ])
        ->assertRedirect();

    $resource = Resource::query()
        ->where('name_fa', 'Test resource')
        ->firstOrFail();

    expect($resource->is_active)->toBeFalse()
        ->and(
            AuditLog::query()
                ->where('action', 'configuration.resource_created')
                ->where('target_id', $resource->getKey())
                ->exists()
        )->toBeTrue();
});

it('updates a resource and records the audit log', function (): void {
    $resource = Resource::factory()->create([
        'name_fa' => 'Old resource',
        'kind' => 'dental_unit',
        'is_active' => false,
        'version' => 1,
    ]);

    $this->actingAs($this->admin, 'staff')
        ->patch(route('staff.configuration.resources.update', $resource), [
            'version' => 1,
            'name_fa' => 'Updated resource',
            'kind' => 'consultation_room',
            'is_active' => true,
        ])
        ->assertRedirect();

    $resource->refresh();

    expect($resource->name_fa)->toBe('Updated resource')
        ->and($resource->kind)->toBe('consultation_room')
        ->and($resource->is_active)->toBeTrue()
        ->and($resource->version)->toBe(2)
        ->and(
            AuditLog::query()
                ->where('action', 'configuration.resource_updated')
                ->where('target_id', $resource->getKey())
                ->exists()
        )->toBeTrue();
});

it('rejects a stale resource version without changing the resource', function (): void {
    $resource = Resource::factory()->create([
        'name_fa' => 'Original resource',
        'kind' => 'dental_unit',
        'is_active' => false,
        'version' => 2,
    ]);

    $this->actingAs($this->admin, 'staff')
        ->from(route('staff.configuration.index'))
        ->patch(route('staff.configuration.resources.update', $resource), [
            'version' => 1,
            'name_fa' => 'Should not be saved',
            'kind' => 'dental_unit',
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['version']);

    expect($resource->fresh()->name_fa)->toBe('Original resource')
        ->and($resource->fresh()->version)->toBe(2);
});

it('prevents deactivating a resource used by a future confirmed appointment', function (): void {
    $resource = Resource::factory()->create([
        'is_active' => true,
        'version' => 1,
    ]);

    $start = now('UTC')->addDays(10)->startOfHour();
    $end = $start->copy()->addHour();

    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Confirmed,
        'start_at' => $start,
        'end_at' => $end,
    ]);

    $occupied = '['.$start->toIso8601String().','.$end->toIso8601String().')';

    $appointment->resources()->attach($resource->getKey(), [
        'active' => true,
        'occupied' => $occupied,
    ]);

    $this->actingAs($this->admin, 'staff')
        ->from(route('staff.configuration.index'))
        ->patch(route('staff.configuration.resources.update', $resource), [
            'version' => 1,
            'name_fa' => $resource->name_fa,
            'kind' => $resource->kind,
            'is_active' => false,
        ])
        ->assertSessionHasErrors(['resource']);

    expect($resource->fresh()->is_active)->toBeTrue()
        ->and($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);
});

it('prevents deactivating a resource used by an unexpired pending appointment', function (): void {
    $resource = Resource::factory()->create([
        'is_active' => true,
        'version' => 1,
    ]);

    $start = now('UTC')->addDays(10)->startOfHour();
    $end = $start->copy()->addHour();

    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Pending,
        'start_at' => $start,
        'end_at' => $end,
        'expires_at' => now('UTC')->addMinutes(15),
    ]);

    $occupied = '['.$start->toIso8601String().','.$end->toIso8601String().')';

    $appointment->resources()->attach($resource->getKey(), [
        'active' => true,
        'occupied' => $occupied,
    ]);

    $this->actingAs($this->admin, 'staff')
        ->from(route('staff.configuration.index'))
        ->patch(route('staff.configuration.resources.update', $resource), [
            'version' => 1,
            'name_fa' => $resource->name_fa,
            'kind' => $resource->kind,
            'is_active' => false,
        ])
        ->assertSessionHasErrors(['resource']);

    expect($resource->fresh()->is_active)->toBeTrue()
        ->and($appointment->fresh()->status)->toBe(AppointmentStatus::Pending);
});

it('allows deactivating a resource when its pending appointment hold has expired', function (): void {
    $resource = Resource::factory()->create([
        'is_active' => true,
        'version' => 1,
    ]);

    $start = now('UTC')->addDays(10)->startOfHour();
    $end = $start->copy()->addHour();

    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Pending,
        'start_at' => $start,
        'end_at' => $end,
        'expires_at' => now('UTC')->subMinute(),
    ]);

    $occupied = '['.$start->toIso8601String().','.$end->toIso8601String().')';

    $appointment->resources()->attach($resource->getKey(), [
        'active' => true,
        'occupied' => $occupied,
    ]);

    $this->actingAs($this->admin, 'staff')
        ->patch(route('staff.configuration.resources.update', $resource), [
            'version' => 1,
            'name_fa' => $resource->name_fa,
            'kind' => $resource->kind,
            'is_active' => false,
        ])
        ->assertRedirect();

    expect($resource->fresh()->is_active)->toBeFalse()
        ->and($resource->fresh()->version)->toBe(2)
        ->and($appointment->fresh()->status)->toBe(AppointmentStatus::Pending);
});
