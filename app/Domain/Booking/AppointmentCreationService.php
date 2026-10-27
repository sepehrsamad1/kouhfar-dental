<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use App\Appointment;
use App\AuditLog;
use App\Domain\Availability\AvailabilityRepository;
use App\Domain\Booking\Exceptions\IdempotencyKeyReused;
use App\Domain\Booking\Exceptions\SlotUnavailable;
use App\Enums\AppointmentStatus;
use App\IdempotencyRecord;
use App\Patient;
use App\Service;
use App\Support\IranianPhoneNumber;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AppointmentCreationService
{
    public const IDEMPOTENCY_TTL_HOURS = 24;
    public const PENDING_HOLD_HOURS = 1;

    public function __construct(
        private readonly AvailabilityRepository $availability,
    ) {
    }

    public function create(Request $request, array $data, string $idempotencyKey): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $ownerScope = $this->ownerScope($request);
        $requestHash = hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($request, $data, $idempotencyKey, $ownerScope, $requestHash, $now): array {
            DB::selectOne('select pg_advisory_xact_lock(hashtext(?))', [$ownerScope.':'.$idempotencyKey]);

            $existing = IdempotencyRecord::query()
                ->where('owner_scope', $ownerScope)
                ->where('key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($existing->expires_at > CarbonImmutable::instance($now)) {
                    if ($existing->request_hash !== $requestHash) {
                        throw new IdempotencyKeyReused;
                    }

                    return [
                        'status' => $existing->response_status,
                        'body' => $existing->response_body ?? [],
                        'replayed' => true,
                    ];
                }

                $existing->delete();
            }

            $service = Service::query()
                ->whereKey($data['service_id'])
                ->where('is_active', true)
                ->where('is_public_bookable', true)
                ->first();

            if ($service === null) {
                throw new SlotUnavailable;
            }

            $startAt = $this->parseUtc($data['start_at']);
            $endAt = $startAt->modify(sprintf('+%d minutes', $service->duration_minutes));

            $this->expireOverduePending($startAt, $endAt, $now);

            $slots = $this->availability->forPublicService($service, $now);
            $matchingSlots = array_values(array_filter(
                $slots,
                static fn (array $slot): bool => $slot['start_at'] === $startAt->format('Y-m-d\TH:i:s\Z')
            ));

            if ($matchingSlots === []) {
                throw new SlotUnavailable;
            }

            $patient = Patient::query()->create([
                'full_name' => trim($data['full_name']),
                'mobile_normalized' => IranianPhoneNumber::normalize($data['mobile']),
            ]);

            $appointment = Appointment::query()->create([
                'patient_id' => $patient->getKey(),
                'service_id' => $service->getKey(),
                'status' => AppointmentStatus::Pending,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'source' => 'public',
                'expires_at' => CarbonImmutable::instance($now)->addHours(self::PENDING_HOLD_HOURS),
                'created_by' => null,
                'version' => 1,
            ]);

            $reserved = false;
            foreach ($matchingSlots as $slot) {
                try {
                    DB::transaction(function () use ($appointment, $slot, $service): void {
                        $occupiedStart = $appointment->start_at->modify(sprintf('-%d minutes', $service->buffer_before_minutes));
                        $occupiedEnd = $appointment->end_at->modify(sprintf('+%d minutes', $service->buffer_after_minutes));
                        $range = "tstzrange('{$occupiedStart->toIso8601String()}', '{$occupiedEnd->toIso8601String()}', '[)')";

                        DB::table('appointment_practitioners')->insert([
                            'appointment_id' => $appointment->getKey(),
                            'practitioner_id' => $slot['practitioner_id'],
                            'active' => true,
                            'occupied' => DB::raw($range),
                        ]);

                        DB::table('appointment_resources')->insert([
                            'appointment_id' => $appointment->getKey(),
                            'resource_id' => $slot['resource_id'],
                            'active' => true,
                            'occupied' => DB::raw($range),
                        ]);
                    });

                    $reserved = true;
                    break;
                } catch (QueryException $exception) {
                    if (! $this->isOverlapViolation($exception)) {
                        throw $exception;
                    }
                }
            }

            if (! $reserved) {
                throw new SlotUnavailable;
            }

            AppointmentEvent::query()->create([
                'appointment_id' => $appointment->getKey(),
                'actor_type' => 'public',
                'actor_id' => null,
                'from_status' => null,
                'to_status' => AppointmentStatus::Pending,
                'occurred_at' => $now,
            ]);

            AuditLog::query()->create([
                'actor_id' => null,
                'action' => 'appointment.created',
                'target_type' => 'Appointment',
                'target_id' => $appointment->getKey(),
                'occurred_at' => $now,
                'request_id' => $request->attributes->get('request_id'),
                'meta' => [
                    'status' => AppointmentStatus::Pending->value,
                    'service_id' => (string) $service->getKey(),
                    'duration_minutes' => (int) $service->duration_minutes,
                    'buffer_before_minutes' => (int) $service->buffer_before_minutes,
                    'buffer_after_minutes' => (int) $service->buffer_after_minutes,
                ],
            ]);

            $body = [
                'status' => AppointmentStatus::Pending->value,
            ];

            IdempotencyRecord::query()->create([
                'owner_scope' => $ownerScope,
                'key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'response_status' => 201,
                'response_body' => $body,
                'created_at' => $now,
                'expires_at' => CarbonImmutable::instance($now)->addHours(self::IDEMPOTENCY_TTL_HOURS),
            ]);

            return [
                'status' => 201,
                'body' => $body,
                'replayed' => false,
            ];
        });
    }

    private function expireOverduePending(DateTimeImmutable $startAt, DateTimeImmutable $endAt, DateTimeImmutable $now): void
    {
        $appointments = Appointment::query()
            ->where('status', AppointmentStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', CarbonImmutable::instance($now))
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->lockForUpdate()
            ->get();

        $stateMachine = new AppointmentStateMachine;
        foreach ($appointments as $appointment) {
            $stateMachine->expireIfDue($appointment, $now);
        }
    }

    private function parseUtc(string $value): DateTimeImmutable
    {
        try {
            $parsed = new DateTimeImmutable($value);
        } catch (\Throwable) {
            throw new SlotUnavailable;
        }

        return $parsed->setTimezone(new DateTimeZone('UTC'));
    }

    private function ownerScope(Request $request): string
    {
        return 'public:'.hash('sha256', $request->ip().'|'.$request->userAgent());
    }

    private function isOverlapViolation(QueryException $exception): bool
    {
        return (string) ($exception->errorInfo[0] ?? $exception->getCode()) === '23P01';
    }
}
