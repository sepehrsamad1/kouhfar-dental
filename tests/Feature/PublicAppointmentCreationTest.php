<?php

declare(strict_types=1);

use App\Appointment;
use App\AppointmentEvent;
use App\ClinicWorkingHour;
use App\Enums\AppointmentStatus;
use App\Practitioner;
use App\Resource;
use App\Service;
use App\WorkingHour;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function publicBookingFixture(): array
{
    $service = Service::factory()->create([
        'duration_minutes' => 30,
        'buffer_before_minutes' => 10,
        'buffer_after_minutes' => 10,
        'required_resource_kind' => 'dental_unit',
        'is_active' => true,
        'is_public_bookable' => true,
    ]);
    $practitioner = Practitioner::factory()->create(['is_active' => true]);
    $resource = Resource::factory()->create(['kind' => 'dental_unit', 'is_active' => true]);
    $service->practitioners()->attach($practitioner);
    $service->resources()->attach($resource);

    foreach ([3, 4, 6, 7] as $weekday) {
        ClinicWorkingHour::query()->create([
            'weekday' => $weekday,
            'start_local' => '09:00',
            'end_local' => '17:00',
            'timezone' => 'Asia/Tehran',
        ]);
        WorkingHour::query()->create([
            'practitioner_id' => $practitioner->id,
            'weekday' => $weekday,
            'start_local' => '09:00',
            'end_local' => '17:00',
        ]);
    }

    return [$service, $practitioner, $resource];
}

it('creates a pending appointment atomically and records an event and idempotency response', function (): void {
    [$service, $practitioner, $resource] = publicBookingFixture();
    $start = new DateTimeImmutable('2026-10-28 10:00:00', new DateTimeZone('Asia/Tehran'));

    $response = $this->postJson(route('booking.appointments.store'), [
        'service_id' => $service->id,
        'start_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'full_name' => 'بیمار آزمایشی',
        'mobile' => '۰۹۱۲۱۲۳۴۵۶۷',
    ], ['Idempotency-Key' => 'create-1']);

    $response->assertCreated()
        ->assertJsonPath('status', 'pending');

    $appointment = Appointment::query()->first();
    expect($appointment)->not->toBeNull()
        ->and($appointment->status)->toBe(AppointmentStatus::Pending)
        ->and($appointment->start_at->equalTo($start->setTimezone(new DateTimeZone('UTC'))))->toBeTrue()
        ->and($appointment->end_at->equalTo($start->modify('+30 minutes')->setTimezone(new DateTimeZone('UTC'))))->toBeTrue()
        ->and($appointment->expires_at->equalTo($appointment->created_at->addHour()))->toBeTrue();

    expect(DB::table('appointment_practitioners')->where('appointment_id', $appointment->id)->where('active', true)->count())->toBe(1)
        ->and(DB::table('appointment_resources')->where('appointment_id', $appointment->id)->where('active', true)->count())->toBe(1)
        ->and(AppointmentEvent::query()->where('appointment_id', $appointment->id)->where('to_status', 'pending')->count())->toBe(1)
        ->and(DB::table('idempotency_records')->where('key', 'create-1')->count())->toBe(1);
});

it('replays the same idempotent request and rejects a changed body', function (): void {
    [$service] = publicBookingFixture();
    $start = new DateTimeImmutable('2026-10-28 10:00:00', new DateTimeZone('Asia/Tehran'));
    $payload = [
        'service_id' => $service->id,
        'start_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'full_name' => 'بیمار آزمایشی',
        'mobile' => '09121234567',
    ];

    $first = $this->postJson(route('booking.appointments.store'), $payload, ['Idempotency-Key' => 'same-key']);
    $second = $this->postJson(route('booking.appointments.store'), $payload, ['Idempotency-Key' => 'same-key']);
    $changed = $this->postJson(route('booking.appointments.store'), $payload + ['full_name' => 'بیمار دیگر'], ['Idempotency-Key' => 'same-key']);

    $first->assertCreated();
    $second->assertCreated()->assertJson($first->json());
    $changed->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');

    expect(Appointment::query()->count())->toBe(1);
});

it('returns a generic 409 when the selected slot is unavailable', function (): void {
    [$service] = publicBookingFixture();
    $start = new DateTimeImmutable('2026-10-28 10:00:00', new DateTimeZone('Asia/Tehran'));
    $payload = [
        'service_id' => $service->id,
        'start_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'full_name' => 'بیمار آزمایشی',
        'mobile' => '09121234567',
    ];

    $this->postJson(route('booking.appointments.store'), $payload, ['Idempotency-Key' => 'first'])->assertCreated();
    $this->postJson(route('booking.appointments.store'), $payload, ['Idempotency-Key' => 'second'])
        ->assertStatus(409)
        ->assertJson(['code' => 'SLOT_UNAVAILABLE'])
        ->assertJsonMissing(['exception', 'sqlstate']);
    expect(Appointment::query()->count())->toBe(1);
});

