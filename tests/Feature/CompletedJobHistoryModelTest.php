<?php

declare(strict_types=1);

use App\CompletedJobHistory;

it('stores only metadata for completed queue jobs', function (): void {
    $history = new CompletedJobHistory;

    expect($history->getTable())->toBe('completed_job_history')
        ->and($history->usesTimestamps())->toBeFalse()
        ->and($history->getFillable())->toBe([
            'job_class',
            'queue_name',
            'attempts',
            'completed_at',
        ])
        ->and($history->getCasts())->toMatchArray([
            'attempts' => 'integer',
            'completed_at' => 'immutable_datetime',
        ]);
});
