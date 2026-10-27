<?php

declare(strict_types=1);

namespace App\Domain\Availability;

use App\Appointment;
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
use Illuminate\Support\Collection;

final class AvailabilityRepository
{
    public const TIMEZONE = 'Asia/Tehran';

    public function forPublicService(Service $service, DateTimeImmutable $now): array
    {
        if (!$service->is_active || !$service->is_public_bookable) {
            return [];
        }

        $timezone = new DateTimeZone(self::TIMEZONE);
        $localNow = CarbonImmutable::instance($now)->setTimezone($timezone);
        $rangeStart = $localNow->startOfDay()->utc();
        $rangeEnd = $localNow->startOfDay()->addDays(15)->utc();

        $clinicHours = ClinicWorkingHour::query()->get()->groupBy('weekday');
        $practitioners = $service->practitioners()->where('practitioners.is_active', true)->get();
        $compatibleResources = Resource::query()
            ->where('is_active', true)
            ->where('kind', $service->required_resource_kind)
            ->whereHas('services', fn ($query) => $query->whereKey($service->getKey()))
            ->get();

        // Include appointments whose service buffers overlap the requested window,
        // even when their actual appointment interval sits just outside it.
        $maxBufferBefore = (int) (Service::query()->max('buffer_before_minutes') ?? 0);
        $maxBufferAfter = (int) (Service::query()->max('buffer_after_minutes') ?? 0);

        $appointments = Appointment::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_at', '<', $rangeEnd->addMinutes($maxBufferBefore))
            ->where('end_at', '>', $rangeStart->subMinutes($maxBufferAfter))
            ->with(['practitioners', 'resources', 'service'])
            ->get()
            ->map(static function (Appointment $appointment): array {
                return [
                    'start' => $appointment->start_at->subMinutes($appointment->service->buffer_before_minutes)->toDateTimeImmutable(),
                    'end' => $appointment->end_at->addMinutes($appointment->service->buffer_after_minutes)->toDateTimeImmutable(),
                    'status' => $appointment->status->value,
                    'expires_at' => $appointment->expires_at?->toDateTimeImmutable(),
                    'practitioner_ids' => $appointment->practitioners
                        ->filter(fn ($practitioner) => (bool) $practitioner->pivot->active)
                        ->pluck('id')->all(),
                    'resource_ids' => $appointment->resources
                        ->filter(fn ($resource) => (bool) $resource->pivot->active)
                        ->pluck('id')->all(),
                ];
            })->all();

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
        $practitionerHours = WorkingHour::query()->where('practitioner_id', $practitioner->id)->get()->groupBy('weekday');
        $breaks = ScheduleBreak::query()
            ->where(fn ($query) => $query->whereNull('practitioner_id')->orWhere('practitioner_id', $practitioner->id))
            ->get()->groupBy('weekday');
        $exceptions = ScheduleException::query()
            ->where(fn ($query) => $query->whereNull('practitioner_id')->orWhere('practitioner_id', $practitioner->id))
            ->where('starts_at', '<', $rangeEnd)
            ->where('ends_at', '>', $localNow->startOfDay()->utc())
            ->get();

        for ($date = $localNow->startOfDay(); $date->utc() < $rangeEnd; $date = $date->addDay()) {
            $weekday = $date->dayOfWeekIso;
            $clinic = $clinicHours->get($weekday, collect());
            $staff = $practitionerHours->get($weekday, collect());
            if ($clinic->isEmpty() || $staff->isEmpty()) {
                continue;
            }

            foreach ($clinic as $clinicHour) {
                foreach ($staff as $staffHour) {
                    $start = $this->localTime($date, max($clinicHour->start_local, $staffHour->start_local));
                    $end = $this->localTime($date, min($clinicHour->end_local, $staffHour->end_local));
                    if ($end <= $start) {
                        continue;
                    }

                    $segments = [[$start, $end]];
                    foreach ($breaks->get($weekday, collect()) as $break) {
                        if ($break->practitioner_id !== null && $break->practitioner_id !== $practitioner->id) {
                            continue;
                        }
                        $segments = $this->subtract($segments, $this->localTime($date, $break->start_local), $this->localTime($date, $break->end_local));
                    }
                    foreach ($exceptions as $exception) {
                        if ($exception->practitioner_id !== null && $exception->practitioner_id !== $practitioner->id) {
                            continue;
                        }
                        if (in_array($exception->kind, ['closed', 'holiday'], true)) {
                            $segments = $this->subtract($segments, $exception->starts_at->toDateTimeImmutable(), $exception->ends_at->toDateTimeImmutable());
                        }
                    }
                    foreach ($segments as [$segmentStart, $segmentEnd]) {
                        $periods[] = ['start' => $segmentStart, 'end' => $segmentEnd];
                    }
                }
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
