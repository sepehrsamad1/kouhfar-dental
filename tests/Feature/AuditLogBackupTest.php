<?php

declare(strict_types=1);

use App\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('audit_log_backup.path', sys_get_temp_dir().DIRECTORY_SEPARATOR.'kouhfar-audit-backups-'.Str::uuid());
    config()->set('audit_log_backup.encryption_key', base64_encode(random_bytes(32)));
    config()->set('audit_log_backup.retention_days', 90);
    config()->set('audit_log_backup.chunk_bytes', 4096);
});

afterEach(function (): void {
    $path = config('audit_log_backup.path');

    if (is_string($path) && is_dir($path)) {
        File::deleteDirectory($path);
    }
});

it('creates an authenticated encrypted backup and verifies it without changing live audit logs', function (): void {
    AuditLog::factory()->count(3)->create();

    $this->artisan('audit-logs:backup')->assertExitCode(0);
    $this->artisan('audit-logs:verify-latest')
        ->expectsOutputToContain('3 audit-log record(s) restored into a temporary table and validated')
        ->assertExitCode(0);

    $files = glob(config('audit_log_backup.path').DIRECTORY_SEPARATOR.'audit-logs-*.kfa') ?: [];

    expect($files)->toHaveCount(1)
        ->and(file_get_contents($files[0]))->not->toContain('test.event')
        ->and(AuditLog::query()->count())->toBe(3);
});

it('rejects a backup whose authenticated ciphertext has been modified', function (): void {
    AuditLog::factory()->create();

    $this->artisan('audit-logs:backup')->assertExitCode(0);

    $files = glob(config('audit_log_backup.path').DIRECTORY_SEPARATOR.'audit-logs-*.kfa') ?: [];
    $contents = file_get_contents($files[0]);
    $contents[40] = chr(ord($contents[40]) ^ 1);
    file_put_contents($files[0], $contents);

    $this->artisan('audit-logs:verify-latest')
        ->expectsOutputToContain('Audit-log backup verification failed')
        ->assertExitCode(1);
});

it('fails closed when no explicit absolute backup path is configured', function (): void {
    AuditLog::factory()->create();
    config()->set('audit_log_backup.path', null);

    $this->artisan('audit-logs:backup')
        ->expectsOutputToContain('AUDIT_LOG_BACKUP_PATH must be an explicitly configured absolute path')
        ->assertExitCode(1);
});

it('rejects an encryption key that is not exactly 32 bytes after base64 decoding', function (): void {
    AuditLog::factory()->create();
    config()->set('audit_log_backup.encryption_key', base64_encode('too-short'));

    $this->artisan('audit-logs:backup')
        ->expectsOutputToContain('must be base64 for exactly 32 random bytes')
        ->assertExitCode(1);
});

it('prunes only expired encrypted backup copies after a successful new backup', function (): void {
    AuditLog::factory()->create();

    $directory = config('audit_log_backup.path');
    mkdir($directory, 0700, true);

    $expired = $directory.DIRECTORY_SEPARATOR.'audit-logs-expired.kfa';
    $recent = $directory.DIRECTORY_SEPARATOR.'audit-logs-recent.kfa';
    file_put_contents($expired, 'old encrypted backup fixture');
    file_put_contents($recent, 'recent encrypted backup fixture');
    touch($expired, now('UTC')->subDays(91)->timestamp);
    touch($recent, now('UTC')->subDays(89)->timestamp);

    $this->artisan('audit-logs:backup')->assertExitCode(0);

    expect(is_file($expired))->toBeFalse()
        ->and(is_file($recent))->toBeTrue()
        ->and(AuditLog::query()->count())->toBe(1);
});
