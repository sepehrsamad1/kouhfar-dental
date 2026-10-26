<?php

declare(strict_types=1);

use App\AIRequest;
use App\Appointment;
use App\AuditLog;
use App\ContentItem;
use App\MediaAsset;
use App\Patient;
use App\Practitioner;
use App\StaffUser;
use App\Support\AuditLogger;
use Illuminate\Database\Query\Expression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

function makeAuthorizedStaff(string $role, array $attributes = []): StaffUser
{
    return StaffUser::factory()->active()->create(array_merge(['role' => $role], $attributes));
}

function assignPractitioner(Appointment $appointment, Practitioner $practitioner): void
{
    $start = $appointment->start_at->toIso8601String();
    $end = $appointment->end_at->toIso8601String();

    DB::table('appointment_practitioners')->insert([
        'appointment_id' => $appointment->id,
        'practitioner_id' => $practitioner->id,
        'active' => true,
        'occupied' => new Expression("tstzrange('{$start}', '{$end}', '[)')"),
    ]);
}

it('enforces patient access and practitioner scope', function (): void {
    $admin = makeAuthorizedStaff('admin');
    $reception = makeAuthorizedStaff('reception');
    $practitioner = makeAuthorizedStaff('practitioner');
    $other = makeAuthorizedStaff('practitioner');
    $practitionerRecord = Practitioner::factory()->create(['staff_user_id' => $practitioner->id]);
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);
    assignPractitioner($appointment, $practitionerRecord);
    $unrelated = Patient::factory()->create();

    expect(Gate::forUser($admin)->allows('view', $patient))->toBeTrue()
        ->and(Gate::forUser($reception)->allows('update', $patient))->toBeTrue()
        ->and(Gate::forUser($practitioner)->allows('view', $patient))->toBeTrue()
        ->and(Gate::forUser($other)->allows('view', $patient))->toBeFalse()
        ->and(Gate::forUser($practitioner)->allows('view', $unrelated))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('archive', $patient))->toBeTrue()
        ->and(Gate::forUser($reception)->allows('archive', $patient))->toBeFalse();
});

it('enforces appointment scope and mutation permissions', function (): void {
    $admin = makeAuthorizedStaff('admin');
    $reception = makeAuthorizedStaff('reception');
    $practitioner = makeAuthorizedStaff('practitioner');
    $other = makeAuthorizedStaff('practitioner');
    $practitionerRecord = Practitioner::factory()->create(['staff_user_id' => $practitioner->id]);
    $appointment = Appointment::factory()->create();
    assignPractitioner($appointment, $practitionerRecord);

    expect(Gate::forUser($admin)->allows('update', $appointment))->toBeTrue()
        ->and(Gate::forUser($reception)->allows('update', $appointment))->toBeTrue()
        ->and(Gate::forUser($practitioner)->allows('view', $appointment))->toBeTrue()
        ->and(Gate::forUser($other)->allows('view', $appointment))->toBeFalse()
        ->and(Gate::forUser($practitioner)->allows('complete', $appointment))->toBeTrue()
        ->and(Gate::forUser($practitioner)->allows('cancel', $appointment))->toBeFalse();
});

it('denies sensitive resources and configuration to non-admin roles', function (): void {
    $admin = makeAuthorizedStaff('admin');
    $reception = makeAuthorizedStaff('reception');
    $practitioner = makeAuthorizedStaff('practitioner');
    $content = ContentItem::factory()->create();
    $media = MediaAsset::factory()->create();
    $ai = AIRequest::factory()->create();

    foreach ([$reception, $practitioner] as $user) {
        expect(Gate::forUser($user)->allows('update', $content))->toBeFalse()
            ->and(Gate::forUser($user)->allows('view', $media))->toBeFalse()
            ->and(Gate::forUser($user)->allows('view', $ai))->toBeFalse()
            ->and(Gate::forUser($user)->allows('manage-configuration'))->toBeFalse()
            ->and(Gate::forUser($user)->allows('view-audit-log'))->toBeFalse();
    }

    expect(Gate::forUser($admin)->allows('update', $content))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $media))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $ai))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manage-configuration'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view-audit-log'))->toBeTrue();
});

it('denies a direct protected endpoint to reception', function (): void {
    $reception = makeAuthorizedStaff('reception');

    $this->withoutMiddleware('staff.session.fresh');

    $this->actingAs($reception, 'staff')
        ->get('/staff/admin/audit')
        ->assertForbidden();
});

it('allows the protected audit endpoint only to administrators', function (): void {
    $admin = makeAuthorizedStaff('admin');

    $this->withoutMiddleware('staff.session.fresh');

    $this->actingAs($admin, 'staff')
        ->get('/staff/admin/audit')
        ->assertNoContent();
});

it('writes only allowlisted audit metadata and excludes patient details', function (): void {
    $admin = makeAuthorizedStaff('admin');
    $patient = Patient::factory()->create();
    $log = AuditLogger::record(
        $admin,
        'patient.updated',
        $patient,
        ['status' => 'active', 'full_name' => $patient->full_name],
        ['status' => 'archived', 'mobile_normalized' => $patient->mobile_normalized],
    );

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->target_type)->toBe('Patient')
        ->and($log->target_id)->toBe($patient->id)
        ->and($log->meta)->toBe([
            'before' => ['status' => 'active'],
            'after' => ['status' => 'archived'],
        ])
        ->and(AuditLog::query()->whereKey($log->id)->exists())->toBeTrue();
});
