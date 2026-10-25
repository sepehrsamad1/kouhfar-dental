<?php

use App\Enums\AppointmentStatus;

it('defines the supported appointment statuses', function () {
    expect(array_column(AppointmentStatus::cases(), 'value'))->toBe([
        'pending',
        'confirmed',
        'completed',
        'cancelled',
        'no_show',
        'expired',
    ]);
});

it('rejects an unsupported appointment status', function () {
    expect(AppointmentStatus::tryFrom('unknown'))->toBeNull();
});
