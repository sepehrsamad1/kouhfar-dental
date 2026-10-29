<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Appointment;
use App\Enums\AppointmentNotificationType;
use Illuminate\Notifications\Notification;

final class AppointmentSmsNotification extends Notification
{
    public function __construct(
        private readonly Appointment $appointment,
        private readonly AppointmentNotificationType $type,
    ) {}

    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        $patient = $this->appointment->patient;
        $localStart = $this->appointment->start_at->setTimezone('Asia/Tehran');
        $date = $localStart->format('Y/m/d');
        $time = $localStart->format('H:i');
        $practitioner = $this->appointment->practitioners->first()?->display_name_fa ?? 'پزشک کلینیک';

        $body = match ($this->type) {
            AppointmentNotificationType::Pending =>
                'درخواست رزرو شما ثبت شد. این درخواست تا ۱ ساعت در انتظار تأیید است و در صورت عدم تأیید منقضی می‌شود.',
            AppointmentNotificationType::Confirmed =>
                "نوبت شما تأیید شد: {$date} ساعت {$time}، {$practitioner}. آدرس: ".config('notifications.sms.clinic_address').' | '.config('notifications.sms.clinic_map_url'),
            AppointmentNotificationType::Reminder =>
                "یادآوری نوبت: فردا {$date} ساعت {$time}، {$practitioner}. لطفاً برای حضور به‌موقع برنامه‌ریزی کنید.",
            AppointmentNotificationType::Expired =>
                'مهلت ۱ ساعته درخواست رزرو شما به پایان رسید و زمان انتخاب‌شده آزاد شد. برای رزرو دوباره درخواست جدید ثبت کنید.',
            AppointmentNotificationType::Cancelled =>
                "نوبت شما لغو شد: {$date} ساعت {$time}. برای رزرو مجدد، درخواست جدید ثبت کنید.",
            AppointmentNotificationType::Rescheduled =>
                "نوبت شما جابه‌جا شد: {$date} ساعت {$time}، {$practitioner}. آدرس: ".config('notifications.sms.clinic_address').' | '.config('notifications.sms.clinic_map_url'),
        };

        return new SmsMessage(
            (string) $patient->mobile_normalized,
            $body,
            $this->type->value,
        );
    }
}
