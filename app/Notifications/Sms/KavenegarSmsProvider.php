<?php

declare(strict_types=1);

namespace App\Notifications\Sms;

use App\Notifications\SmsMessage;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class KavenegarSmsProvider implements SmsProvider
{
    public function send(SmsMessage $message): void
    {
        $apiKey = config('notifications.sms.kavenegar.api_key');
        $sender = config('notifications.sms.kavenegar.sender');
        $endpoint = rtrim((string) config('notifications.sms.kavenegar.endpoint'), '/');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($sender) || $sender === '') {
            throw new RuntimeException('Kavenegar is not configured; SMS delivery remains disabled.');
        }

        try {
            Http::asForm()
                ->timeout(10)
                ->retry(2, 250)
                ->post($endpoint.'/'.$apiKey.'/sms/send.json', [
                    'receptor' => $message->to,
                    'sender' => $sender,
                    'message' => $message->body,
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Kavenegar SMS delivery failed.', 0, $exception);
        }
    }
}
