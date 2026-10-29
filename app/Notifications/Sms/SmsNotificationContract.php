<?php

declare(strict_types=1);

namespace App\Notifications\Sms;

use App\Notifications\SmsMessage;

interface SmsNotificationContract
{
    public function toSms(object $notifiable): SmsMessage;
}
