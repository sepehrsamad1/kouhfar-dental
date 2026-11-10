<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AuditLogBackupService;
use Illuminate\Console\Command;
use Throwable;

final class BackupAuditLogs extends Command
{
    protected $signature = 'audit-logs:backup';

    protected $description = 'Create an encrypted audit-log backup on configured separate storage.';

    public function handle(AuditLogBackupService $service): int
    {
        try {
            $result = $service->createBackup();
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Audit-log backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Created {$result['file']} with {$result['records']} record(s); pruned {$result['pruned']} expired backup(s).");

        return self::SUCCESS;
    }
}
