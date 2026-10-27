<?php

declare(strict_types=1);

use App\ClinicWorkingHour;
use App\Domain\Availability\AvailabilityRepository;
use App\Practitioner;
use App\Resource;
use App\ScheduleBreak;
use App\ScheduleException;
use App\Service;
use App\WorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('returns no-store availability and no slots for a service not approved for public booking', function (): void {
    $service = Service::factory()->create([
        'is_active' => false,
        'is_public_bookable' => false,
    ]);

    $this->get(route('booking.availability', $service))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('timezone', 'Asia/Tehran')
        ->assertJsonPath('slots', []);
});

it('includes manager-configured special openings and lets closure exceptions take precedence', function (): void {
    $service = Service::factory()->create([
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'required_resource_kind' => 'dental_unit',
        'is_active' => true,
        'is_public_bookable' => true,
    ]);
    $practitioner = Practitioner::factory()->create(['is_active' => true]);
    $resource = Resource::factory()->create(['kind' => 'dental_unit', 'is_active' => true]);
    $service->practitioners()->attach($practitioner->getKey());
    $service->resources()->attach($resource->getKey());

    ClinicWorkingHour::query()->create([
        'weekday' => 3,
        'start_local' => '09:00',
        'end_local' => '17:00',
        'timezone' => 'Asia/Tehran',
    ]);
    WorkingHour::query()->create([
        'practitioner_id' => $practitioner->getKey(),
        'weekday' => 3,
        'start_local' => '09:00',
        'end_local' => '17:00',
    ]);
    $openingStart = new DateTimeImmutable('2026-10-28 17:00:00', new DateTimeZone('Asia/Tehran'));
    $openingEnd = new DateTimeImmutable('2026-10-28 18:00:00', new DateTimeZone('Asia/Tehran'));
    ScheduleException::query()->create([
        'practitioner_id' => null,
        'starts_at' => $openingStart,
        'ends_at' => $openingEnd,
        'kind' => 'special_opening',
    ]);

    $repository = app(AvailabilityRepository::class);
    $now = new DateTimeImmutable('2026-10-27T06:00:00Z');
    $openedSlots = $repository->forPublicService($service, $now);
    expect(array_column($openedSlots, 'display_local'))->toContain('2026-10-28 17:00');

    ScheduleException::query()->create([
        'practitioner_id' => null,
        'starts_at' => new DateTimeImmutable('2026-10-28 17:15:00', new DateTimeZone('Asia/Tehran')),
        'ends_at' => new DateTimeImmutable('2026-10-28 17:30:00', new DateTimeZone('Asia/Tehran')),
        'kind' => 'closed',
    ]);
    $closedSlots = $repository->forPublicService($service, $now);
    expect(array_column($closedSlots, 'display_local'))->not->toContain('2026-10-28 17:00');
});

it('subtracts recurring breaks from otherwise valid working periods', function (): void {
    $service = Service::factory()->create([
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'required_resource_kind' => 'dental_unit',
        'is_active' => true,
        'is_public_bookable' => true,
    ]);
    $practitioner = Practitioner::factory()->create(['is_active' => true]);
    $resource = Resource::factory()->create(['kind' => 'dental_unit', 'is_active' => true]);
    $service->practitioners()->attach($practitioner->getKey());
    $service->resources()->attach($resource->getKey());

    ClinicWorkingHour::query()->create([
        'weekday' => 3,
        'start_local' => '09:00',
        'end_local' => '17:00',
        'timezone' => 'Asia/Tehran',
    ]);
    WorkingHour::query()->create([
        'practitioner_id' => $practitioner->getKey(),
        'weekday' => 3,
        'start_local' => '09:00',
        'end_local' => '17:00',
    ]);
    ScheduleBreak::query()->create([
        'practitioner_id' => null,
        'weekday' => 3,
        'start_local' => '12:00',
        'end_local' => '13:00',
    ]);

    $slots = app(AvailabilityRepository::class)->forPublicService(
        $service,
        new DateTimeImmutable('2026-10-27T06:00:00Z')
    );

    $displayTimes = array_column($slots, 'display_local');
    expect($displayTimes)->toContain('2026-10-28 11:30')
        ->and($displayTimes)->not->toContain('2026-10-28 12:00')
        ->and($displayTimes)->not->toContain('2026-10-28 12:30')
        ->and($displayTimes)->toContain('2026-10-28 13:00');
});

