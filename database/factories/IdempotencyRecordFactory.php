<?php

declare(strict_types=1);

namespace Database\Factories;

use App\IdempotencyRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IdempotencyRecord>
 */
class IdempotencyRecordFactory extends Factory
{
    protected $model = IdempotencyRecord::class;

    public function definition(): array
    {
        $createdAt = now('UTC')->toImmutable();

        return [
            'owner_scope' => 'synthetic-test-only',
            'key' => (string) Str::uuid(),
            'request_hash' => hash('sha256', 'synthetic-test-request'),
            'response_status' => 200,
            'response_body' => ['test' => true],
            'created_at' => $createdAt,
            'expires_at' => $createdAt->addHour(),
        ];
    }
}
