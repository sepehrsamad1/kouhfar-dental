<?php

declare(strict_types=1);

use App\Appointment;
use App\Enums\AppointmentStatus;
use App\Patient;
use App\Practitioner;
use App\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the approved core tables and excludes the deferred OTP table', function (): void {
    foreach ([
        'staff_users', 'patients', 'patient_match_flags', 'services', 'practitioners',
        'practitioner_services', 'resources', 'service_resources', 'working_hours',
        'schedule_exceptions', 'appointments', 'appointment_practitioners',
        'appointment_resources', 'appointment_events', 'follow_up_tokens', 'content_items',
        'media_assets', 'consent_records', 'ai_requests', 'ai_usages', 'sessions',
        'login_attempts', 'audit_logs', 'jobs', 'notification_outbox',
        'idempotency_records', 'deletion_requests',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    expect(Schema::hasTable('verification_challenges'))->toBeFalse();
});

it('rejects appointment intervals with an end that is not after the start', function (): void {
    $patient = Patient::factory()->create();
    $service = Service::factory()->create();
    $start = now('UTC');

    expect(fn () => DB::table('appointments')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'status' => 'confirmed',
        'start_at' => $start,
        'end_at' => $start->copy()->subMinute(),
        'source' => 'staff',
        'expires_at' => null,
        'created_by' => null,
        'version' => 1,
        'created_at' => $start,
        'updated_at' => $start,
    ]))->toThrow(QueryException::class);
});

it('rejects pending appointments without an expiry instant', function (): void {
    $patient = Patient::factory()->create();
    $service = Service::factory()->create();
    $start = now('UTC')->addDay();

    expect(fn () => DB::table('appointments')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'patient_id' => $patient->id,
        'service_id' => $service->id,
        'status' => 'pending',
        'start_at' => $start,
        'end_at' => $start->copy()->addHour(),
        'source' => 'public',
        'expires_at' => null,
        'created_by' => null,
        'version' => 1,
        'created_at' => $start,
        'updated_at' => $start,
    ]))->toThrow(QueryException::class);
});

it('prevents overlapping active practitioner occupancy in PostgreSQL', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('PostgreSQL range exclusion constraints require PostgreSQL.');
    }

    $practitioner = Practitioner::factory()->create();
    $appointments = Appointment::factory()->count(2)->create([
        'status' => AppointmentStatus::Confirmed,
    ]);
    $occupied = '["2027-01-01 09:00:00+00","2027-01-01 10:00:00+00")';

    DB::table('appointment_practitioners')->insert([
        'appointment_id' => $appointments[0]->id,
        'practitioner_id' => $practitioner->id,
        'occupied' => $occupied,
        'active' => true,
    ]);

    expect(fn () => DB::table('appointment_practitioners')->insert([
        'appointment_id' => $appointments[1]->id,
        'practitioner_id' => $practitioner->id,
        'occupied' => $occupied,
        'active' => true,
    ]))->toThrow(QueryException::class);
});

it('prevents overlapping active resource occupancy in PostgreSQL', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('PostgreSQL range exclusion constraints require PostgreSQL.');
    }

    $resource = \App\Resource::factory()->create();
    $appointments = Appointment::factory()->count(2)->create([
        'status' => AppointmentStatus::Confirmed,
    ]);
    $occupied = '["2027-01-01 09:00:00+00","2027-01-01 10:00:00+00")';

    DB::table('appointment_resources')->insert([
        'appointment_id' => $appointments[0]->id,
        'resource_id' => $resource->id,
        'occupied' => $occupied,
        'active' => true,
    ]);

    expect(fn () => DB::table('appointment_resources')->insert([
        'appointment_id' => $appointments[1]->id,
        'resource_id' => $resource->id,
        'occupied' => $occupied,
        'active' => true,
    ]))->toThrow(QueryException::class);
});

it('seeds only synthetic inactive fixtures and is repeatable', function (): void {
    $this->seed(DatabaseSeeder::class);
    $firstCounts = [
        'staff_users' => DB::table('staff_users')->count(),
        'patients' => DB::table('patients')->count(),
        'services' => DB::table('services')->count(),
        'practitioners' => DB::table('practitioners')->count(),
        'resources' => DB::table('resources')->count(),
        'working_hours' => DB::table('working_hours')->count(),
        'appointments' => DB::table('appointments')->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect(DB::table('staff_users')->count())->toBe($firstCounts['staff_users'])
        ->and(DB::table('patients')->count())->toBe($firstCounts['patients'])
        ->and(DB::table('services')->count())->toBe($firstCounts['services'])
        ->and(DB::table('practitioners')->count())->toBe($firstCounts['practitioners'])
        ->and(DB::table('resources')->count())->toBe($firstCounts['resources'])
        ->and(DB::table('working_hours')->count())->toBe($firstCounts['working_hours'])
        ->and(DB::table('appointments')->count())->toBe($firstCounts['appointments'])
        ->and(DB::table('staff_users')->where('id', '00000000-0000-4000-8000-000000000001')->value('is_active'))->toBeFalse()
        ->and(DB::table('services')->where('id', '00000000-0000-4000-8000-000000000003')->value('is_public_bookable'))->toBeFalse()
        ->and(DB::table('appointments')->where('id', '00000000-0000-4000-8000-000000000007')->value('status'))->toBe('completed');
});

it('refuses to run synthetic seeders in production', function (): void {
    $this->app['env'] = 'production';

    expect(fn () => app(DatabaseSeeder::class)->run())->toThrow(LogicException::class);
});
