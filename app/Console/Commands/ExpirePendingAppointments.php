<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Booking\AppointmentStateMachine;
use Illuminate\Console\Command;

final class ExpirePendingAppointments extends Command
{
    protected $signature = 'appointments:expire-pending';

    protected $description = 'Expire pending booking holds that reached their one-hour deadline.';

    public function handle(AppointmentStateMachine $states): int
    {
        $count = $states->expirePendingHolds();

        $this->info("Expired {$count} pending appointment hold(s).");

        return self::SUCCESS;
    }
}
