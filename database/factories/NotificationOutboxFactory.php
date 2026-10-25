<?php

declare(strict_types=1);

namespace Database\Factories;

use App\NotificationOutbox;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationOutbox>
 */
class NotificationOutboxFactory extends Factory
{
    protected $model = NotificationOutbox::class;

    public function definition(): array
    {
        return [
            'channel' => 'sms',
            'recipient_hash' => hash('sha256', fake()->uuid()),
            'template' => 'synthetic-test-only',
            'payload_ref' => 'synthetic-test-only',
            'status' => 'synthetic-test-only',
            'attempts' => 0,
            'created_at' => now(),
            'sent_at' => null,
        ];
    }
}
