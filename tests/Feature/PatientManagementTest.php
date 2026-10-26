<?php

declare(strict_types=1);

use App\Appointment;
use App\Http\Middleware\EnsureStaffSessionIsFresh;
use App\AuditLog;
use App\Patient;
use App\Practitioner;
use App\StaffUser;
use App\Support\IranianPhoneNumber;
use Illuminate\Database\Query\Expression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(EnsureStaffSessionIsFresh::class);
});

function makePatientManagerStaff(string $role): StaffUser
{
    return StaffUser::factory()->active()->create(['role' => $role]);
}

function attachPatientPractitioner(Appointment $appointment, Practitioner $practitioner): void
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

it('normalizes Iranian phone input to one canonical form', function (string $input, string $expected): void {
    expect(IranianPhoneNumber::normalize($input))->toBe($expected);
})->with([
    ['09121234567', '09121234567'],
    ['۰۹۱۲۱۲۳۴۵۶۷', '09121234567'],
    ['٠٩١٢١٢٣٤٥٦٧', '09121234567'],
    ['+98 912 123 4567', '09121234567'],
    ['0098-912-123-4567', '09121234567'],
    ['98(912)1234567', '09121234567'],
]);

it('rejects invalid Iranian mobile numbers', function (string $input): void {
    expect(fn () => IranianPhoneNumber::normalize($input))
        ->toThrow(InvalidArgumentException::class);
})->with([
    '0912123456',
    '08121234567',
    '+1 212 123 4567',
    '091212345678',
]);

it('creates patients only for admin and reception and stores only normalized mobile', function (): void {
    $reception = makePatientManagerStaff('reception');

    $response = $this->actingAs($reception, 'staff')
        ->post(route('staff.patients.store'), [
            'full_name' => 'بیمار آزمایشی',
            'mobile' => '۰۹۱۲-۱۲۳-۴۵۶۷',
        ]);

    $response->assertRedirect(route('staff.patients.index'));

    $patient = Patient::query()->first();
    expect($patient->mobile_normalized)->toBe('09121234567')
        ->and($patient->getAttributes())->not->toHaveKey('mobile');
});

it('returns a duplicate warning and never auto-merges on create', function (): void {
    $reception = makePatientManagerStaff('reception');
    $existing = Patient::factory()->create(['mobile_normalized' => '09121234567']);

    $response = $this->actingAs($reception, 'staff')
        ->postJson(route('staff.patients.store'), [
            'full_name' => 'بیمار دوم',
            'mobile' => '+98 912 123 4567',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('duplicate_warning', true)
        ->assertJsonPath('duplicate_count', 1);

    expect(Patient::query()->count())->toBe(1)
        ->and(Patient::query()->first()->id)->toBe($existing->id);

    $confirmed = $this->actingAs($reception, 'staff')
        ->postJson(route('staff.patients.store'), [
            'full_name' => 'بیمار دوم',
            'mobile' => '+98 912 123 4567',
            'confirm_duplicate' => true,
        ]);

    $confirmed->assertCreated();
    expect(Patient::query()->count())->toBe(2);
});

it('rejects invalid patient input before persistence', function (): void {
    $admin = makePatientManagerStaff('admin');

    $response = $this->actingAs($admin, 'staff')
        ->postJson(route('staff.patients.store'), [
            'full_name' => 'x',
            'mobile' => '123',
        ]);

    $response->assertUnprocessable();
    expect(Patient::query()->count())->toBe(0);
});

it('paginates and scopes patient lookup while masking mobile numbers', function (): void {
    $admin = makePatientManagerStaff('admin');
    Patient::factory()->create(['full_name' => 'Alpha Patient', 'mobile_normalized' => '09121234567']);
    Patient::factory()->count(24)->create();

    $response = $this->actingAs($admin, 'staff')
        ->getJson(route('staff.patients.index', ['q' => 'Alpha', 'per_page' => 20]));

    $response->assertOk()
        ->assertJsonPath('per_page', 20)
        ->assertJsonPath('data.0.full_name', 'Alpha Patient')
        ->assertJsonPath('data.0.mobile', '0912****4567');

    expect($response->json('data.0.mobile'))->not->toBe('09121234567');
});

it('restricts practitioner lookup and updates to their own patients', function (): void {
    $practitionerUser = makePatientManagerStaff('practitioner');
    $otherPractitioner = makePatientManagerStaff('practitioner');
    $practitioner = Practitioner::factory()->create(['staff_user_id' => $practitionerUser->id]);
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);
    attachPatientPractitioner($appointment, $practitioner);

    $unrelated = Patient::factory()->create();

    $list = $this->actingAs($practitionerUser, 'staff')
        ->getJson(route('staff.patients.index'));

    $list->assertOk()->assertJsonCount(1, 'data');

    $denied = $this->actingAs($otherPractitioner, 'staff')
        ->patchJson(route('staff.patients.update', $patient), [
            'full_name' => 'تلاش غیرمجاز',
            'mobile' => '09121234567',
            'version' => $patient->version,
        ]);

    $denied->assertForbidden();
    expect($unrelated->id)->not->toBe($patient->id);
});

it('uses optimistic concurrency and returns 409 without current patient data', function (): void {
    $reception = makePatientManagerStaff('reception');
    $patient = Patient::factory()->create([
        'full_name' => 'نام اولیه',
        'mobile_normalized' => '09121234567',
    ]);

    $response = $this->actingAs($reception, 'staff')
        ->patchJson(route('staff.patients.update', $patient), [
            'full_name' => 'نام جدید',
            'mobile' => '09121234567',
            'version' => $patient->version + 1,
        ]);

    $response->assertStatus(409);
    expect($response->json('message'))->not->toContain('نام اولیه')
        ->and($response->json('message'))->not->toContain('09121234567');

    expect($patient->refresh()->full_name)->toBe('نام اولیه');
});

it('audits create, update, archive and mobile reveal without patient values', function (): void {
    $admin = makePatientManagerStaff('admin');
    $patient = Patient::factory()->create([
        'full_name' => 'بیمار حسابرسی',
        'mobile_normalized' => '09121234567',
    ]);

    $this->actingAs($admin, 'staff')->patchJson(route('staff.patients.update', $patient), [
        'full_name' => 'نام اصلاح‌شده',
        'version' => $patient->version,
    ])->assertOk();

    $this->actingAs($admin, 'staff')->postJson(route('staff.patients.archive', $patient))
        ->assertOk();

    $this->actingAs($admin, 'staff')->get(route('staff.patients.reveal-mobile', $patient))
        ->assertOk();

    expect(AuditLog::query()->where('actor_id', $admin->id)->whereIn('action', [
        'patient.updated',
        'patient.archived',
        'patient.mobile_revealed',
    ])->count())->toBe(3);

    $logs = AuditLog::query()->where('actor_id', $admin->id)->get();
    foreach ($logs as $log) {
        expect(json_encode($log->meta))->not->toContain('09121234567')
            ->and(json_encode($log->meta))->not->toContain('بیمار حسابرسی');
    }
});

it('allows archive only to administrators', function (): void {
    $reception = makePatientManagerStaff('reception');
    $patient = Patient::factory()->create();

    $this->actingAs($reception, 'staff')
        ->postJson(route('staff.patients.archive', $patient))
        ->assertForbidden();

    expect($patient->refresh()->archived_at)->toBeNull();
});
