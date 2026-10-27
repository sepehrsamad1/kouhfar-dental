<?php

declare(strict_types=1);

namespace App\Domain\Availability;

use App\Appointment;
use App\Enums\AppointmentStatus;
use App\ClinicWorkingHour;
use App\Practitioner;
use App\Resource;
use App\ScheduleBreak;
use App\ScheduleException;
use App\Service;
use App\WorkingHour;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final class AvailabilityRepository
{
    public const TIMEZONE = 'Asia/Tehran';

    public function forPublicService(Service $service, DateTimeImmutable $now): array
    {
        if ($service->is_active === false || $service->is_public_bookable === false) {

            return [];
        }

        $timezone = new DateTimeZone(self::TIMEZONE);
        $localNow = CarbonImmutable::instance($now)->setTimezone($timezone);
        $rangeStart = $localNow->startOfDay()->utc();
        $rangeEnd = $localNow->startOfDay()->addDays(15)->utc();

        /** @var EloquentCollection<int, ClinicWorkingHour> $clinicHourModels */
        $clinicHourModels = ClinicWorkingHour::query()->get();
        $clinicHours = $clinicHourModels->groupBy('weekday');
        /** @var EloquentCollection<int, Practitioner> $practitioners */
        $practitioners = $service->practitioners()->where('practitioners.is_active', true)->get();
        $resourceQuery = Resource::query()
            ->where('is_active', true)
            ->whereHas('services', fn ($query) => $query->whereKey($service->getKey()));
        if ($service->required_resource_kind !== null) {
            $resourceQuery->where('kind', $service->required_resource_kind);
        }
        $compatibleResources = $resourceQuery->get();

        // Include appointments whose service buffers overlap the requested window,
        // even when their actual appointment interval sits just outside it.
        $maxBufferBefore = (int) (Service::query()->max('buffer_before_minutes') ?? 0);
        $maxBufferAfter = (int) (Service::query()->max('buffer_after_minutes') ?? 0);

        /** @var EloquentCollection<int, Appointment> $appointmentModels */
        $appointmentModels = Appointment::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_at', '<', $rangeEnd->addMinutes($maxBufferBefore))
            ->where('end_at', '>', $rangeStart->subMinutes($maxBufferAfter))
            ->with(['practitioners', 'resources', 'service'])
            ->get();

        $appointments = [];
        foreach ($appointmentModels as $appointment) {
            $serviceModel = $appointment->service;
            /** @var EloquentCollection<int, Practitioner> $appointmentPractitioners */
            $appointmentPractitioners = $appointment->practitioners;
            /** @var EloquentCollection<int, Resource> $appointmentResources */
            $appointmentResources = $appointment->resources;
            $practitionerIds = [];
            foreach ($appointmentPractitioners as $appointmentPractitioner) {
                if ((bool) $appointmentPractitioner->pivot->active) {
                    $practitionerIds[] = (string) $appointmentPractitioner->getKey();
                }
            }
            $resourceIds = [];
            foreach ($appointmentResources as $appointmentResource) {
                if ((bool) $appointmentResource->pivot->active) {
                    $resourceIds[] = (string) $appointmentResource->getKey();
                }
            }
            $status = $appointment->status;
            $statusValue = $status instanceof AppointmentStatus ? $status->value : (string) $status;
            $startAt = CarbonImmutable::parse((string) $appointment->start_at);
            $endAt = CarbonImmutable::parse((string) $appointment->end_at);
            $expiresAt = $appointment->expires_at === null
                ? null
                : CarbonImmutable::parse((string) $appointment->expires_at);

            $appointments[] = [
                'start' => $startAt->subMinutes((int) $serviceModel->buffer_before_minutes)->toDateTimeImmutable(),
                'end' => $endAt->addMinutes((int) $serviceModel->buffer_after_minutes)->toDateTimeImmutable(),
                'status' => $statusValue,
                'expires_at' => $expiresAt?->toDateTimeImmutable(),
                'practitioner_ids' => $practitionerIds,
                'resource_ids' => $resourceIds,
            ];
        }

        $engine = new AvailabilityEngine;
        $slots = [];

        foreach ($practitioners as $practitioner) {
            $periods = $this->workingPeriods($practitioner, $clinicHours, $localNow, $rangeEnd);
            if ($periods === []) {

                continue;
            }

            $slots = array_merge($slots, $engine->slots([
                'timezone' => self::TIMEZONE,
                'now' => $now,
                'duration_minutes' => $service->duration_minutes,
                'buffer_before_minutes' => $service->buffer_before_minutes,
                'buffer_after_minutes' => $service->buffer_after_minutes,
                'minimum_notice_minutes' => 1440,
                'horizon_days' => 14,
                'grid_minutes' => 5,
                'practitioner_ids' => [$practitioner->id],
                'resources' => $compatibleResources->map(fn (Resource $resource): array => [
                    'id' => $resource->id,
                    'kind' => $resource->kind,
                    'active' => $resource->is_active,
                ])->all(),
                'required_resource_kind' => $service->required_resource_kind,
                'working_periods' => $periods,
                'closed_dates' => [],
                'appointments' => $appointments,
            ]));
        }

        return $slots;
    }

    private function workingPeriods(
        Practitioner $practitioner,
        Collection $clinicHours,
        CarbonImmutable $localNow,
        CarbonImmutable $rangeEnd
    ): array {
        $periods = [];
        /** @var EloquentCollection<int, WorkingHour> $workingHourModels */
        $workingHourModels = WorkingHour::query()->where('practitioner_id', $practitioner->id)->get();
        $practitionerHours = $workingHourModels->groupBy('weekday');
        /** @var EloquentCollection<int, ScheduleBreak> $breakModels */
        $breakModels = ScheduleBreak::query()
            ->where(fn ($query) => $query->whereNull('practitioner_id')->orWhere('practitioner_id', $practitioner->id))
            ->get();
        $breaks = $breakModels->groupBy('weekday');
        /** @var EloquentCollection<int, ScheduleException> $exceptions */
        $exceptions = ScheduleException::query()
            ->where(fn ($query) => $query->whereNull('practitioner_id')->orWhere('practitioner_id', $practitioner->id))
            ->where('starts_at', '<', $rangeEnd)
            ->where('ends_at', '>', $localNow->startOfDay()->utc())
            ->get();

        for ($date = $localNow->startOfDay(); $date->utc() < $rangeEnd; $date = $date->addDay()) {
            $weekday = $date->dayOfWeekIso;
            $clinic = $clinicHours->get($weekday, collect());
            $staff = $practitionerHours->get($weekday, collect());
            $segments = [];

            foreach ($clinic as $clinicHour) {
                foreach ($staff as $staffHour) {
                    $start = $this->localTime($date, max($clinicHour->start_local, $staffHour->start_local));
                    $end = $this->localTime($date, min($clinicHour->end_local, $staffHour->end_local));
                    if ($end > $start) {
                        $segments[] = [$start, $end];
                    }
                }
            }

            // An explicit special opening is a manager-configured exception to the
            // regular weekly schedule. Clinic-wide openings apply to every active
            // practitioner; practitioner-scoped openings apply only to that practitioner.
            foreach ($exceptions as $exception) {
                if ($exception->kind !== 'special_opening') {

                    continue;
                }
                if ($exception->practitioner_id !== null && $exception->practitioner_id !== $practitioner->id) {

                    continue;
                }
                $exceptionStart = $exception->starts_at->toDateTimeImmutable();
                $exceptionEnd = $exception->ends_at->toDateTimeImmutable();
                $dayStart = $date->startOfDay()->toDateTimeImmutable();
                $dayEnd = $date->addDay()->startOfDay()->toDateTimeImmutable();
                $openingStart = $exceptionStart > $dayStart ? $exceptionStart : $dayStart;
                $openingEnd = $exceptionEnd < $dayEnd ? $exceptionEnd : $dayEnd;
                if ($openingEnd > $openingStart) {
                    $segments[] = [$openingStart, $openingEnd];
                }
            }

            if ($segments === []) {

                continue;
            }

            // Normalize overlapping normal/opening intervals before subtracting breaks
            // and closures so a special opening cannot re-open a closed interval.
            usort($segments, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
            $merged = [];
            foreach ($segments as [$segmentStart, $segmentEnd]) {
                if ($merged === [] || $segmentStart > $merged[array_key_last($merged)][1]) {
                    $merged[] = [$segmentStart, $segmentEnd];
                } else {
                    $last = array_key_last($merged);
                    if ($segmentEnd > $merged[$last][1]) {
                        $merged[$last][1] = $segmentEnd;
                    }
                }
            }
            $segments = $merged;

            foreach ($breaks->get($weekday, collect()) as $break) {
                if ($break->practitioner_id !== null && $break->practitioner_id !== $practitioner->id) {

                    continue;
                }
                $segments = $this->subtract(
                    $segments,
                    $this->localTime($date, $break->start_local),
                    $this->localTime($date, $break->end_local)
                );
            }

            foreach ($exceptions as $exception) {
                if ($exception->practitioner_id !== null && $exception->practitioner_id !== $practitioner->id) {

                    continue;
                }
                if (in_array($exception->kind, ['closed', 'holiday'], true)) {
                    $segments = $this->subtract(
                        $segments,
                        $exception->starts_at->toDateTimeImmutable(),
                        $exception->ends_at->toDateTimeImmutable()
                    );
                }
            }

            foreach ($segments as [$segmentStart, $segmentEnd]) {
                $periods[] = ['start' => $segmentStart, 'end' => $segmentEnd];
            }
        }

        return $periods;
    }

    private function localTime(CarbonImmutable $date, string $time): DateTimeImmutable
    {

        return new DateTimeImmutable($date->format('Y-m-d').' '.substr($time, 0, 8), new DateTimeZone(self::TIMEZONE));
    }

    private function subtract(array $segments, DateTimeImmutable $cutStart, DateTimeImmutable $cutEnd): array
    {
        $result = [];
        foreach ($segments as [$start, $end]) {
            if ($cutEnd <= $start || $cutStart >= $end) {
                $result[] = [$start, $end];

                continue;
            }
            if ($cutStart > $start) {
                $result[] = [$start, $cutStart];
            }
            if ($cutEnd < $end) {
                $result[] = [$cutEnd, $end];
            }
        }

        return array_values(array_filter($result, static fn (array $segment): bool => $segment[1] > $segment[0]));
    }
}
