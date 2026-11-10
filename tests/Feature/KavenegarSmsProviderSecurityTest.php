<?php

declare(strict_types=1);

use App\Notifications\Sms\KavenegarSmsProvider;
use App\Notifications\SmsMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

beforeEach(function (): void {
    config()->set('notifications.sms.kavenegar.api_key', 'synthetic-api-key');
    config()->set('notifications.sms.kavenegar.sender', 'synthetic-sender');
    config()->set('notifications.sms.kavenegar.endpoint', 'https://api.kavenegar.com/v1');
});

it('rejects Kavenegar endpoints outside the approved HTTPS origin and path', function (string $endpoint): void {
    config()->set('notifications.sms.kavenegar.endpoint', $endpoint);
    Http::fake();

    expect(fn () => (new KavenegarSmsProvider)->send(
        new SmsMessage('09120000000', 'Synthetic test message', 'test')
    ))->toThrow(RuntimeException::class, 'Kavenegar endpoint is not approved.');

    Http::assertNothingSent();
})->with([
    'plain HTTP' => 'http://api.kavenegar.com/v1',
    'unapproved host' => 'https://example.invalid/v1',
    'cloud metadata address' => 'http://169.254.169.254/v1',
    'unapproved port' => 'https://api.kavenegar.com:8443/v1',
    'unapproved path' => 'https://api.kavenegar.com/other',
    'userinfo' => 'https://user:pass@api.kavenegar.com/v1',
    'query string' => 'https://api.kavenegar.com/v1?next=https://127.0.0.1',
    'fragment' => 'https://api.kavenegar.com/v1#internal',
]);

it('sends only to the fixed approved Kavenegar URL', function (): void {
    Http::fake(fn () => Http::response(['return' => ['status' => 200]], 200));

    (new KavenegarSmsProvider)->send(
        new SmsMessage('09120000000', 'Synthetic test message', 'test')
    );

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kavenegar.com/v1/synthetic-api-key/sms/send.json'
        && $request['receptor'] === '09120000000'
        && $request['sender'] === 'synthetic-sender'
        && $request['message'] === 'Synthetic test message');
    Http::assertSentCount(1);
});

it('does not follow provider redirects to another host', function (): void {
    Http::fake(fn () => Http::response('', 302, [
        'Location' => 'http://169.254.169.254/latest/meta-data',
    ]));

    expect(fn () => (new KavenegarSmsProvider)->send(
        new SmsMessage('09120000000', 'Synthetic test message', 'test')
    ))->toThrow(RuntimeException::class, 'Kavenegar SMS delivery failed.');

    Http::assertSentCount(1);
});

it('does not chain transport exceptions that could expose the API key URL', function (): void {
    Http::fake(fn () => throw new ConnectionException('synthetic transport failure'));

    try {
        (new KavenegarSmsProvider)->send(
            new SmsMessage('09120000000', 'Synthetic test message', 'test')
        );
        test()->fail('Expected SMS delivery to fail.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Kavenegar SMS delivery failed.')
            ->and($exception->getPrevious())->toBeNull();
    }
});
