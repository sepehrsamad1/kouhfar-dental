# Audit-log backup and recovery runbook

## Approved policy

- Create one encrypted audit-log backup every day.
- Keep encrypted backup copies for at least 90 days.
- Store backups on storage physically separate from the application/database storage.
- Restrict the destination to authorized administrators and encrypt the storage volume/service where available.
- Run a monthly authenticated restore into a temporary database table.
- Never automatically delete original rows from `audit_logs`. The 90-day retention applies only to backup copies.

## Deployment configuration

Set these values in deployment secret/environment configuration. Do not commit real values:

- `AUDIT_LOG_BACKUP_PATH`: an absolute path mounted from a separate storage volume or separately managed storage service. A different directory on the same disk is not sufficient.
- `AUDIT_LOG_BACKUP_ENCRYPTION_KEY`: base64 encoding of exactly 32 cryptographically random bytes.
- `AUDIT_LOG_BACKUP_RETENTION_DAYS`: defaults to 90; values below 90 are rejected.

Generate a key in a trusted environment and transfer it through the approved secret-management channel:

```sh
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Back up the key separately from the encrypted backup files. Loss of the key makes backups unrecoverable. Do not paste the key into chat, commit it, or put it in `.env.example`.

On Linux, provision the mount and restrict its directory permissions to the application service account (for example, directory mode 0700 and file mode 0600). On Windows, use an NTFS volume and explicit ACLs so only the application service identity and authorized administrators can read the directory. The application cannot prove that a configured path is physically separate; deployment staff must verify the mounted volume/service and document that evidence.

## Commands

```sh
php artisan audit-logs:backup
php artisan audit-logs:verify-latest
```

The backup command reads the existing `audit_logs` table through Laravel's database connection, so it does not depend on `pg_dump` or the SQLite CLI. It exports a consistent database snapshot directly into an encrypted file, using independently authenticated AES-256-GCM chunks using a per-backup derived key and random salt, and publishes the completed backup atomically. It only prunes encrypted backup copies older than the configured retention period after a successful backup. No plaintext backup file is written to disk. Original database rows are never deleted.

The verification command authenticates and decrypts each chunk in memory, then inserts every recovered row into a randomly named temporary table that mirrors the audit-log column types on the active PostgreSQL or SQLite connection. It compares the restored row count with the authenticated record count and drops the temporary table in a finally block. It never writes to the live audit_logs table or stores plaintext backup files. This is a database-level temporary-table restore drill; it is not a substitute for a separately controlled full disaster-recovery exercise against an isolated database environment.

## Scheduler

Laravel schedules the daily backup at 02:00 Asia/Tehran and verification on the first day of each month at 04:00 Asia/Tehran. Ensure the host scheduler invokes `php artisan schedule:run` every minute. Monitor command failures and alert the responsible administrator; a configured schedule alone is not evidence that the host scheduler or separate storage is operational.

## Operational checks

1. Confirm the configured path is a mounted, separate storage volume/service.
2. Confirm the application service account can write there and unrelated users cannot read it.
3. Confirm the encryption key is present, backed up separately and available only to authorized operators.
4. Run both commands manually in the deployment environment and retain success/failure evidence.
5. Confirm monthly verification succeeds and periodically perform a separately controlled restore into an isolated database.
6. Monitor available storage capacity and failed scheduler executions.
7. Never run destructive cleanup against the original audit-log table under this policy.
