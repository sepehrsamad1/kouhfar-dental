<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use App\Appointment;
use App\AppointmentEvent;
use App\AuditLog;
use App\Enums\AppointmentStatus;
use App\StaffUser;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AppointmentStateMachine
{
    /**
     * @return list<AppointmentStatus>
     */
    public static function allowedFrom(AppointmentStatus $status): array
    {
        return match ($status) {
            AppointmentStatus::Pending => [
                AppointmentStatus::Confirmed,
                AppointmentStatus::Cancelled,
                AppointmentStatus::Expired,
            ],
            AppointmentStatus::Confirmed => [
                AppointmentStatus::Completed,
                AppointmentStatus::Cancelled,
                AppointmentStatus::NoShow,
            ],
            AppointmentStatus::Completed,
            AppointmentStatus::Cancelled,
            AppointmentStatus::NoShow,
            AppointmentStatus::Expired => [],
        };
    }

    public function transition(
        Appointment $appointment,
        AppointmentStatus $to,
        string $actorType,
        ?string $actorId = null,
        ?string $reasonCode = null,
    ): Appointment {
        $from = $appointment->status;

        if ($from === $to) {
            return $appointment;
        }

        if (! in_array($to, self::allowedFrom($from), true)) {
            throw new LogicException('Appointment status transition is not allowed.');
        }

        $now = now('UTC');

        $appointment->forceFill([
            'status' => $to,
            'version' => $appointment->version + 1,
            'updated_at' => $now,
        ])->save();

        $active = in_array($to, [
            AppointmentStatus::Pending,
            AppointmentStatus::Confirmed,
        ], true);

        DB::table('appointment_practitioners')
            ->where('appointment_id', $appointment->getKey())
            ->update(['active' => $active]);

        DB::table('appointment_resources')
            ->where('appointment_id', $appointment->getKey())
            ->update(['active' => $active]);

        AppointmentEvent::query()->create([
            'appointment_id' => $appointment->getKey(),
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'from_status' => $from,
            'to_status' => $to,
            'occurred_at' => $now,
        ]);

        if ($actorType === 'staff' && $actorId !== null) {
            $staff = StaffUser::query()->findOrFail($actorId);
            AuditLogger::record($staff, 'appointment.status_changed', $appointment, [
                'status' => $from->value,
                'appointment_id' => $appointment->getKey(),
            ], [
                'status' => $to->value,
                'reason_code' => $reasonCode,
                'appointment_id' => $appointment->getKey(),
            ]);
        } else {
            AuditLog::query()->create([
                'actor_id' => $actorId,
                'action' => 'appointment.status_changed',
                'target_type' => 'Appointment',
                'target_id' => $appointment->getKey(),
                'occurred_at' => $now,
                'request_id' => $this->requestId(),
                'meta' => [
                    'before' => ['status' => $from->value],
                    'after' => [
                        'status' => $to->value,
                        'reason_code' => $reasonCode,
                    ],
                ],
            ]);
        }

        return $appointment->refresh();
    }

    public function expirePendingHolds(): int
    {
        $now = now('UTC');
        $ids = DB::select(
            'UPDATE appointments
             SET status = ?, version = version + 1, updated_at = ?
             WHERE status = ? AND expires_at IS NOT NULL AND expires_at <= ?
             RETURNING id',
            [
                AppointmentStatus::Expired->value,
                $now,
                AppointmentStatus::Pending->value,
                $now,
            ],
        );

        if ($ids === []) {
            return 0;
        }

        $appointmentIds = array_map(static fn (object $row): string => (string) $row->id, $ids);

        DB::table('appointment_practitioners')
            ->whereIn('appointment_id', $appointmentIds)
            ->update(['active' => false]);

        DB::table('appointment_resources')
            ->whereIn('appointment_id', $appointmentIds)
            ->update(['active' => false]);

        foreach ($appointmentIds as $appointmentId) {
            AppointmentEvent::query()->create([
                'appointment_id' => $appointmentId,
                'actor_type' => 'system',
                'actor_id' => null,
                'from_status' => AppointmentStatus::Pending,
                'to_status' => AppointmentStatus::Expired,
                'occurred_at' => $now,
            ]);

            AuditLog::query()->create([
                'actor_id' => null,
                'action' => 'appointment.hold_expired',
                'target_type' => 'Appointment',
                'target_id' => $appointmentId,
                'occurred_at' => $now,
                'request_id' => $this->requestId(),
                'meta' => [
                    'before' => ['status' => AppointmentStatus::Pending->value],
                    'after' => [
                        'status' => AppointmentStatus::Expired->value,
                        'reason_code' => 'pending_hold_expired',
                    ],
                ],
            ]);
        }

        return count($appointmentIds);
    }

    private function requestId(): ?string
    {
        return app()->bound('request')
            ? request()->attributes->get('request_id')
            : null;
    }
}
