<?php

declare(strict_types=1);

namespace Database\Factories;

use App\DeletionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeletionRequest>
 */
class DeletionRequestFactory extends Factory
{
    protected $model = DeletionRequest::class;

    public function definition(): array
    {
        return [
            'target_type' => 'Patient',
            'target_id' => fake()->uuid(),
            'requested_by' => fake()->uuid(),
            'approved_by' => null,
            'status' => 'pending',
            'created_at' => now(),
        ];
    }
}
