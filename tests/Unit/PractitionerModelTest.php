<?php

use App\Practitioner;

it('allows the approved practitioner fields for mass assignment', function () {
    expect((new Practitioner)->getFillable())->toBe([
        'display_name_fa',
        'is_active',
        'version',
    ]);
});

it('casts the active flag to boolean', function () {
    expect((new Practitioner)->getCasts())->toMatchArray(['is_active' => 'boolean', 'version' => 'integer']);
});

it('makes synthetic inactive practitioner data without saving it', function () {
    $practitioner = Practitioner::factory()->make();

    expect($practitioner->exists)->toBeFalse()
        ->and($practitioner->display_name_fa)->toBe('پزشک آزمایشی')
        ->and($practitioner->is_active)->toBeFalse();
});
