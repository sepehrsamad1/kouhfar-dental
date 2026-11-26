<?php

declare(strict_types=1);

use App\ClinicWorkingHour;
use App\Domain\Availability\AvailabilityRepository;
use App\Practitioner;
use App\Resource;
use App\ScheduleException;
use App\Service;
use App\WorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies special opening only to its assigned practitioner', function (): void {
    $service = Service::factory()->create([
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'required_resource_kind' => 'dental_unit',
        'is_active' => true,
        'is_public_bookable' => true,
    ]);

    $firstPractitioner = Practitioner::factory()->create(['is_active' => true]);
    $secondPractitioner = Practitioner::factory()->create(['is_active' => true]);
    $resource = Resource::factory()->create([
        'kind' => 'dental_unit',
        'is_active' => true,
    ]);

    $service->practitioners()->attach([
        $firstPractitioner->getKey(),
        $secondPractitioner->getKey(),
    ]);
    $service->resources()->attach($resource->getKey());

    ClinicWorkingHour::query()->create([
        'weekday' => 3,
        'start_local' => '09:00',
        'end_local' => '10:00',
        'timezone' => 'Asia/Tehran',
    ]);

    foreach ([$firstPractitioner, $secondPractitioner] as $practitioner) {
        WorkingHour::query()->create([
            'practitioner_id' => $practitioner->getKey(),
            'weekday' => 3,
            'start_local' => '09:00',
            'end_local' => '10:00',
        ]);
    }

    ScheduleException::query()->create([
        'practitioner_id' => $firstPractitioner->getKey(),
        'starts_at' => new DateTimeImmutable(
            '2026-10-28 10:00:00',
            new DateTimeZone('Asia/Tehran')
        ),
        'ends_at' => new DateTimeImmutable(
            '2026-10-28 11:00:00',
            new DateTimeZone('Asia/Tehran')
        ),
        'kind' => 'special_opening',
    ]);

    $slots = app(AvailabilityRepository::class)->forPublicService(
        $service,
        new DateTimeImmutable('2026-10-27T06:00:00Z')
    );

    $openingSlots = array_filter(
        array_column($slots, 'display_local'),
        fn (string $time): bool => $time === '2026-10-28 10:00'
    );

    expect(count($openingSlots))->toBe(1);
});

it('applies a multi-day closure across every affected working day', function (): void {
    $service = Service::factory()->create([
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'required_resource_kind' => 'dental_unit',
        'is_active' => true,
        'is_public_bookable' => true,
    ]);

    $practitioner = Practitioner::factory()->create(['is_active' => true]);
    $resource = Resource::factory()->create([
        'kind' => 'dental_unit',
        'is_active' => true,
    ]);

    $service->practitioners()->attach($practitioner->getKey());
    $service->resources()->attach($resource->getKey());

    foreach ([3, 4] as $weekday) {
        ClinicWorkingHour::query()->create([
            'weekday' => $weekday,
            'start_local' => '09:00',
            'end_local' => '17:00',
            'timezone' => 'Asia/Tehran',
        ]);

        WorkingHour::query()->create([
            'practitioner_id' => $practitioner->getKey(),
            'weekday' => $weekday,
            'start_local' => '09:00',
            'end_local' => '17:00',
        ]);
    }

    ScheduleException::query()->create([
        'practitioner_id' => null,
        'starts_at' => new DateTimeImmutable(
            '2026-10-28 12:00:00',
            new DateTimeZone('Asia/Tehran')
        ),
        'ends_at' => new DateTimeImmutable(
            '2026-10-29 14:00:00',
            new DateTimeZone('Asia/Tehran')
        ),
        'kind' => 'closed',
    ]);

    $slots = app(AvailabilityRepository::class)->forPublicService(
        $service,
        new DateTimeImmutable('2026-10-27T06:00:00Z')
    );

    $displayTimes = array_column($slots, 'display_local');

    expect($displayTimes)
        ->not->toContain('2026-10-28 12:00')
        ->not->toContain('2026-10-29 09:00')
        ->toContain('2026-10-29 14:00');
});
