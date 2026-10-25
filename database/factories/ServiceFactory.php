<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name_fa' => 'خدمت آزمایشی',
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'is_active' => false,
            'is_public_bookable' => false,
        ];
    }
}
