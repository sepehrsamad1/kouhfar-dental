<?php

declare(strict_types=1);

namespace App\Notifications\Sms;

use App\Notifications\SmsMessage;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class KavenegarSmsProvider implements SmsProvider
{
    private const APPROVED_ENDPOINT = 'https://api.kavenegar.com/v1';

    public function send(SmsMessage $message): void
    {
        $apiKey = config('notifications.sms.kavenegar.api_key');
        $sender = config('notifications.sms.kavenegar.sender');
        $endpoint = rtrim((string) config('notifications.sms.kavenegar.endpoint'), '/');

        if (! is_string($apiKey) || $apiKey === '' || ! is_string($sender) || $sender === '') {
            throw new RuntimeException('Kavenegar is not configured; SMS delivery remains disabled.');
        }

        if (! $this->isApprovedEndpoint($endpoint)) {
            throw new RuntimeException('Kavenegar endpoint is not approved.');
        }

        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->asForm()
                ->timeout(10)
                ->retry(2, 250)
                ->post(self::APPROVED_ENDPOINT.'/'.$apiKey.'/sms/send.json', [
                    'receptor' => $message->to,
                    'sender' => $sender,
                    'message' => $message->body,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Kavenegar SMS delivery failed.');
            }
        } catch (Throwable) {
            // Do not chain transport exceptions: their request URL may contain the API key.
            throw new RuntimeException('Kavenegar SMS delivery failed.');
        }
    }

    private function isApprovedEndpoint(string $endpoint): bool
    {
        $parts = parse_url($endpoint);

        return $parts !== false
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 'api.kavenegar.com'
            && (! isset($parts['port']) || $parts['port'] === 443)
            && rtrim((string) ($parts['path'] ?? ''), '/') === '/v1'
            && ! isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment']);
    }
}
