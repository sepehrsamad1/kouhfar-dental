<?php

declare(strict_types=1);

use App\Domain\Availability\AvailabilityEngine;

it('generates five-minute aligned slots inside working hours and the booking horizon', function (): void {
    $engine = new AvailabilityEngine;
    $slots = $engine->slots([
        'timezone' => 'Asia/Tehran',
        'now' => new DateTimeImmutable('2026-10-27T06:00:00Z'),
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'minimum_notice_minutes' => 1440,
        'horizon_days' => 14,
        'grid_minutes' => 5,
        'practitioner_ids' => ['p1'],
        'resources' => [['id' => 'chair-1', 'kind' => 'dental_unit', 'active' => true]],
        'required_resource_kind' => 'dental_unit',
        'working_periods' => [[
            'start' => new DateTimeImmutable('2026-10-28T08:30:00Z'),
            'end' => new DateTimeImmutable('2026-10-28T16:30:00Z'),
        ]],
        'closed_dates' => [],
        'appointments' => [],
    ]);

    expect($slots)->not->toBeEmpty();
    foreach ($slots as $slot) {
        $local = new DateTimeImmutable($slot['display_local'], new DateTimeZone('Asia/Tehran'));
        expect((int) $local->format('i') % 5)->toBe(0)
            ->and(new DateTimeImmutable($slot['start_at']))->toBeInstanceOf(DateTimeImmutable::class);
    }
});

it('ignores expired pending holds but respects active pending and confirmed conflicts', function (): void {
    $engine = new AvailabilityEngine;
    $base = [
        'timezone' => 'Asia/Tehran',
        'now' => new DateTimeImmutable('2026-10-27T06:00:00Z'),
        'duration_minutes' => 30,
        'minimum_notice_minutes' => 0,
        'horizon_days' => 14,
        'grid_minutes' => 5,
        'practitioner_ids' => ['p1'],
        'resources' => [['id' => 'chair-1', 'kind' => 'dental_unit', 'active' => true]],
        'required_resource_kind' => 'dental_unit',
        'working_periods' => [[
            'start' => new DateTimeImmutable('2026-10-28T08:30:00Z'),
            'end' => new DateTimeImmutable('2026-10-28T09:00:00Z'),
        ]],
        'closed_dates' => [],
    ];

    $expired = $engine->slots($base + ['appointments' => [[
        'start' => new DateTimeImmutable('2026-10-28T08:30:00Z'),
        'end' => new DateTimeImmutable('2026-10-28T09:00:00Z'),
        'status' => 'pending',
        'expires_at' => new DateTimeImmutable('2026-10-27T05:59:59Z'),
        'practitioner_ids' => ['p1'],
        'resource_ids' => ['chair-1'],
    ]]]);
    $active = $engine->slots($base + ['appointments' => [[
        'start' => new DateTimeImmutable('2026-10-28T08:30:00Z'),
        'end' => new DateTimeImmutable('2026-10-28T09:00:00Z'),
        'status' => 'pending',
        'expires_at' => new DateTimeImmutable('2026-10-27T07:00:01Z'),
        'practitioner_ids' => ['p1'],
        'resource_ids' => ['chair-1'],
    ]]]);

    expect($expired)->not->toBeEmpty()
        ->and($active)->toBeEmpty();
});

it('does not offer slots on configured closure dates or when the full buffered interval does not fit', function (): void {
    $engine = new AvailabilityEngine;
    $input = [
        'timezone' => 'Asia/Tehran',
        'now' => new DateTimeImmutable('2026-10-27T06:00:00Z'),
        'duration_minutes' => 30,
        'buffer_before_minutes' => 10,
        'buffer_after_minutes' => 15,
        'minimum_notice_minutes' => 0,
        'horizon_days' => 14,
        'grid_minutes' => 5,
        'practitioner_ids' => ['p1'],
        'resources' => [['id' => 'chair-1', 'kind' => 'dental_unit', 'active' => true]],
        'required_resource_kind' => 'dental_unit',
        'working_periods' => [[
            'start' => new DateTimeImmutable('2026-10-28T08:30:00Z'),
            'end' => new DateTimeImmutable('2026-10-28T09:00:00Z'),
        ]],
        'closed_dates' => ['2026-10-28'],
        'appointments' => [],
    ];

    expect($engine->slots($input))->toBeEmpty();
    $input['closed_dates'] = [];
    expect($engine->slots($input))->toBeEmpty();
});

