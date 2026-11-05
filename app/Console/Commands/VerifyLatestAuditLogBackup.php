<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AuditLogBackupService;
use Illuminate\Console\Command;
use Throwable;

final class VerifyLatestAuditLogBackup extends Command
{
    protected $signature = 'audit-logs:verify-latest';

    protected $description = 'Restore the latest audit-log backup into an isolated temporary table and validate it.';

    public function handle(AuditLogBackupService $service): int
    {
        try {
            $result = $service->verifyLatestBackup();
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Audit-log backup verification failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Verified {$result['file']}: {$result['records']} audit-log record(s) restored into a temporary table and validated.");

        return self::SUCCESS;
    }
}
