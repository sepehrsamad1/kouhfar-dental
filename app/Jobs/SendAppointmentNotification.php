<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Appointment;
use App\Enums\AppointmentNotificationType;
use App\Enums\AppointmentStatus;
use App\NotificationOutbox;
use App\Notifications\AppointmentSmsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class SendAppointmentNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public string $outboxId) {}

    public function uniqueId(): string
    {
        return $this->outboxId;
    }

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('notification-outbox:'.$this->outboxId))
            ->releaseAfter(30)
            ->expireAfter(120)];
    }

    public function handle(): void
    {
        $outbox = NotificationOutbox::query()->find($this->outboxId);
        if ($outbox === null || $outbox->sent_at !== null) {
            return;
        }

        $appointment = Appointment::query()
            ->with(['patient', 'practitioners'])
            ->find($outbox->appointment_id);

        if ($appointment === null || $appointment->patient === null) {
            return;
        }

        $type = $outbox->type;
        $currentStatus = $appointment->status;

        if ($type === AppointmentNotificationType::Confirmed && $currentStatus !== AppointmentStatus::Confirmed) {
            return;
        }

        if ($type === AppointmentNotificationType::Reminder && $currentStatus !== \App\Enums\AppointmentStatus::Confirmed) {
            return;
        }

        if ($type === AppointmentNotificationType::Expired && $currentStatus !== AppointmentStatus::Expired) {
            return;
        }

        $outbox->increment('attempts');

        Notification::sendNow(
            $appointment->patient,
            new AppointmentSmsNotification($appointment, $type),
        );

        $outbox->forceFill([
            'sent_at' => now('UTC'),
            'last_error' => null,
        ])->save();
    }

    public function failed(\Throwable $exception): void
    {
        NotificationOutbox::query()
            ->whereKey($this->outboxId)
            ->update([
                'last_error' => 'SMS notification delivery failed after retries.',
            ]);
    }
}
