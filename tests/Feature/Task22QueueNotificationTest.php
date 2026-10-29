<?php

declare(strict_types=1);

use App\Appointment;
use App\Domain\Booking\AppointmentStateMachine;
use App\Enums\AppointmentNotificationType;
use App\Enums\AppointmentStatus;
use App\ClinicWorkingHour;
use App\Domain\Booking\AppointmentCreator;
use App\Jobs\SendAppointmentNotification;
use App\NotificationOutbox;
use App\Practitioner;
use App\Resource;
use App\Service;
use App\WorkingHour;
use App\Notifications\SmsChannel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\FakeSmsProvider;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-27T18:00:00Z');
    config(['notifications.sms.driver' => 'null']);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function task22Booking(): array
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

    $result = app(AppointmentCreator::class)->create([
        'full_name' => 'Synthetic Queue Patient',
        'mobile' => '09120000001',
        'service_id' => $service->getKey(),
        'start_at' => '2026-10-29T09:30:00Z',
    ], 'task22-'.uniqid());

    return [Appointment::query()->findOrFail($result['body']['appointment_id']), $practitioner];
}

it('creates one pending outbox entry and never duplicates it', function (): void {
    [$appointment] = task22Booking();

    expect(NotificationOutbox::query()
        ->where('appointment_id', $appointment->getKey())
        ->where('type', AppointmentNotificationType::Pending->value)
        ->count())->toBe(1);

    NotificationOutbox::enqueue(
        $appointment->getKey(),
        AppointmentNotificationType::Pending,
        now('UTC'),
        'appointment:'.$appointment->getKey().':booking_pending',
    );

    expect(NotificationOutbox::query()
        ->where('appointment_id', $appointment->getKey())
        ->where('type', AppointmentNotificationType::Pending->value)
        ->count())->toBe(1);
});

it('expires only overdue pending appointments and releases capacity idempotently', function (): void {
    [$appointment] = task22Booking();

    $appointment->update(['expires_at' => now('UTC')->subSecond()]);

    expect(app(AppointmentStateMachine::class)->expirePendingHolds())->toBe(1)
        ->and(app(AppointmentStateMachine::class)->expirePendingHolds())->toBe(0)
        ->and($appointment->refresh()->status)->toBe(AppointmentStatus::Expired);

    expect(NotificationOutbox::query()
        ->where('appointment_id', $appointment->getKey())
        ->where('type', AppointmentNotificationType::Expired->value)
        ->count())->toBe(1);
});

it('delays automated notifications during quiet hours but not transactional notifications', function (): void {
    [$appointment] = task22Booking();

    Carbon::setTestNow('2026-10-27T20:00:00Z'); // 23:30 Asia/Tehran
    NotificationOutbox::enqueue($appointment->getKey(), AppointmentNotificationType::Reminder, now('UTC'), 'quiet-reminder');
    NotificationOutbox::enqueue($appointment->getKey(), AppointmentNotificationType::Pending, now('UTC'), 'quiet-pending');

    Artisan::call('notifications:dispatch-due');

    expect(NotificationOutbox::query()->where('dedupe_key', 'quiet-reminder')->firstOrFail()->scheduled_at->setTimezone('Asia/Tehran')->format('H:i'))->toBe('08:01')
        ->and(NotificationOutbox::query()->where('dedupe_key', 'quiet-pending')->firstOrFail()->sent_at)->toBeNull();
});

it('sends an outbox notification once even if the job is executed twice', function (): void {
    [$appointment] = task22Booking();
    $outbox = NotificationOutbox::query()->where('appointment_id', $appointment->getKey())->where('type', AppointmentNotificationType::Pending)->firstOrFail();

    $fake = new FakeSmsProvider();
    app()->instance(SmsChannel::class, new SmsChannel($fake));

    (new SendAppointmentNotification($outbox->getKey()))->handle();
    (new SendAppointmentNotification($outbox->getKey()))->handle();

    expect($fake->messages)->toHaveCount(1)
        ->and($outbox->refresh()->sent_at)->not->toBeNull();
});

it('retries a failed provider call without changing the outbox payload', function (): void {
    [$appointment] = task22Booking();
    $outbox = NotificationOutbox::query()->where('appointment_id', $appointment->getKey())->where('type', AppointmentNotificationType::Pending)->firstOrFail();

    $fake = new FakeSmsProvider();
    $fake->fail = true;
    app()->instance(SmsChannel::class, new SmsChannel($fake));

    expect(fn () => (new SendAppointmentNotification($outbox->getKey()))->handle())->toThrow(RuntimeException::class);

    $fake->fail = false;
    (new SendAppointmentNotification($outbox->getKey()))->handle();

    expect($outbox->refresh()->attempts)->toBe(2)
        ->and($outbox->sent_at)->not->toBeNull()
        ->and($fake->messages)->toHaveCount(1);
});

it('uses the approved 24-hour reminder timing after confirmation', function (): void {
    [$appointment] = task22Booking();
    $appointment->refresh();

    Carbon::setTestNow($appointment->start_at->subDays(2));
    $appointment->expires_at = now('UTC')->addHour();
    $appointment->save();

    app(AppointmentStateMachine::class)->transition(
        $appointment,
        AppointmentStatus::Confirmed,
        'system',
        null,
        'test_confirmed',
        $appointment->version,
    );

    $reminder = NotificationOutbox::query()
        ->where('appointment_id', $appointment->getKey())
        ->where('type', AppointmentNotificationType::Reminder->value)
        ->first();

    expect($reminder)->not->toBeNull()
        ->and($reminder->scheduled_at->equalTo($appointment->start_at->subDay()))->toBeTrue();
});
