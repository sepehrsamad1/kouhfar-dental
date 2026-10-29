<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendAppointmentNotification;
use App\NotificationOutbox;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class DispatchDueNotifications extends Command
{
    protected $signature = 'notifications:dispatch-due';

    protected $description = 'Dispatch due appointment notification outbox entries without bypassing approved quiet hours.';

    public function handle(): int
    {
        $now = CarbonImmutable::now('UTC');
        $timezone = (string) config('notifications.sms.timezone', 'Asia/Tehran');
        $localNow = $now->setTimezone($timezone);

        $count = 0;

        NotificationOutbox::query()
            ->whereNull('sent_at')
            ->where('scheduled_at', '<=', $now)
            ->orderBy('scheduled_at')
            ->limit(200)
            ->get()
            ->each(function (NotificationOutbox $outbox) use ($localNow, &$count): void {
                if ($outbox->type->isAutomated() && $this->isQuietHour($localNow)) {
                    $outbox->update(['scheduled_at' => $this->nextRelease($localNow)->setTimezone('UTC')]);

                    return;
                }

                SendAppointmentNotification::dispatch($outbox->getKey());
                $count++;
            });

        $this->info("Dispatched {$count} notification job(s).");

        return self::SUCCESS;
    }

    private function isQuietHour(CarbonImmutable $local): bool
    {
        $minutes = ((int) $local->format('H') * 60) + (int) $local->format('i');

        return $minutes >= 22 * 60 || $minutes < 8 * 60 + 1;
    }

    private function nextRelease(CarbonImmutable $local): CarbonImmutable
    {
        $release = $local->setTime(8, 1);

        if ($local->hour >= 22) {
            $release = $release->addDay();
        }

        return $release;
    }
}