it('preserves slot invariants across multiple durations and buffer combinations', function (): void {
    $engine = new AvailabilityEngine;
    foreach ([[15, 0, 0], [25, 5, 10], [40, 10, 5], [60, 0, 15]] as [$duration, $before, $after]) {
        $periodStart = new DateTimeImmutable('2026-10-28T08:30:00Z');
        $periodEnd = new DateTimeImmutable('2026-10-28T12:30:00Z');
        $slots = $engine->slots([
            'timezone' => 'Asia/Tehran',
            'now' => new DateTimeImmutable('2026-10-27T06:00:00Z'),
            'duration_minutes' => $duration,
            'buffer_before_minutes' => $before,
            'buffer_after_minutes' => $after,
            'minimum_notice_minutes' => 1440,
            'horizon_days' => 14,
            'grid_minutes' => 5,
            'practitioner_ids' => ['p1'],
            'resources' => [['id' => 'chair-1', 'kind' => 'dental_unit', 'active' => true]],
            'required_resource_kind' => 'dental_unit',
            'working_periods' => [['start' => $periodStart, 'end' => $periodEnd]],
            'closed_dates' => [],
            'appointments' => [],
        ]);

        foreach ($slots as $slot) {
            $start = new DateTimeImmutable($slot['start_at']);
            $end = new DateTimeImmutable($slot['end_at']);
            $occupiedStart = $start->modify(sprintf('-%d minutes', $before));
            $occupiedEnd = $end->modify(sprintf('+%d minutes', $after));
            $local = $start->setTimezone(new DateTimeZone('Asia/Tehran'));

            expect($occupiedStart >= $periodStart)->toBeTrue()
                ->and($occupiedEnd <= $periodEnd)->toBeTrue()
                ->and((int) $local->format('i') % 5)->toBe(0)
                ->and($end > $start)->toBeTrue();
        }
    }
});

it('enforces the exact minimum notice and excludes dates beyond the booking horizon', function (): void {
    $engine = new AvailabilityEngine;
    $now = new DateTimeImmutable('2026-10-27T06:00:00Z');
    $base = [
        'timezone' => 'Asia/Tehran',
        'now' => $now,
        'duration_minutes' => 30,
        'minimum_notice_minutes' => 1440,
        'horizon_days' => 14,
        'grid_minutes' => 5,
        'practitioner_ids' => ['p1'],
        'resources' => [['id' => 'chair-1', 'kind' => 'dental_unit', 'active' => true]],
        'required_resource_kind' => 'dental_unit',
        'closed_dates' => [],
        'appointments' => [],
    ];

    $beforeNotice = $engine->slots($base + ['working_periods' => [[
        'start' => new DateTimeImmutable('2026-10-28T05:50:00Z'),
        'end' => new DateTimeImmutable('2026-10-28T05:55:00Z'),
    ]]]);
    $atNotice = $engine->slots($base + ['working_periods' => [[
        'start' => new DateTimeImmutable('2026-10-28T06:00:00Z'),
        'end' => new DateTimeImmutable('2026-10-28T06:30:00Z'),
    ]]]);
    $beyondHorizon = $engine->slots($base + ['working_periods' => [[
        'start' => new DateTimeImmutable('2026-11-11T08:30:00Z'),
        'end' => new DateTimeImmutable('2026-11-11T09:30:00Z'),
    ]]]);

    expect($beforeNotice)->toBeEmpty()
        ->and($atNotice)->not->toBeEmpty()
        ->and($beyondHorizon)->toBeEmpty();
});

