<?php

declare(strict_types=1);

namespace App\Jobs;

use App\MediaAsset;
use App\Services\AIProviderManager;
use App\SmartSmileSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessSmartSmile implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public string $sessionId;

    public function __construct(string $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function backoff(): array
    {
        return [30];
    }

    public function handle(AIProviderManager $ai): void
    {
        $session = SmartSmileSession::query()->with('mediaAsset')->find($this->sessionId);

        if ($session === null || $session->revoked_at !== null) {
            return;
        }

        $asset = $session->mediaAsset;

        if (! $asset instanceof MediaAsset) {
            return;
        }

        $session->forceFill([
            'status' => 'processing',
            'result_generated_at' => null,
            'result_expires_at' => null,
        ])->save();

        try {
            if (Storage::disk('media')->exists($asset->storage_key) === false) {
                $session->forceFill([
                    'status' => 'failed',
                    'result' => ['message' => 'تصویر در دسترس نیست. لطفاً دوباره تلاش کنید.'],
                    'result_generated_at' => null,
                    'result_expires_at' => null,
                ])->save();

                return;
            }

            $response = $ai->analyzeImage(
                'smart-smile',
                $asset->storage_key,
                ['feature_variant' => 'non_diagnostic_simulation'],
            );

            if ($response['status'] !== 'succeeded') {
                $session->forceFill([
                    'status' => 'failed',
                    'result' => ['message' => 'پردازش تصویر در حال حاضر در دسترس نیست. لطفاً بعداً دوباره تلاش کنید.'],
                    'result_generated_at' => null,
                    'result_expires_at' => null,
                ])->save();

                return;
            }

            $generatedAt = now('UTC');
            $session->forceFill([
                'status' => 'completed',
                'result' => [
                    'message' => 'این خروجی صرفاً یک شبیه‌سازی غیرتشخیصی است و نتیجه پزشکی یا پیشنهاد درمانی محسوب نمی‌شود.',
                ],
                'result_generated_at' => $generatedAt,
                'result_expires_at' => $generatedAt->copy()->addDays((int) config('smart_smile.result_retention_days', 365)),
            ])->save();
        } catch (Throwable) {
            $session->forceFill([
                'status' => 'failed',
                'result' => ['message' => 'پردازش تصویر با خطا مواجه شد. لطفاً بعداً دوباره تلاش کنید.'],
                'result_generated_at' => null,
                'result_expires_at' => null,
            ])->save();
        }
    }
}
