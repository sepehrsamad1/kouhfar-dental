<?php

declare(strict_types=1);

use App\Domain\Availability\AvailabilityEngine;

it('generates five-minute aligned slots inside working hours and the booking horizon', function (): void {
    $engine = new AvailabilityEngine();
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
    $engine = new AvailabilityEngine();
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
        'expires_at' => new DateTimeImmutable('2026-10-27T06:59:59Z'),
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
    $engine = new AvailabilityEngine();
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
    $engine = new AvailabilityEngine();
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
    $engine = new AvailabilityEngine();
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
        'start' => new DateTimeImmutable('2026-10-28T05:55:00Z'),
        'end' => new DateTimeImmutable('2026-10-28T06:30:00Z'),
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
