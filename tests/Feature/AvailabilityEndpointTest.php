<?php

declare(strict_types=1);

use App\ClinicWorkingHour;
use App\Practitioner;
use App\Resource;
use App\ScheduleException;
use App\Service;
use App\WorkingHour;
use App\Domain\Availability\AvailabilityRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
