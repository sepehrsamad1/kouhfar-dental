<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use App\Appointment;
use App\AppointmentEvent;
use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AppointmentStateMachine
{
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled', 'expired'],
        'confirmed' => ['completed', 'cancelled', 'no_show'],
        'completed' => [],
        'cancelled' => [],
        'no_show' => [],
        'expired' => [],
    ];

    public function transition(
        Appointment $appointment,
        AppointmentStatus $to,
        string $actorType,
        ?string $actorId = null,
        ?DateTimeImmutable $occurredAt = null,
    ): Appointment {
        $from = $appointment->status;

        if ($from === $to) {
            return $appointment;
        }

        if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
            throw new LogicException('Invalid appointment status transition.');
        }

        $now = $occurredAt ?? new DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $appointment->status = $to;
        $appointment->version++;
        $appointment->save();

        if (in_array($to, [AppointmentStatus::Cancelled, AppointmentStatus::Completed, AppointmentStatus::NoShow, AppointmentStatus::Expired], true)) {
            $this->deactivateCapacity($appointment);
        }

        AppointmentEvent::query()->create([
            'appointment_id' => $appointment->getKey(),
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'from_status' => $from,
            'to_status' => $to,
            'occurred_at' => $now,
        ]);

        return $appointment->refresh();
    }

    public function expireIfDue(Appointment $appointment, DateTimeImmutable $now): bool
    {
        $appointment = Appointment::query()
            ->whereKey($appointment->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ($appointment->status !== AppointmentStatus::Pending || $appointment->expires_at === null || $appointment->expires_at > CarbonImmutable::instance($now)) {
            return false;
        }

        $updated = Appointment::query()
            ->whereKey($appointment->getKey())
            ->where('status', AppointmentStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', CarbonImmutable::instance($now))
            ->update([
                'status' => AppointmentStatus::Expired->value,
                'version' => DB::raw('version + 1'),
                'updated_at' => CarbonImmutable::instance($now),
            ]);

        if ($updated !== 1) {
            return false;
        }

        $this->deactivateCapacity($appointment);

        AppointmentEvent::query()->create([
            'appointment_id' => $appointment->getKey(),
            'actor_type' => 'system',
            'actor_id' => null,
            'from_status' => AppointmentStatus::Pending,
            'to_status' => AppointmentStatus::Expired,
            'occurred_at' => $now,
        ]);

        return true;
    }

    private function deactivateCapacity(Appointment $appointment): void
    {
        DB::table('appointment_practitioners')
            ->where('appointment_id', $appointment->getKey())
            ->where('active', true)
            ->update(['active' => false]);

        DB::table('appointment_resources')
            ->where('appointment_id', $appointment->getKey())
            ->where('active', true)
            ->update(['active' => false]);
    }
}
