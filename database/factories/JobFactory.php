<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'type' => 'image_processing',
            'payload' => ['test' => true],
            'status' => 'queued',
            'attempts' => 0,
            'run_at' => now('UTC')->toImmutable(),
            'locked_until' => null,
        ];
    }
}
