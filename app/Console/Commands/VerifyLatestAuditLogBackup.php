<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AuditLogBackupService;
use Illuminate\Console\Command;
use Throwable;

final class VerifyLatestAuditLogBackup extends Command
{
    protected $signature = 'audit-logs:verify-latest';

    protected $description = 'Decrypt and validate the latest audit-log backup in an isolated streaming verification.';

    public function handle(AuditLogBackupService $service): int
    {
        try {
            $result = $service->verifyLatestBackup();
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Audit-log backup verification failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Verified {$result['file']}: {$result['records']} audit-log record(s) authenticated and validated.");

        return self::SUCCESS;
    }
}
