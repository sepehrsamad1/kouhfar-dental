<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Notifications\Sms\SmsProvider;
use App\Notifications\SmsMessage;
use RuntimeException;

final class FakeSmsProvider implements SmsProvider
{
    /** @var list<SmsMessage> */
    public array $messages = [];

    public bool $fail = false;

    public function send(SmsMessage $message): void
    {
        if ($this->fail) {
            throw new RuntimeException('Synthetic SMS provider failure.');
        }

        if ($message->to === '' || $message->body === '' || $message->type === '') {
            throw new RuntimeException('Invalid SMS payload.');
        }

        $this->messages[] = $message;
    }
}
