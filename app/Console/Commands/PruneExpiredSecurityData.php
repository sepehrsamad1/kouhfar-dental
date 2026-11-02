<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PruneExpiredSecurityData extends Command
{
    protected $signature = 'security:prune-expired-data';

    protected $description = 'Prune security and operational records according to approved retention periods.';

    public function handle(): int
    {
        $now = now('UTC');
        $counts = [];

        if (Schema::hasTable('login_attempts')) {
            $counts['login attempts'] = DB::table('login_attempts')
                ->where('created_at', '<', $now->copy()->subDays(90))
                ->delete();
        }

        if (Schema::hasTable('ai_requests')) {
            $counts['AI requests'] = DB::table('ai_requests')
                ->where('created_at', '<', $now->copy()->subMonthsNoOverflow(12))
                ->delete();
        }

        if (Schema::hasTable('ai_usage')) {
            $counts['AI daily usage'] = DB::table('ai_usage')
                ->where('day', '<', $now->copy()->subMonthsNoOverflow(12)->toDateString())
                ->delete();
        }

        if (Schema::hasTable('idempotency_records')) {
            $counts['expired idempotency records'] = DB::table('idempotency_records')
                ->where('expires_at', '<=', $now)
                ->delete();
        }

        foreach ($counts as $label => $count) {
            $this->info("Pruned {$count} {$label} record(s).");
        }

        return self::SUCCESS;
    }
}
