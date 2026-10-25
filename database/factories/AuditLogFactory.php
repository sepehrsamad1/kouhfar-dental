<?php

declare(strict_types=1);

namespace Database\Factories;

use App\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'actor_id' => null,
            'action' => 'test.event',
            'target_type' => 'TestRecord',
            'target_id' => (string) Str::uuid(),
            'occurred_at' => now('UTC')->toImmutable(),
            'request_id' => (string) Str::uuid(),
            'meta' => null,
        ];
    }
}
