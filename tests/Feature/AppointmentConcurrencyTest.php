<?php

declare(strict_types=1);

use App\ClinicWorkingHour;
use App\Domain\Booking\AppointmentCreator;
use App\Enums\AppointmentStatus;
use App\Practitioner;
use App\Resource;
use App\Service;
use App\WorkingHour;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

uses(DatabaseMigrations::class);

function concurrencyFixture(): array
{
    $service = Service::factory()->create([
        'duration_minutes' => 30,
        'buffer_before_minutes' => 10,
        'buffer_after_minutes' => 10,
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
    WorkingHour::query()->create([
        'practitioner_id' => $practitioner->getKey(),
        'weekday' => 4,
        'start_local' => '12:00:00',
        'end_local' => '20:00:00',
        'version' => 1,
    ]);

    return [$service, $practitioner, $resource];
}

it('creates exactly one appointment across 50 simultaneous PostgreSQL attempts', function () {
    if (!extension_loaded('pcntl')) {
        $this->markTestSkipped('pcntl is required for the PostgreSQL concurrency proof.');
    }

    Carbon::setTestNow('2026-10-27T08:00:00Z');
    [$service, $practitioner, $resource] = concurrencyFixture();

    $payload = [
        'patient_name' => 'Synthetic Concurrent Booker',
        'mobile' => '09120000001',
        'service_id' => $service->getKey(),
        'practitioner_id' => $practitioner->getKey(),
        'resource_id' => $resource->getKey(),
        'start_at' => '2026-10-29T09:30:00Z',
    ];
    $encoded = base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    $worker = base_path('tests/Support/AppointmentBookingWorker.php');
    $processes = [];

    for ($i = 1; $i <= 50; $i++) {
        $process = new Process([
            PHP_BINARY,
            $worker,
            $encoded,
            sprintf('idem-task14-concurrency-%02d', $i),
            '2026-10-27T08:00:00Z',
        ], base_path(), null, null, 60);
        $process->start();
        $processes[] = $process;
    }

    $results = [];
    foreach ($processes as $process) {
        $process->wait();
        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
        $results[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    }

    expect(collect($results)->where('status', 201)->count())->toBe(1)
        ->and(collect($results)->where('status', 409)->count())->toBe(49)
        ->and(DB::table('appointments')->count())->toBe(1)
        ->and(DB::table('idempotency_records')->count())->toBe(50)
        ->and(DB::table('appointments')->where('status', AppointmentStatus::Pending->value)->count())->toBe(1);
});
