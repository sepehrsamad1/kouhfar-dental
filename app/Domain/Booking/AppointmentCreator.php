<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use App\Appointment;
use App\AppointmentEvent;
use App\AuditLog;
use App\Domain\Availability\AvailabilityRepository;
use App\Enums\AppointmentNotificationType;
use App\Enums\AppointmentStatus;
use App\IdempotencyRecord;
use App\NotificationOutbox;
use App\Patient;
use App\Practitioner;
use App\Resource;
use App\Service;
use App\Support\IranianPhoneNumber;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

final class AppointmentCreator
{
    private const IDEMPOTENCY_TTL_HOURS = 24;

    private AppointmentStateMachine $states;

    private AvailabilityRepository $availability;

    public function __construct(
        AppointmentStateMachine $states,
        AvailabilityRepository $availability,
    ) {
        $this->states = $states;
        $this->availability = $availability;
    }

    public function create(array $input, string $idempotencyKey, string $ownerScope = 'public:appointment:create:v1'): array
    {
        $normalizedInput = [
            'full_name' => trim($input['full_name']),
            'mobile' => IranianPhoneNumber::normalize($input['mobile']),
            'service_id' => $input['service_id'],
            'start_at' => (new DateTimeImmutable($input['start_at']))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z'),
        ];

        $requestHash = hash('sha256', $this->canonicalJson($normalizedInput));

        return DB::transaction(function () use ($normalizedInput, $idempotencyKey, $ownerScope, $requestHash): array {
            $record = $this->lockIdempotencyRecord($ownerScope, $idempotencyKey, $requestHash);

            if ($record->response_status !== 0 && $record->response_body !== null) {
                return [
                    'status' => $record->response_status,
                    'body' => $record->response_body,
                ];
            }

            $now = CarbonImmutable::now('UTC');
            $this->states->expirePendingHolds();

            $service = Service::query()
                ->whereKey($normalizedInput['service_id'])
                ->where('is_active', true)
                ->where('is_public_bookable', true)
                ->first();

            if ($service === null) {
                return $this->storeResponse(
                    $record,
                    409,
                    ['code' => 'SLOT_UNAVAILABLE', 'message' => 'The selected slot is no longer available.'],
                );
            }

            $requestedStart = new DateTimeImmutable($normalizedInput['start_at']);
            $requestedEnd = $requestedStart->modify(sprintf('+%d minutes', $service->duration_minutes));

            $available = $this->availability->forPublicService($service, $now->toDateTimeImmutable());
            $matches = array_values(array_filter(
                $available,
                static fn (array $slot): bool => $slot['start_at'] === $requestedStart->format('Y-m-d\TH:i:s\Z')
                    && $slot['end_at'] === $requestedEnd->format('Y-m-d\TH:i:s\Z'),
            ));

            if ($matches === []) {
                return $this->storeResponse(
                    $record,
                    409,
                    ['code' => 'SLOT_UNAVAILABLE', 'message' => 'The selected slot is no longer available.'],
                );
            }

            usort($matches, static fn (array $left, array $right): int => [
                $left['practitioner_id'],
                $left['resource_id'],
            ] <=> [
                $right['practitioner_id'],
                $right['resource_id'],
            ]);

            $patient = Patient::query()->create([
                'full_name' => $normalizedInput['full_name'],
                'mobile_normalized' => $normalizedInput['mobile'],
            ]);

            $expiresAt = $now->addHour();
            $occupiedStart = $requestedStart->modify(sprintf('-%d minutes', $service->buffer_before_minutes));
            $occupiedEnd = $requestedEnd->modify(sprintf('+%d minutes', $service->buffer_after_minutes));

            $appointment = Appointment::query()->create([
                'patient_id' => $patient->getKey(),
                'service_id' => $service->getKey(),
                'status' => AppointmentStatus::Pending,
                'start_at' => $requestedStart,
                'end_at' => $requestedEnd,
                'source' => 'public',
                'expires_at' => $expiresAt,
                'created_by' => null,
                'version' => 1,
            ]);

            $reserved = false;
            foreach ($matches as $match) {
                try {
                    DB::selectOne(
                        'select pg_advisory_xact_lock(hashtextextended(?, 0))',
                        [sprintf('booking-capacity:%s:%s:%s:%s', $match['practitioner_id'], $match['resource_id'], $occupiedStart->format('c'), $occupiedEnd->format('c'))],
                    );
                    DB::transaction(function () use ($appointment, $match, $occupiedStart, $occupiedEnd): void {
                        $practitioner = Practitioner::query()
                            ->whereKey($match['practitioner_id'])
                            ->lockForUpdate()
                            ->firstOrFail();
                        $resource = Resource::query()
                            ->whereKey($match['resource_id'])
                            ->lockForUpdate()
                            ->firstOrFail();

                        $conflictingPractitioner = DB::table('appointment_practitioners')
                            ->where('practitioner_id', $practitioner->getKey())
                            ->where('active', true)
                            ->whereRaw(
                                "occupied && tstzrange(?::timestamptz, ?::timestamptz, '[)')",
                                [$occupiedStart->format('Y-m-d H:i:sP'), $occupiedEnd->format('Y-m-d H:i:sP')],
                            )
                            ->exists();

                        $conflictingResource = DB::table('appointment_resources')
                            ->where('resource_id', $resource->getKey())
                            ->where('active', true)
                            ->whereRaw(
                                "occupied && tstzrange(?::timestamptz, ?::timestamptz, '[)')",
                                [$occupiedStart->format('Y-m-d H:i:sP'), $occupiedEnd->format('Y-m-d H:i:sP')],
                            )
                            ->exists();

                        if ($conflictingPractitioner || $conflictingResource) {
                            throw new AppointmentBookingException(
                                'SLOT_UNAVAILABLE',
                                'The selected slot is no longer available.',
                            );
                        }

                        $range = sprintf(
                            "tstzrange('%s'::timestamptz, '%s'::timestamptz, '[)')",
                            $occupiedStart->format('Y-m-d H:i:sP'),
                            $occupiedEnd->format('Y-m-d H:i:sP'),
                        );

                        DB::table('appointment_practitioners')->insert([
                            'appointment_id' => $appointment->getKey(),
                            'practitioner_id' => $practitioner->getKey(),
                            'active' => true,
                            'occupied' => DB::raw($range),
                        ]);

                        DB::table('appointment_resources')->insert([
                            'appointment_id' => $appointment->getKey(),
                            'resource_id' => $resource->getKey(),
                            'active' => true,
                            'occupied' => DB::raw($range),
                        ]);
                    });

                    $reserved = true;
                    break;
                } catch (AppointmentBookingException) {
                    continue;
                } catch (QueryException $exception) {
                    if ($this->isExclusionViolation($exception) === false) {
                        throw $exception;
                    }
                }
            }

            if ($reserved === false) {
                $appointment->delete();
                $patient->delete();

                return $this->storeResponse(
                    $record,
                    409,
                    ['code' => 'SLOT_UNAVAILABLE', 'message' => 'The selected slot is no longer available.'],
                );
            }

            AppointmentEvent::query()->create([
                'appointment_id' => $appointment->getKey(),
                'actor_type' => 'public',
                'actor_id' => null,
                'from_status' => null,
                'to_status' => AppointmentStatus::Pending,
                'occurred_at' => $now,
            ]);

            NotificationOutbox::enqueue($appointment->getKey(), AppointmentNotificationType::Pending, $now, 'appointment:'.$appointment->getKey().':booking_pending');

            AuditLog::query()->create([
                'actor_id' => null,
                'action' => 'appointment.created',
                'target_type' => Appointment::class,
                'target_id' => $appointment->getKey(),
                'occurred_at' => $now,
                'request_id' => $this->requestId(),
                'meta' => [
                    'after' => [
                        'status' => AppointmentStatus::Pending->value,
                        'service_id' => $service->getKey(),
                        'practitioner_id' => $match['practitioner_id'],
                        'resource_id' => $match['resource_id'],
                        'reason_code' => 'public_booking_created',
                    ],
                ],
            ]);

            return $this->storeResponse(
                $record,
                201,
                [
                    'appointment_id' => $appointment->getKey(),
                    'status' => AppointmentStatus::Pending->value,
                    'start_at' => $requestedStart->format('Y-m-d\TH:i:s\Z'),
                    'end_at' => $requestedEnd->format('Y-m-d\TH:i:s\Z'),
                    'expires_at' => $expiresAt->format('Y-m-d\TH:i:s\Z'),
                ],
            );
        }, 3);
    }