it('blocks practitioner and shared-resource conflicts independently', function (): void {
    $engine = new AvailabilityEngine;
    $base = [
        'timezone' => 'Asia/Tehran',
        'now' => new DateTimeImmutable('2026-10-27T06:00:00Z'),
        'duration_minutes' => 30,
        'minimum_notice_minutes' => 0,
        'horizon_days' => 14,
        'grid_minutes' => 5,
        'practitioner_ids' => ['p1'],
        'resources' => [
            ['id' => 'chair-1', 'kind' => 'dental_unit', 'active' => true],
            ['id' => 'chair-2', 'kind' => 'dental_unit', 'active' => true],
        ],
        'required_resource_kind' => 'dental_unit',
        'working_periods' => [[
            'start' => new DateTimeImmutable('2026-10-28T08:30:00Z'),
            'end' => new DateTimeImmutable('2026-10-28T09:00:00Z'),
        ]],
        'closed_dates' => [],
    ];

    $resourceConflict = $engine->slots($base + ['appointments' => [[
        'start' => new DateTimeImmutable('2026-10-28T08:30:00Z'),
        'end' => new DateTimeImmutable('2026-10-28T09:00:00Z'),
        'status' => 'confirmed',
        'expires_at' => null,
        'practitioner_ids' => ['p2'],
        'resource_ids' => ['chair-1'],
    ]]]);

    $practitionerConflict = $engine->slots($base + ['appointments' => [[
        'start' => new DateTimeImmutable('2026-10-28T08:30:00Z'),
        'end' => new DateTimeImmutable('2026-10-28T09:00:00Z'),
        'status' => 'confirmed',
        'expires_at' => null,
        'practitioner_ids' => ['p1'],
        'resource_ids' => ['chair-2'],
    ]]]);

    expect($resourceConflict)->not->toBeEmpty()
        ->and(array_column($resourceConflict, 'resource_id'))->not->toContain('chair-1')
        ->and($practitionerConflict)->toBeEmpty();
});

it('keeps generated slots valid across randomized durations, buffers, and working periods', function (): void {
    mt_srand(13013);
    $engine = new AvailabilityEngine;

    for ($case = 0; $case < 50; $case++) {
        $duration = mt_rand(1, 12) * 5;
        $before = mt_rand(0, 3) * 5;
        $after = mt_rand(0, 3) * 5;
        $startMinute = mt_rand(0, 4) * 5;
        $periodStart = new DateTimeImmutable(sprintf('2026-10-28 09:%02d:00', $startMinute), new DateTimeZone('Asia/Tehran'));
        $periodEnd = $periodStart->modify('+8 hours');

        $slots = $engine->slots([
            'timezone' => 'Asia/Tehran',
            'now' => new DateTimeImmutable('2026-10-27T06:00:00Z'),
            'duration_minutes' => $duration,
            'buffer_before_minutes' => $before,
            'buffer_after_minutes' => $after,
            'minimum_notice_minutes' => 0,
            'horizon_days' => 14,
            'grid_minutes' => 5,
            'practitioner_ids' => ['p1'],
            'resources' => [['id' => 'chair-1', 'kind' => 'dental_unit', 'active' => true]],
            'required_resource_kind' => 'dental_unit',
            'working_periods' => [['start' => $periodStart, 'end' => $periodEnd]],
            'closed_dates' => [],
            'appointments' => [],
        ]);

        foreach ($slots as $slot) {
            $start = new DateTimeImmutable($slot['start_at']);
            $end = new DateTimeImmutable($slot['end_at']);
            $localStart = $start->setTimezone(new DateTimeZone('Asia/Tehran'));
            $occupiedStart = $start->modify(sprintf('-%d minutes', $before));
            $occupiedEnd = $end->modify(sprintf('+%d minutes', $after));

            expect($localStart->format('Y-m-d'))->toBe('2026-10-28')
                ->and((int) $localStart->format('i') % 5)->toBe(0)
                ->and($end > $start)->toBeTrue()
                ->and($occupiedStart >= $periodStart)->toBeTrue()
                ->and($occupiedEnd <= $periodEnd)->toBeTrue();
        }
    }
});

it('converts clinic-local wall time to UTC while preserving the separate local display value', function (): void {
    $engine = new AvailabilityEngine;
    $slots = $engine->slots([
        'timezone' => 'Asia/Tehran',
        'now' => new DateTimeImmutable('2026-10-27T20:30:00Z'),
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'minimum_notice_minutes' => 0,
        'horizon_days' => 14,
        'grid_minutes' => 5,
        'practitioner_ids' => ['p1'],
        'resources' => [['id' => 'chair-1', 'kind' => 'dental_unit', 'active' => true]],
        'required_resource_kind' => 'dental_unit',
        'working_periods' => [[
            'start' => new DateTimeImmutable('2026-10-28 09:00:00', new DateTimeZone('Asia/Tehran')),
            'end' => new DateTimeImmutable('2026-10-28 10:00:00', new DateTimeZone('Asia/Tehran')),
        ]],
        'closed_dates' => [],
        'appointments' => [],
    ]);

    expect($slots[0]['display_local'])->toBe('2026-10-28 09:00')
        ->and($slots[0]['start_at'])->toBe('2026-10-28T05:30:00Z')
        ->and($slots[0]['timezone'])->toBe('Asia/Tehran');
});

