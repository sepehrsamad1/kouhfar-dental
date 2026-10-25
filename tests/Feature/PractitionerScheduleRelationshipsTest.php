<?php

use App\Practitioner;
use App\ScheduleException;
use App\WorkingHour;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines practitioner working hour and schedule exception relationships', function () {
    $practitioner = new Practitioner;
    $workingHours = $practitioner->workingHours();
    $scheduleExceptions = $practitioner->scheduleExceptions();

    expect($workingHours)
        ->toBeInstanceOf(HasMany::class)
        ->and($workingHours->getForeignKeyName())->toBe('practitioner_id')
        ->and($workingHours->getRelated())->toBeInstanceOf(WorkingHour::class)
        ->and($scheduleExceptions)
        ->toBeInstanceOf(HasMany::class)
        ->and($scheduleExceptions->getForeignKeyName())->toBe('practitioner_id')
        ->and($scheduleExceptions->getRelated())->toBeInstanceOf(ScheduleException::class);
});
