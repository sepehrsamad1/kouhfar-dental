<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<resource>
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'name_fa' => 'منبع آزمایشی',
            'kind' => 'test_resource',
            'is_active' => false,
        ];
    }
}
