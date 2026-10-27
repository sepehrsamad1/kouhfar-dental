<?php

use App\Practitioner;
use App\WorkingHour;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('allows the planned working hour fields', function () {
    expect((new WorkingHour)->getFillable())->toBe([
        'practitioner_id',
        'weekday',
        'start_local',
        'end_local',
        'version',
    ]);
});

it('casts weekday as an integer', function () {
    expect((new WorkingHour)->getCasts())->toMatchArray(['weekday' => 'integer', 'version' => 'integer']);
});

it('defines the practitioner relationship', function () {
    $relationship = (new WorkingHour)->practitioner();

    expect($relationship)
        ->toBeInstanceOf(BelongsTo::class)
        ->and($relationship->getForeignKeyName())->toBe('practitioner_id')
        ->and($relationship->getRelated())->toBeInstanceOf(Practitioner::class);
});

it('makes synthetic working hour data without saving it', function () {
    $workingHour = WorkingHour::factory()->make();

    expect($workingHour->exists)->toBeFalse()
        ->and($workingHour->practitioner_id)->toBeNull()
        ->and($workingHour->start_local)->toBe('09:00')
        ->and($workingHour->end_local)->toBe('10:00');
});