    private function lockIdempotencyRecord(string $ownerScope, string $key, string $requestHash): IdempotencyRecord
    {
        $now = now('UTC');

        DB::statement(
            'INSERT INTO idempotency_records
                (id, owner_scope, key, request_hash, response_status, response_body, created_at, expires_at)
             VALUES (?, ?, ?, ?, 0, NULL, ?, ?)
             ON CONFLICT (owner_scope, key) DO NOTHING',
            [
                (string) Str::uuid(),
                $ownerScope,
                $key,
                $requestHash,
                $now,
                $now->copy()->addHours(self::IDEMPOTENCY_TTL_HOURS),
            ],
        );

        $record = IdempotencyRecord::query()
            ->where('owner_scope', $ownerScope)
            ->where('key', $key)
            ->lockForUpdate()
            ->firstOrFail();

        if ($record->expires_at <= $now) {
            $record->forceFill([
                'request_hash' => $requestHash,
                'response_status' => 0,
                'response_body' => null,
                'created_at' => $now,
                'expires_at' => $now->copy()->addHours(self::IDEMPOTENCY_TTL_HOURS),
            ])->save();

            return $record;
        }

        if (hash_equals($record->request_hash, $requestHash) === false) {
            throw new AppointmentBookingException(
                'IDEMPOTENCY_CONFLICT',
                'The idempotency key was already used for a different request.',
            );
        }

        return $record;
    }

    private function storeResponse(IdempotencyRecord $record, int $status, array $body): array
    {
        $record->forceFill([
            'response_status' => $status,
            'response_body' => $body,
        ])->save();

        return ['status' => $status, 'body' => $body];
    }

    private function isExclusionViolation(QueryException $exception): bool
    {
        return $exception->getCode() === '23P01'
            || str_contains($exception->getMessage(), 'no_overlap');
    }

    private function requestId(): ?string
    {
        return app()->bound('request')
            ? request()->attributes->get('request_id')
            : null;
    }

    private function canonicalJson(array $input): string
    {
        try {
            return json_encode(
                $input,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $exception) {
            throw new \LogicException('Could not hash booking request.', 0, $exception);
        }
    }
}
