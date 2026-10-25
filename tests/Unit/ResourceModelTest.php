<?php

use App\Resource;

it('allows the approved resource fields for mass assignment', function () {
    expect((new Resource)->getFillable())->toBe([
        'name_fa',
        'kind',
        'is_active',
    ]);
});

it('casts the active flag to boolean', function () {
    expect((new Resource)->getCasts()['is_active'])->toBe('boolean');
});

it('makes synthetic inactive resource data without saving it', function () {
    $resource = Resource::factory()->make();

    expect($resource->exists)->toBeFalse()
        ->and($resource->name_fa)->toBe('منبع آزمایشی')
        ->and($resource->kind)->toBe('test_resource')
        ->and($resource->is_active)->toBeFalse();
});
