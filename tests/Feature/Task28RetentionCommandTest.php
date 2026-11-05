<?php

declare(strict_types=1);

use App\AIRequest;
use App\AIUsage;
use App\CompletedJobHistory;
use App\IdempotencyRecord;
use App\LoginAttempt;
use App\SmartSmileSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('prunes login attempts after 90 days and keeps newer rows', function (): void {
    $old = LoginAttempt::query()->create([
        'identifier_hash' => hash('sha256', 'old@example.invalid'),
        'ip_hash' => hash('sha256', '192.0.2.1'),
        'created_at' => now('UTC')->subDays(91),
        'success' => false,
    ]);
    $recent = LoginAttempt::query()->create([
        'identifier_hash' => hash('sha256', 'recent@example.invalid'),
        'ip_hash' => hash('sha256', '192.0.2.2'),
        'created_at' => now('UTC')->subDays(89),
        'success' => true,
    ]);

    Artisan::call('security:prune-expired-data');

    expect(LoginAttempt::query()->find($old->id))->toBeNull()
        ->and(LoginAttempt::query()->find($recent->id))->not->toBeNull();
});

it('prunes AI operational records after 12 months', function (): void {
    $oldRequest = AIRequest::query()->create([
        'feature' => 'assistant',
        'actor_type' => 'system',
        'actor_id' => null,
        'status' => 'succeeded',
        'created_at' => now('UTC')->subMonths(13),
        'provider' => 'fake',
        'model' => 'fake-v1',
        'capability' => 'text',
        'input_tokens' => 1,
        'output_tokens' => 1,
        'estimated_cost_irr' => 0,
        'content_ids' => [],
    ]);
    $recentRequest = AIRequest::query()->create([
        'feature' => 'assistant',
        'actor_type' => 'system',
        'actor_id' => null,
        'status' => 'succeeded',
        'created_at' => now('UTC')->subMonths(11),
        'provider' => 'fake',
        'model' => 'fake-v1',
        'capability' => 'text',
        'input_tokens' => 1,
        'output_tokens' => 1,
        'estimated_cost_irr' => 0,
        'content_ids' => [],
    ]);
    AIUsage::query()->create([
        'day' => now('UTC')->subMonths(13)->toDateString(),
        'feature' => 'assistant',
        'provider' => 'fake',
        'model' => 'fake-v1',
        'capability' => 'text',
        'input_tokens' => 1,
        'output_tokens' => 1,
        'cost_estimate' => 0,
        'estimated_cost_irr' => 0,
        'request_count' => 1,
    ]);

    Artisan::call('security:prune-expired-data');

    expect(AIRequest::query()->find($oldRequest->id))->toBeNull()
        ->and(AIRequest::query()->find($recentRequest->id))->not->toBeNull()
        ->and(AIUsage::query()->count())->toBe(0);
});

it('prunes idempotency records only after their 24-hour expiry', function (): void {
    $expired = IdempotencyRecord::query()->create([
        'owner_scope' => 'test',
        'key' => 'expired',
        'request_hash' => hash('sha256', 'expired'),
        'response_status' => 201,
        'response_body' => ['ok' => true],
        'created_at' => now('UTC')->subHours(25),
        'expires_at' => now('UTC')->subHour(),
    ]);
    $active = IdempotencyRecord::query()->create([
        'owner_scope' => 'test',
        'key' => 'active',
        'request_hash' => hash('sha256', 'active'),
        'response_status' => 201,
        'response_body' => ['ok' => true],
        'created_at' => now('UTC')->subHour(),
        'expires_at' => now('UTC')->addHours(23),
    ]);

    Artisan::call('security:prune-expired-data');

    expect(IdempotencyRecord::query()->find($expired->id))->toBeNull()
        ->and(IdempotencyRecord::query()->find($active->id))->not->toBeNull();
});

it('clears expired Smart Smile result payloads without deleting consent evidence', function (): void {
    $expired = SmartSmileSession::factory()->create([
        'result' => ['message' => 'Synthetic result'],
        'result_generated_at' => now('UTC')->subDays(366),
        'result_expires_at' => now('UTC')->subDay(),
    ]);
    $active = SmartSmileSession::factory()->create([
        'result' => ['message' => 'Synthetic result'],
        'result_generated_at' => now('UTC')->subDays(364),
        'result_expires_at' => now('UTC')->addDay(),
    ]);

    Artisan::call('security:prune-expired-data');

    expect(SmartSmileSession::query()->find($expired->id))
        ->not->toBeNull()
        ->and(SmartSmileSession::query()->find($expired->id)->result)->toBeNull()
        ->and(SmartSmileSession::query()->find($expired->id)->consented_at)->not->toBeNull()
        ->and(SmartSmileSession::query()->find($active->id)->result)->not->toBeNull();
});

it('prunes completed-job history after seven days', function (): void {
    $old = CompletedJobHistory::query()->create([
        'job_class' => 'SyntheticCompletedJob',
        'queue_name' => 'default',
        'attempts' => 1,
        'completed_at' => now('UTC')->subDays(8),
    ]);
    $recent = CompletedJobHistory::query()->create([
        'job_class' => 'SyntheticCompletedJob',
        'queue_name' => 'default',
        'attempts' => 1,
        'completed_at' => now('UTC')->subDays(6),
    ]);

    Artisan::call('security:prune-expired-data');

    expect(CompletedJobHistory::query()->find($old->id))->toBeNull()
        ->and(CompletedJobHistory::query()->find($recent->id))->not->toBeNull();
});

it('prunes failed queue jobs after 30 days', function (): void {
    $oldUuid = (string) Str::uuid();
    $recentUuid = (string) Str::uuid();

    foreach ([[$oldUuid, 31], [$recentUuid, 29]] as [$uuid, $days]) {
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'SyntheticFailedJob'], JSON_THROW_ON_ERROR),
            'exception' => 'Synthetic failure metadata only.',
            'failed_at' => now('UTC')->subDays($days),
        ]);
    }

    Artisan::call('security:prune-expired-data');

    expect(DB::table('failed_jobs')->where('uuid', $oldUuid)->exists())->toBeFalse()
        ->and(DB::table('failed_jobs')->where('uuid', $recentUuid)->exists())->toBeTrue();
});
