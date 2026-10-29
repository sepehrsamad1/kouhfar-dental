<?php

declare(strict_types=1);

namespace App\Notifications\Sms;

use App\Notifications\SmsMessage;

final class NullSmsProvider implements SmsProvider
{
    public function send(SmsMessage $message): void {}
}