it('expires overdue pending holds before reserving a newly available slot', function (): void {
    [$service] = publicBookingFixture();
    $start = new DateTimeImmutable('2026-10-28 10:00:00', new DateTimeZone('Asia/Tehran'));

    $first = $this->postJson(route('booking.appointments.store'), [
        'service_id' => $service->id,
        'start_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'full_name' => 'بیمار اول',
        'mobile' => '09121234567',
    ], ['Idempotency-Key' => 'hold-1']);
    $first->assertCreated();

    $appointment = Appointment::query()->first();
    Appointment::query()->whereKey($appointment->id)->update([
        'expires_at' => now('UTC')->subSecond(),
    ]);

    $second = $this->postJson(route('booking.appointments.store'), [
        'service_id' => $service->id,
        'start_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'full_name' => 'بیمار دوم',
        'mobile' => '09121234568',
    ], ['Idempotency-Key' => 'hold-2']);

    $second->assertCreated();
    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Expired)
        ->and(DB::table('appointment_practitioners')->where('appointment_id', $appointment->id)->value('active'))->toBeFalse()
        ->and(DB::table('appointment_resources')->where('appointment_id', $appointment->id)->value('active'))->toBeFalse();
});

it('controls appointment lifecycle transitions and rejects invalid transitions', function (): void {
    [$service] = publicBookingFixture();
    $start = new DateTimeImmutable('2026-10-28 10:00:00', new DateTimeZone('Asia/Tehran'));
    $this->postJson(route('booking.appointments.store'), [
        'service_id' => $service->id,
        'start_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'full_name' => 'بیمار آزمایشی',
        'mobile' => '09121234567',
    ], ['Idempotency-Key' => 'transition-1'])->assertCreated();

    $appointment = Appointment::query()->first();
    expect(fn () => app(\App\Domain\Booking\AppointmentStateMachine::class)->transition(
        $appointment,
        AppointmentStatus::Completed,
        'system',
    ))->toThrow(LogicException::class);
});

it('rejects invalid idempotency keys before persistence', function (): void {
    [$service] = publicBookingFixture();
    $this->postJson(route('booking.appointments.store'), [
        'service_id' => $service->id,
        'start_at' => 'not-a-date',
        'full_name' => 'بیمار آزمایشی',
        'mobile' => '09121234567',
    ])->assertUnprocessable();

    expect(Appointment::query()->count())->toBe(0);
});

it('enforces buffer occupancy at the database level', function (): void {
    [$service, $practitioner, $resource] = publicBookingFixture();
    $firstStart = new DateTimeImmutable('2026-10-28 10:00:00', new DateTimeZone('Asia/Tehran'));
    $secondStart = $firstStart->modify('+35 minutes');

    $this->postJson(route('booking.appointments.store'), [
        'service_id' => $service->id,
        'start_at' => $firstStart->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'full_name' => 'بیمار اول',
        'mobile' => '09121234567',
    ], ['Idempotency-Key' => 'buffer-1'])->assertCreated();

    $response = $this->postJson(route('booking.appointments.store'), [
        'service_id' => $service->id,
        'start_at' => $secondStart->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'full_name' => 'بیمار دوم',
        'mobile' => '09121234568',
    ], ['Idempotency-Key' => 'buffer-2']);

    $response->assertStatus(409)->assertJson(['code' => 'SLOT_UNAVAILABLE']);
    expect(DB::table('appointment_practitioners')->where('practitioner_id', $practitioner->id)->where('active', true)->count())->toBe(1)
        ->and(DB::table('appointment_resources')->where('resource_id', $resource->id)->where('active', true)->count())->toBe(1);
});

it('runs the required 50-way PostgreSQL concurrency test when process forking is available', function (): void {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('PCNTL is required for the 50-way PostgreSQL concurrency proof.');
    }

    [$service, $practitioner, $resource] = publicBookingFixture();
    $start = new DateTimeImmutable('2026-10-28 10:00:00', new DateTimeZone('Asia/Tehran'));
    $payload = [
        'service_id' => $service->id,
        'start_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
        'full_name' => 'همزمانی آزمایشی',
        'mobile' => '09121234567',
    ];

    $barrier = tempnam(sys_get_temp_dir(), 'booking-barrier-');
    $results = tempnam(sys_get_temp_dir(), 'booking-results-');
    file_put_contents($results, '');
    $children = [];

    for ($i = 0; $i < 50; $i++) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Unable to fork booking concurrency worker.');
        }
        if ($pid === 0) {
            DB::disconnect();
            while (! file_exists($barrier)) {
                usleep(1000);
            }
            try {
                $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
                $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
                $request = Request::create('/booking/appointments', 'POST', $payload, [], [], [
                    'REMOTE_ADDR' => '127.0.0.'.(($i % 200) + 1),
                    'HTTP_USER_AGENT' => 'task14-concurrency',
                ]);
                $result = app(\App\Domain\Booking\AppointmentCreationService::class)->create($request, $payload, 'concurrent-'.$i);
                file_put_contents($results, json_encode(['ok' => true, 'status' => $result['status']]).PHP_EOL, FILE_APPEND | LOCK_EX);
            } catch (\Throwable $exception) {
                file_put_contents($results, json_encode(['ok' => false, 'class' => $exception::class]).PHP_EOL, FILE_APPEND | LOCK_EX);
            }
            exit(0);
        }
        $children[] = $pid;
    }

    touch($barrier);
    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
    }

    $lines = file($results, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $successes = count(array_filter($lines, static fn (string $line): bool => (json_decode($line, true)['ok'] ?? false) === true));

    unlink($barrier);
    unlink($results);

    expect($successes)->toBe(1)
        ->and(Appointment::query()->count())->toBe(1);
})->uses(DatabaseMigrations::class);
