<?php

use App\Enums\JobStatus;
use App\Job;

it('uses the explicit jobs table and planned field casts', function () {
    $job = new Job;

    expect($job->getTable())->toBe('jobs')
        ->and($job->usesTimestamps())->toBeFalse()
        ->and($job->getCasts())
        ->toMatchArray([
            'payload' => 'array',
            'status' => JobStatus::class,
            'attempts' => 'integer',
            'run_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
        ]);
});

it('defines the planned job statuses', function () {
    expect(array_column(JobStatus::cases(), 'value'))->toBe([
        'queued',
        'running',
        'done',
        'failed',
        'dead',
    ]);
});

it('makes synthetic job data without saving it', function () {
    $job = Job::factory()->make();

    expect($job->exists)->toBeFalse()
        ->and($job->type)->toBe('image_processing')
        ->and($job->status)->toBe(JobStatus::Queued)
        ->and($job->payload)->toBe(['test' => true])
        ->and($job->attempts)->toBe(0)
        ->and($job->locked_until)->toBeNull();
});
