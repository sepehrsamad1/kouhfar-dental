<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class AuditLogBackupService
{
    private const MAGIC = 'KFAUDIT1';

    private const NONCE_BYTES = 12;

    private const TAG_BYTES = 16;

    /** @return array{file: string, records: int, pruned: int} */
    public function createBackup(): array
    {
        $root = $this->backupRoot();
        $key = $this->encryptionKey();
        $chunkBytes = $this->chunkBytes();
        $retentionDays = $this->retentionDays();

        $filename = 'audit-logs-'.now('UTC')->format('Ymd\THis\Z').'-'.bin2hex(random_bytes(8)).'.kfa';
        $finalPath = $root.DIRECTORY_SEPARATOR.$filename;
        $partPath = $finalPath.'.part';
        $output = fopen($partPath, 'xb');

        if ($output === false) {
            throw new RuntimeException('Could not create the encrypted audit-log backup file.');
        }

        @chmod($partPath, 0600);

        try {
            $this->writeAll($output, self::MAGIC);
            $connection = DB::connection();
            $outerTransactionLevel = $connection->transactionLevel();
            $connection->beginTransaction();
            $recordCount = 0;
            $sequence = 0;
            $buffer = '';

            try {
                // A repeatable-read snapshot prevents rows inserted during export from
                // being skipped by UUID keyset pagination on PostgreSQL.
                if ($outerTransactionLevel === 0 && $connection->getDriverName() === 'pgsql') {
                    $connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
                }

                $connection->table('audit_logs')
                    ->select(['id', 'actor_id', 'action', 'target_type', 'target_id', 'occurred_at', 'request_id', 'meta'])
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) use (
                        $output,
                        $key,
                        $chunkBytes,
                        &$buffer,
                        &$sequence,
                        &$recordCount
                    ): void {
                        foreach ($rows as $row) {
                            $buffer .= json_encode(
                                (array) $row,
                                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            )."\n";

                            while (strlen($buffer) >= $chunkBytes) {
                                $chunk = substr($buffer, 0, $chunkBytes);
                                $buffer = substr($buffer, $chunkBytes);
                                $this->writeEncryptedFrame($output, $chunk, $key, $sequence++);
                            }

                            $recordCount++;
                        }
                    }, 'id');

                if ($buffer !== '') {
                    $this->writeEncryptedFrame($output, $buffer, $key, $sequence++);
                }

                // Authenticated empty frame marks the exact end and detects truncation.
                $this->writeEncryptedFrame($output, '', $key, $sequence);
                $connection->commit();
            } catch (Throwable $exception) {
                $connection->rollBack();
                throw $exception;
            }

            if (! fclose($output)) {
                $output = null;
                throw new RuntimeException('Could not finalize the encrypted audit-log backup.');
            }

            $output = null;

            if (! rename($partPath, $finalPath)) {
                throw new RuntimeException('Could not atomically publish the encrypted audit-log backup.');
            }

            @chmod($finalPath, 0600);
            $pruned = $this->pruneExpiredBackups($root, $retentionDays);

            return ['file' => $filename, 'records' => $recordCount, 'pruned' => $pruned];
        } catch (Throwable $exception) {
            if (is_resource($output)) {
                fclose($output);
            }

            if (is_file($partPath)) {
                @unlink($partPath);
            }

            throw $exception;
        }
    }

    /** @return array{file: string, records: int} */
    public function verifyLatestBackup(): array
    {
        $root = $this->backupRoot();
        $files = glob($root.DIRECTORY_SEPARATOR.'audit-logs-*.kfa') ?: [];

        if ($files === []) {
            throw new RuntimeException('No audit-log backup is available to verify.');
        }

        usort($files, static fn (string $left, string $right): int => (filemtime($right) ?: 0) <=> (filemtime($left) ?: 0));

        return [
            'file' => basename($files[0]),
            'records' => $this->decryptAndValidate($files[0], $this->encryptionKey(), $this->chunkBytes()),
        ];
    }

    private function backupRoot(): string
    {
        $path = config('audit_log_backup.path');

        if (! is_string($path) || trim($path) === '' || ! $this->isAbsolutePath($path)) {
            throw new RuntimeException('AUDIT_LOG_BACKUP_PATH must be an explicitly configured absolute path on separate storage.');
        }

        if (! is_dir($path) && ! @mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException('The configured audit-log backup directory could not be created.');
        }

        $root = realpath($path);
        $isWindowsDriveRoot = is_string($root) && strlen($root) <= 3 && isset($root[1]) && $root[1] === ':';

        if ($root === false || ! is_dir($root) || ! is_writable($root) || $root === DIRECTORY_SEPARATOR || $isWindowsDriveRoot) {
            throw new RuntimeException('The configured audit-log backup directory is not writable or is unsafe.');
        }

        return rtrim($root, DIRECTORY_SEPARATOR);
    }

    private function isAbsolutePath(string $path): bool
    {
        $windowsDrivePath = strlen($path) >= 3
            && ctype_alpha($path[0])
            && $path[1] === ':'
            && ($path[2] === '\\' || $path[2] === '/');

        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || $windowsDrivePath;
    }

    private function encryptionKey(): string
    {
        $encoded = config('audit_log_backup.encryption_key');

        if (! is_string($encoded) || $encoded === '') {
            throw new RuntimeException('AUDIT_LOG_BACKUP_ENCRYPTION_KEY is not configured.');
        }

        $key = base64_decode($encoded, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('AUDIT_LOG_BACKUP_ENCRYPTION_KEY must be base64 for exactly 32 random bytes.');
        }

        return $key;
    }

    private function chunkBytes(): int
    {
        $size = (int) config('audit_log_backup.chunk_bytes', 1048576);

        if ($size < 4096 || $size > 4194304) {
            throw new RuntimeException('AUDIT_LOG_BACKUP_CHUNK_BYTES must be between 4096 and 4194304.');
        }

        return $size;
    }

    private function retentionDays(): int
    {
        $days = (int) config('audit_log_backup.retention_days', 90);

        if ($days < 90 || $days > 3650) {
            throw new RuntimeException('AUDIT_LOG_BACKUP_RETENTION_DAYS must be between 90 and 3650.');
        }

        return $days;
    }

    /** @param resource $output */
    private function writeEncryptedFrame($output, string $plain, string $key, int $sequence): void
    {
        $length = strlen($plain);
        $nonce = random_bytes(self::NONCE_BYTES);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plain,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            self::MAGIC.pack('N2', $sequence, $length),
            self::TAG_BYTES
        );

        if ($ciphertext === false || strlen($tag) !== self::TAG_BYTES) {
            throw new RuntimeException('Authenticated encryption of an audit-log backup chunk failed.');
        }

        $this->writeAll($output, $nonce.pack('N', $length).$ciphertext.$tag);
    }

    private function decryptAndValidate(string $path, string $key, int $chunkBytes): int
    {
        $input = fopen($path, 'rb');

        if ($input === false) {
            throw new RuntimeException('Could not open the backup for recovery verification.');
        }

        try {
            if ($this->readExactly($input, strlen(self::MAGIC)) !== self::MAGIC) {
                throw new RuntimeException('The audit-log backup format is invalid.');
            }

            $sequence = 0;
            $pendingLine = '';
            $recordCount = 0;
            $foundEnd = false;

            while (true) {
                $header = $this->readExactly($input, self::NONCE_BYTES + 4);

                if ($header === '') {
                    break;
                }

                if (strlen($header) !== self::NONCE_BYTES + 4) {
                    throw new RuntimeException('The audit-log backup is truncated.');
                }

                $nonce = substr($header, 0, self::NONCE_BYTES);
                $length = unpack('Nlength', substr($header, self::NONCE_BYTES, 4))['length'];

                if ($length > $chunkBytes) {
                    throw new RuntimeException('The audit-log backup contains an invalid chunk length.');
                }

                $ciphertext = $this->readExactly($input, $length);
                $tag = $this->readExactly($input, self::TAG_BYTES);

                if (strlen($ciphertext) !== $length || strlen($tag) !== self::TAG_BYTES) {
                    throw new RuntimeException('The audit-log backup is truncated.');
                }

                $plain = openssl_decrypt(
                    $ciphertext,
                    'aes-256-gcm',
                    $key,
                    OPENSSL_RAW_DATA,
                    $nonce,
                    $tag,
                    self::MAGIC.pack('N2', $sequence, $length)
                );

                if ($plain === false) {
                    throw new RuntimeException('Audit-log backup authentication failed; the file or key may be invalid.');
                }

                if ($length === 0) {
                    $trailing = fread($input, 1);

                    if ($trailing === false || $trailing !== '') {
                        throw new RuntimeException('The audit-log backup contains trailing data or a read error.');
                    }

                    if ($pendingLine !== '') {
                        $this->validateRecord($pendingLine);
                        $recordCount++;
                    }

                    $foundEnd = true;
                    break;
                }

                $pendingLine .= $plain;

                while (($newline = strpos($pendingLine, "\n")) !== false) {
                    $line = substr($pendingLine, 0, $newline);
                    $pendingLine = substr($pendingLine, $newline + 1);

                    if ($line !== '') {
                        $this->validateRecord($line);
                        $recordCount++;
                    }
                }

                $sequence++;
            }

            if (! $foundEnd) {
                throw new RuntimeException('The audit-log backup has no authenticated end marker.');
            }

            return $recordCount;
        } finally {
            fclose($input);
        }
    }

    private function validateRecord(string $line): void
    {
        $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($record)
            || ! is_string($record['id'] ?? null)
            || ! is_string($record['action'] ?? null)
            || ! is_string($record['occurred_at'] ?? null)) {
            throw new RuntimeException('The decrypted audit-log backup contains an invalid record.');
        }
    }

    private function pruneExpiredBackups(string $root, int $retentionDays): int
    {
        $cutoff = now('UTC')->subDays($retentionDays)->timestamp;
        $deleted = 0;

        foreach (glob($root.DIRECTORY_SEPARATOR.'audit-logs-*.kfa') ?: [] as $path) {
            $modifiedAt = filemtime($path);

            if ($modifiedAt !== false && $modifiedAt < $cutoff && @unlink($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /** @param resource $stream */
    private function writeAll($stream, string $contents): void
    {
        $offset = 0;
        $length = strlen($contents);

        while ($offset < $length) {
            $written = fwrite($stream, substr($contents, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('A backup file write operation failed.');
            }

            $offset += $written;
        }
    }

    /** @param resource $stream */
    private function readExactly($stream, int $length): string
    {
        $contents = '';

        while (strlen($contents) < $length && ! feof($stream)) {
            $chunk = fread($stream, $length - strlen($contents));

            if ($chunk === false) {
                throw new RuntimeException('A backup file read operation failed.');
            }

            if ($chunk === '') {
                break;
            }

            $contents .= $chunk;
        }

        return $contents;
    }
}
