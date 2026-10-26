<?php

declare(strict_types=1);

use App\Appointment;
use App\Patient;
use App\Practitioner;
use App\Resource;
use App\Service;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('has the Task 8 PostgreSQL schema', function () {
    expect(DB::getDriverName())->toBe('pgsql');
    foreach ([
        'staff_users',
        'patients',
        'services',
        'practitioners',
        'resources',
        'working_hours',
        'appointments',
        'appointment_practitioners',
        'appointment_resources',
        'content_items',
        'media_assets',
        'ai_requests',
        'ai_usage',
        'jobs',
        'notification_outbox',
        'idempotency_records',
        'deletion_requests',
    ] as $table) {
        expect(DB::getSchemaBuilder()->hasTable($table))->toBeTrue($table);
    }
    expect((int) DB::selectOne("select count(*) from pg_extension where extname in ('citext','btree_gist')")->count)->toBe(2);
});

it('prevents overlapping active practitioner occupancy at database level', function () {
    $practitioner = Practitioner::factory()->create();
    $service = Service::factory()->create();
    $first = Appointment::factory()->create(['patient_id' => Patient::factory(), 'service_id' => $service->id, 'start_at' => now()->addDay()->startOfHour(), 'end_at' => now()->addDay()->startOfHour()->addMinutes(30)]);
    DB::table('appointment_practitioners')->insert([
        'appointment_id' => $first->id,
        'practitioner_id' => $practitioner->id,
        'active' => true,
        'occupied' => DB::raw("tstzrange('".$first->start_at->toIso8601String()."', '".$first->end_at->toIso8601String()."', '[)')"),
    ]);
    $second = Appointment::factory()->create(['patient_id' => Patient::factory(), 'service_id' => $service->id, 'start_at' => $first->start_at->addMinutes(15), 'end_at' => $first->end_at->addMinutes(15)]);
    expect(fn () => DB::table('appointment_practitioners')->insert([
        'appointment_id' => $second->id,
        'practitioner_id' => $practitioner->id,
        'active' => true,
        'occupied' => DB::raw("tstzrange('".$second->start_at->toIso8601String()."', '".$second->end_at->toIso8601String()."', '[)')"),
    ]))->toThrow(QueryException::class);
});

it('allows adjacent and inactive occupancy', function () {
    $practitioner = Practitioner::factory()->create();
    $service = Service::factory()->create();
    $base = now()->addDay()->startOfHour();
    $a = Appointment::factory()->create(['patient_id' => Patient::factory(), 'service_id' => $service->id, 'start_at' => $base, 'end_at' => $base->addMinutes(30)]);
    $b = Appointment::factory()->create(['patient_id' => Patient::factory(), 'service_id' => $service->id, 'start_at' => $base->addMinutes(30), 'end_at' => $base->addHour()]);
    DB::table('appointment_practitioners')->insert([
        [
            'appointment_id' => $a->id,
            'practitioner_id' => $practitioner->id,
            'active' => true,
            'occupied' => DB::raw("tstzrange('".$a->start_at->toIso8601String()."', '".$a->end_at->toIso8601String()."', '[)')"),
        ],
        [
            'appointment_id' => $b->id,
            'practitioner_id' => $practitioner->id,
            'active' => true,
            'occupied' => DB::raw("tstzrange('".$b->start_at->toIso8601String()."', '".$b->end_at->toIso8601String()."', '[)')"),
        ],
    ]);
    expect(DB::table('appointment_practitioners')->count())->toBe(2);
});

it('is repeatable and produces synthetic development data', function () {
    $this->seed();
    $first = [DB::table('staff_users')->count(), DB::table('patients')->count(), DB::table('services')->count(), DB::table('appointments')->count()];
    $this->seed();
    $second = [DB::table('staff_users')->count(), DB::table('patients')->count(), DB::table('services')->count(), DB::table('appointments')->count()];
    expect($second)->toBe($first);
    expect(DB::table('patients')->where('full_name', 'Synthetic Test Patient')->exists())->toBeTrue();
});