it('benchmarks the database-backed availability repository with 1000 relevant appointments', function (): void {
    $timezone = new DateTimeZone('Asia/Tehran');
    $service = Service::factory()->create([
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'required_resource_kind' => 'dental_unit',
        'is_active' => true,
        'is_public_bookable' => true,
    ]);
    $otherServices = Service::factory()->count(29)->create([
        'duration_minutes' => 15,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'is_active' => true,
        'is_public_bookable' => true,
    ]);
    $practitioners = Practitioner::factory()->count(10)->create(['is_active' => true]);
    $resources = Resource::factory()->count(3)->create([
        'kind' => 'dental_unit',
        'is_active' => true,
    ]);
    $service->practitioners()->attach($practitioners->modelKeys());
    $service->resources()->attach($resources->modelKeys());

    foreach ([1, 2, 3, 4, 6, 7] as $weekday) {
        ClinicWorkingHour::query()->create([
            'weekday' => $weekday,
            'start_local' => '09:00',
            'end_local' => '17:00',
            'timezone' => 'Asia/Tehran',
        ]);
    }
    foreach ($practitioners as $practitioner) {
        foreach ([1, 2, 3, 4, 6, 7] as $weekday) {
            WorkingHour::query()->create([
                'practitioner_id' => $practitioner->getKey(),
                'weekday' => $weekday,
                'start_local' => '09:00',
                'end_local' => '17:00',
            ]);
        }
    }

    $patientId = (string) Str::uuid();
    DB::table('patients')->insert([
        'id' => $patientId,
        'full_name' => 'Benchmark patient',
        'mobile_normalized' => '099'.substr((string) random_int(10000000, 99999999), 0, 8),
        'created_at' => now('UTC'),
        'updated_at' => now('UTC'),
    ]);

    $serviceIds = $otherServices->modelKeys();
    $practitionerIds = $practitioners->modelKeys();
    $resourceIds = $resources->modelKeys();
    $workingDates = [];
    $firstDate = new DateTimeImmutable('2026-10-28 00:00:00', $timezone);
    for ($day = 0; $day < 14; $day++) {
        $date = $firstDate->modify(sprintf('+%d days', $day));
        if ((int) $date->format('N') !== 5) {
            $workingDates[] = $date;
        }
    }

    $appointmentRows = [];
    $practitionerPivots = [];
    $resourcePivots = [];
    for ($i = 0; $i < 1000; $i++) {
        $withinDay = $i % 96;
        $date = $workingDates[intdiv($i, 96)];
        $start = $date->setTime(9, 0)->modify(sprintf('+%d minutes', intdiv($withinDay, 3) * 15));
        $end = $start->modify('+15 minutes');
        $startUtc = $start->setTimezone(new DateTimeZone('UTC'));
        $endUtc = $end->setTimezone(new DateTimeZone('UTC'));
        $appointmentId = (string) Str::uuid();
        $practitionerId = $practitionerIds[$i % count($practitionerIds)];
        $resourceId = $resourceIds[$i % count($resourceIds)];

        $appointmentRows[] = [
            'id' => $appointmentId,
            'patient_id' => $patientId,
            'service_id' => $serviceIds[$i % count($serviceIds)],
            'status' => 'confirmed',
            'start_at' => $startUtc->format('Y-m-d H:i:sP'),
            'end_at' => $endUtc->format('Y-m-d H:i:sP'),
            'source' => 'staff',
            'expires_at' => null,
            'created_by' => null,
            'version' => 1,
            'created_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ];
        $practitionerPivots[] = [$appointmentId, $practitionerId, $startUtc->format('Y-m-d H:i:sP'), $endUtc->format('Y-m-d H:i:sP')];
        $resourcePivots[] = [$appointmentId, $resourceId, $startUtc->format('Y-m-d H:i:sP'), $endUtc->format('Y-m-d H:i:sP')];
    }

    foreach (array_chunk($appointmentRows, 100) as $chunk) {
        DB::table('appointments')->insert($chunk);
    }
    foreach (array_chunk($practitionerPivots, 100) as $chunk) {
        $values = [];
        $bindings = [];
        foreach ($chunk as [$appointmentId, $practitionerId, $startAt, $endAt]) {
            $values[] = "(?, ?, true, tstzrange(?::timestamptz, ?::timestamptz, '[)'))";
            array_push($bindings, $appointmentId, $practitionerId, $startAt, $endAt);
        }
        DB::insert('INSERT INTO appointment_practitioners (appointment_id, practitioner_id, active, occupied) VALUES '.implode(', ', $values), $bindings);
    }
    foreach (array_chunk($resourcePivots, 100) as $chunk) {
        $values = [];
        $bindings = [];
        foreach ($chunk as [$appointmentId, $resourceId, $startAt, $endAt]) {
            $values[] = "(?, ?, true, tstzrange(?::timestamptz, ?::timestamptz, '[)'))";
            array_push($bindings, $appointmentId, $resourceId, $startAt, $endAt);
        }
        DB::insert('INSERT INTO appointment_resources (appointment_id, resource_id, active, occupied) VALUES '.implode(', ', $values), $bindings);
    }

    $repository = app(AvailabilityRepository::class);
    $now = new DateTimeImmutable('2026-10-27T06:00:00Z');
    $repository->forPublicService($service, $now);
    $durationsMs = [];
    $slots = [];
    for ($sample = 0; $sample < 5; $sample++) {
        $startedAt = hrtime(true);
        $slots = $repository->forPublicService($service, $now);
        $durationsMs[] = (hrtime(true) - $startedAt) / 1_000_000;
    }
    sort($durationsMs);
    $p95 = $durationsMs[(int) ceil(count($durationsMs) * 0.95) - 1];
    $p99 = $durationsMs[(int) ceil(count($durationsMs) * 0.99) - 1];

    fwrite(STDERR, sprintf(
        'Availability repository benchmark: 5 samples, 1000 relevant appointments, 10 practitioners, 3 resources, 30 services, 14 days; p95=%.2fms p99=%.2fms (CI PostgreSQL runner).%s',
        $p95,
        $p99,
        PHP_EOL
    ));

    expect($slots)->not->toBeEmpty();
});
