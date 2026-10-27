<?php

declare(strict_types=1);

namespace App\Domain\Availability;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Pure slot calculator. Inputs are normalized values; this class never reads the database.
 *
 * Expected period: ['start' => DateTimeImmutable, 'end' => DateTimeImmutable]
 * Expected appointment: ['start' => DateTimeImmutable, 'end' => DateTimeImmutable,
 *   'status' => string, 'expires_at' => ?DateTimeImmutable,
 *   'practitioner_ids' => string[], 'resource_ids' => string[]]
 */
final class AvailabilityEngine
{
    public function slots(array $input): array
    {
        $timezone = new DateTimeZone((string) ($input['timezone'] ?? 'Asia/Tehran'));
        $now = $this->asUtc($input['now'] ?? null);
        $duration = (int) ($input['duration_minutes'] ?? 0);
        $before = (int) ($input['buffer_before_minutes'] ?? 0);
        $after = (int) ($input['buffer_after_minutes'] ?? 0);
        $grid = (int) ($input['grid_minutes'] ?? 5);
        $notice = (int) ($input['minimum_notice_minutes'] ?? 1440);
        $horizon = (int) ($input['horizon_days'] ?? 14);
        $eligiblePractitioners = array_values(array_unique($input['practitioner_ids'] ?? []));
        $resources = $input['resources'] ?? [];
        $periods = $input['working_periods'] ?? [];
        $exceptions = $input['closed_dates'] ?? [];
        $appointments = $input['appointments'] ?? [];
        $occupancy = $this->buildOccupancyIndex($appointments, $now);

        if ($duration < 1 || $before < 0 || $after < 0 || $grid < 1 || $notice < 0 || $horizon < 1) {
            throw new InvalidArgumentException('Invalid duration, buffer, grid, notice, or horizon.');
        }

        $localNow = $now->setTimezone($timezone);
        $horizonEnd = $localNow->setTime(0, 0)->modify(sprintf('+%d days', $horizon + 1));
        $earliest = $now->modify(sprintf('+%d minutes', $notice));
        $results = [];

        foreach ($periods as $period) {
            $periodStart = $this->asUtc($period['start'])->setTimezone($timezone);
            $periodEnd = $this->asUtc($period['end'])->setTimezone($timezone);
            if ($periodEnd <= $periodStart || $periodStart->format('Y-m-d') !== $periodEnd->format('Y-m-d')) {
                continue;
            }
            if (in_array($periodStart->format('Y-m-d'), $exceptions, true)) {
                continue;
            }

            $dayStart = $periodStart->setTime(0, 0);
            $first = $dayStart->setTime((int) $periodStart->format('H'), (int) $periodStart->format('i'), 0);
            $minute = (int) $first->format('i');
            $remainder = $minute % $grid;
            if ($remainder !== 0) {
                $first = $first->modify(sprintf('+%d minutes', $grid - $remainder));
            }

            for ($start = $first; $start < $periodEnd && $start < $horizonEnd; $start = $start->modify(sprintf('+%d minutes', $grid))) {
                $end = $start->modify(sprintf('+%d minutes', $duration));
                $occupiedStart = $start->modify(sprintf('-%d minutes', $before));
                $occupiedEnd = $end->modify(sprintf('+%d minutes', $after));

                if ($start->setTimezone(new DateTimeZone('UTC')) < $earliest
                    || $start->setTimezone(new DateTimeZone('UTC')) >= $horizonEnd->setTimezone(new DateTimeZone('UTC'))
                    || $occupiedStart < $periodStart
                    || $occupiedEnd > $periodEnd
                    || $end > $horizonEnd) {
                    continue;
                }

                foreach ($eligiblePractitioners as $practitionerId) {
                    if ($this->hasConflict($occupiedStart, $occupiedEnd, $occupancy['practitioners'][(string) $practitionerId] ?? [])) {
                        continue;
                    }

                    foreach ($resources as $resource) {
                        if (($resource['active'] ?? true) !== true) {
                            continue;
                        }
                        if (isset($input['required_resource_kind']) && $resource['kind'] !== $input['required_resource_kind']) {
                            continue;
                        }
                        if ($this->hasConflict($occupiedStart, $occupiedEnd, $occupancy['resources'][(string) $resource['id']] ?? [])) {
                            continue;
                        }

                        $startUtc = $start->setTimezone(new DateTimeZone('UTC'));
                        $endUtc = $end->setTimezone(new DateTimeZone('UTC'));
                        $results[] = [
                            'start_at' => $startUtc->format('Y-m-d\TH:i:s\Z'),
                            'end_at' => $endUtc->format('Y-m-d\TH:i:s\Z'),
                            'display_local' => $start->format('Y-m-d H:i'),
                            'timezone' => $timezone->getName(),
                            'practitioner_id' => (string) $practitionerId,
                            'resource_id' => (string) $resource['id'],
                        ];
                    }
                }
            }
        }

        usort($results, static fn (array $a, array $b): int => [$a['start_at'], $a['practitioner_id'], $a['resource_id']] <=> [$b['start_at'], $b['practitioner_id'], $b['resource_id']]);

        return $results;
    }

    /**
     * Index active appointment occupancy once so each candidate checks only the
     * intervals for its practitioner and resource, not every appointment in the clinic.
     *
     * @return array{practitioners: array<string, list<array{start: DateTimeImmutable, end: DateTimeImmutable}>>, resources: array<string, list<array{start: DateTimeImmutable, end: DateTimeImmutable}>>}
     */
    private function buildOccupancyIndex(array $appointments, DateTimeImmutable $now): array
    {
        $index = ['practitioners' => [], 'resources' => []];

        foreach ($appointments as $appointment) {
            $status = (string) ($appointment['status'] ?? '');
            if ($status === 'pending') {
                $expiry = $appointment['expires_at'] ?? null;
                if (! $expiry instanceof DateTimeImmutable || $expiry <= $now) {
                    continue;
                }
            } elseif ($status !== 'confirmed') {
                continue;
            }

            $interval = [
                'start' => $this->asUtc($appointment['start']),
                'end' => $this->asUtc($appointment['end']),
            ];

            foreach ($appointment['practitioner_ids'] ?? [] as $practitionerId) {
                $index['practitioners'][(string) $practitionerId][] = $interval;
            }
            foreach ($appointment['resource_ids'] ?? [] as $resourceId) {
                $index['resources'][(string) $resourceId][] = $interval;
            }
        }

        foreach (['practitioners', 'resources'] as $kind) {
            foreach ($index[$kind] as $key => $intervals) {
                usort($intervals, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);
                $index[$kind][$key] = $intervals;
            }
        }

        return $index;
    }

    /**
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable}> $intervals
     */
    private function hasConflict(DateTimeImmutable $start, DateTimeImmutable $end, array $intervals): bool
    {
        foreach ($intervals as $interval) {
            if ($interval['start'] >= $end) {
                break;
            }
            if ($interval['end'] > $start) {
                return true;
            }
        }

        return false;
    }

    private function asUtc(mixed $value): DateTimeImmutable
    {
        if (! $value instanceof DateTimeImmutable && ! is_string($value)) {
            throw new InvalidArgumentException('Date/time values must be strings or DateTimeImmutable instances.');
        }

        return ($value instanceof DateTimeImmutable ? $value : new DateTimeImmutable($value))
            ->setTimezone(new DateTimeZone('UTC'));
    }
}
