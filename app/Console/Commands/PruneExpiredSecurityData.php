<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PruneExpiredSecurityData extends Command
{
    protected $signature = 'security:prune-expired-data';

    protected $description = 'Prune security and operational records according to documented prototype retention defaults.';

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

        if (Schema::hasTable('smart_smile_sessions')) {
            $counts['expired Smart Smile results'] = DB::table('smart_smile_sessions')
                ->whereNotNull('result_expires_at')
                ->where('result_expires_at', '<=', $now)
                ->whereNotNull('result')
                ->update([
                    'result' => null,
                    'result_expires_at' => null,
                    'updated_at' => $now,
                ]);
        }

        if (Schema::hasTable('booking_otp_challenges')) {
            $counts['terminal booking OTP challenges'] = DB::table('booking_otp_challenges')
                ->whereIn('status', ['consumed', 'expired', 'exhausted', 'invalidated', 'delivery_failed'])
                ->where('created_at', '<', $now->copy()->subDays(30))
                ->delete();
        }

        if (Schema::hasTable('deletion_requests')
            && Schema::hasColumn('deletion_requests', 'reviewed_at')
            && Schema::hasColumn('deletion_requests', 'resolved_at')) {
            $counts['closed deletion requests'] = DB::table('deletion_requests')
                ->where('resolution_code', '!=', 'legal_hold')
                ->where(function ($query) use ($now): void {
                    $query->where(function ($closed) use ($now): void {
                        $closed->where('status', 'closed')
                            ->whereNotNull('resolved_at')
                            ->where('resolved_at', '<', $now->copy()->subYears(3));
                    })->orWhere(function ($rejected) use ($now): void {
                        $rejected->where('status', 'rejected')
                            ->whereNotNull('reviewed_at')
                            ->where('reviewed_at', '<', $now->copy()->subYears(3));
                    });
                })
                ->delete();
        }

        if (Schema::hasTable('completed_job_history')) {
            $counts['completed job history'] = DB::table('completed_job_history')
                ->where('completed_at', '<', $now->copy()->subDays(7))
                ->delete();
        }

        if (Schema::hasTable('failed_jobs')) {
            $counts['failed queue jobs'] = DB::table('failed_jobs')
                ->where('failed_at', '<', $now->copy()->subDays(30))
                ->delete();
        }

        if (Schema::hasTable('notification_outbox')) {
            if (Schema::hasColumn('notification_outbox', 'sent_at')) {
                $counts['sent notification outbox rows'] = DB::table('notification_outbox')
                    ->whereNotNull('sent_at')
                    ->where('sent_at', '<', $now->copy()->subMonthsNoOverflow(12))
                    ->delete();
            }

            if (Schema::hasColumn('notification_outbox', 'last_error')
                && Schema::hasColumn('notification_outbox', 'last_attempt_at')) {
                $counts['stale failed notification outbox rows'] = DB::table('notification_outbox')
                    ->whereNotNull('last_error')
                    ->whereNull('sent_at')
                    ->whereNotNull('last_attempt_at')
                    ->where('last_attempt_at', '<', $now->copy()->subDays(30))
                    ->delete();
            }
        }

        foreach ($counts as $label => $count) {
            $this->info("Pruned {$count} {$label} record(s).");
        }

        return self::SUCCESS;
    }
}
