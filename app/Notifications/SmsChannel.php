<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Sms\SmsNotificationContract;
use App\Notifications\Sms\SmsProvider;
use Illuminate\Notifications\Notification;
use LogicException;

final class SmsChannel
{
    public function __construct(private readonly SmsProvider $provider)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof SmsNotificationContract) {
            throw new LogicException('SMS notifications must implement SmsNotificationContract.');
        }

        $message = $notification->toSms($notifiable);

        if (! $message instanceof SmsMessage) {
            throw new LogicException('SMS notifications must return an SmsMessage.');
        }

        $this->provider->send($message);
    }
}
