<?php

declare(strict_types=1);

namespace App\Notifications\Sms;

use App\Notifications\SmsMessage;
use Illuminate\Support\Facades\Log;

final class LogSmsProvider implements SmsProvider
{
    public function send(SmsMessage $message): void
    {
        Log::info('sms.notification.dispatched', [
            'type' => $message->type,
            'recipient_hash' => hash('sha256', $message->to),
            'body_sha256' => hash('sha256', $message->body),
            'body_length' => mb_strlen($message->body),
        ]);
    }
}
