<?php

declare(strict_types=1);

namespace App\Notifications;

final readonly class SmsMessage
{
    public function __construct(
        public string $to,
        public string $body,
        public string $type,
    ) {}
}
