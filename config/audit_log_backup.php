<?php

declare(strict_types=1);

return [
    'path' => env('AUDIT_LOG_BACKUP_PATH'),
    'encryption_key' => env('AUDIT_LOG_BACKUP_ENCRYPTION_KEY'),
    'retention_days' => (int) env('AUDIT_LOG_BACKUP_RETENTION_DAYS', 90),
    'chunk_bytes' => (int) env('AUDIT_LOG_BACKUP_CHUNK_BYTES', 1048576),
];