it('rejects cross-midnight working periods instead of inventing a next-day rule', function (): void {
    $engine = new AvailabilityEngine;

    $slots = $engine->slots([
        'timezone' => 'Asia/Tehran',
        'now' => new DateTimeImmutable('2026-10-27T06:00:00Z'),
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'minimum_notice_minutes' => 0,
        'horizon_days' => 14,
        'grid_minutes' => 5,
        'practitioner_ids' => ['p1'],
        'resources' => [['id' => 'chair-1', 'kind' => 'dental_unit', 'active' => true]],
        'required_resource_kind' => 'dental_unit',
        'working_periods' => [[
            'start' => new DateTimeImmutable('2026-10-28 23:30:00', new DateTimeZone('Asia/Tehran')),
            'end' => new DateTimeImmutable('2026-10-29 00:30:00', new DateTimeZone('Asia/Tehran')),
        ]],
        'closed_dates' => [],
        'appointments' => [],
    ]);

    expect($slots)->toBeEmpty();
});

it('records an engine-core benchmark for a 14-day, 1000-appointment workload', function (): void {
    $engine = new AvailabilityEngine;
    $timezone = new DateTimeZone('Asia/Tehran');
    $periods = [];
    $firstDay = new DateTimeImmutable('2026-10-28 09:00:00', $timezone);

    for ($day = 0; $day < 14; $day++) {
        $start = $firstDay->modify(sprintf('+%d days', $day));
        $periods[] = [
            'start' => $start,
            'end' => $start->setTime(17, 0),
        ];
    }

    $appointments = [];
    $appointmentBase = new DateTimeImmutable('2026-10-28T05:30:00Z');
    for ($i = 0; $i < 1000; $i++) {
        $start = $appointmentBase->modify(sprintf('+%d minutes', intdiv($i, 3) * 15));
        $appointments[] = [
            'start' => $start,
            'end' => $start->modify('+15 minutes'),
            'status' => 'confirmed',
            'expires_at' => null,
            'practitioner_ids' => ['p'.($i % 10)],
            'resource_ids' => ['r'.($i % 3)],
        ];
    }

    $input = [
        'timezone' => 'Asia/Tehran',
        'now' => new DateTimeImmutable('2026-10-27T06:00:00Z'),
        'duration_minutes' => 30,
        'buffer_before_minutes' => 0,
        'buffer_after_minutes' => 0,
        'minimum_notice_minutes' => 1440,
        'horizon_days' => 14,
        'grid_minutes' => 5,
        'practitioner_ids' => array_map(static fn (int $i): string => 'p'.$i, range(0, 9)),
        'resources' => array_map(static fn (int $i): array => [
            'id' => 'r'.$i,
            'kind' => 'dental_unit',
            'active' => true,
        ], range(0, 2)),
        'required_resource_kind' => 'dental_unit',
        'working_periods' => $periods,
        'closed_dates' => [],
        'appointments' => $appointments,
    ];

    $engine->slots($input);
    $durationsMs = [];
    $slots = [];
    for ($sample = 0; $sample < 10; $sample++) {
        $startedAt = hrtime(true);
        $slots = $engine->slots($input);
        $durationsMs[] = (hrtime(true) - $startedAt) / 1_000_000;
    }
    sort($durationsMs);
    $p95 = $durationsMs[(int) ceil(count($durationsMs) * 0.95) - 1];
    $p99 = $durationsMs[(int) ceil(count($durationsMs) * 0.99) - 1];

    fwrite(STDERR, sprintf(
        'Availability engine-core benchmark: 10 samples, 1000 appointments, 10 practitioners, 3 resources, 14 days; p95=%.2fms p99=%.2fms (CI runner; excludes database/repository overhead).%s',
        $p95,
        $p99,
        PHP_EOL
    ));

    expect($slots)->not->toBeEmpty();
});
