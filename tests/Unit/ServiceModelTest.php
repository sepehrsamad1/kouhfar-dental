<?php

use App\Service;

it('allows the approved service fields for mass assignment', function () {
    expect((new Service)->getFillable())->toBe([
        'name_fa',
        'duration_minutes',
        'buffer_before_minutes',
        'buffer_after_minutes',
        'is_active',
        'is_public_bookable',
    ]);
});

it('casts service configuration fields', function () {
    expect((new Service)->getCasts())
        ->toMatchArray([
            'duration_minutes' => 'integer',
            'buffer_before_minutes' => 'integer',
            'buffer_after_minutes' => 'integer',
            'is_active' => 'boolean',
            'is_public_bookable' => 'boolean',
        ]);
});

it('makes synthetic service data without saving it', function () {
    $service = Service::factory()->make();

    expect($service->exists)->toBeFalse()
        ->and($service->name_fa)->toBe('خدمت آزمایشی')
        ->and($service->is_active)->toBeFalse()
        ->and($service->is_public_bookable)->toBeFalse();
});
