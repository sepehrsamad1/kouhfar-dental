<?php

use App\Practitioner;
use App\ScheduleException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('allows the planned schedule exception fields', function () {
    expect((new ScheduleException)->getFillable())->toBe([
        'practitioner_id',
        'starts_at',
        'ends_at',
        'kind',
        'version',
    ]);
});

it('casts schedule exception timestamps as immutable', function () {
    expect((new ScheduleException)->getCasts())
        ->toMatchArray([
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'version' => 'integer',
        ]);
});

it('defines the practitioner relationship', function () {
    $relationship = (new ScheduleException)->practitioner();

    expect($relationship)
        ->toBeInstanceOf(BelongsTo::class)
        ->and($relationship->getForeignKeyName())->toBe('practitioner_id')
        ->and($relationship->getRelated())->toBeInstanceOf(Practitioner::class);
});

it('makes synthetic schedule exception data without saving it', function () {
    $exception = ScheduleException::factory()->make();

    expect($exception->exists)->toBeFalse()
        ->and($exception->practitioner_id)->toBeNull()
        ->and($exception->kind)->toBe('closed')
        ->and($exception->starts_at)->not->toBeNull()
        ->and($exception->ends_at)->not->toBeNull();
});
