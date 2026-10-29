<?php

declare(strict_types=1);

namespace App\Jobs;

use App\MediaAsset;
use App\Services\MediaAssetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PurgeMediaAsset implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $mediaAssetId) {}

    public function handle(MediaAssetService $service): void
    {
        $asset = MediaAsset::withTrashed()->find($this->mediaAssetId);

        if ($asset !== null) {
            $service->purge($asset);
        }
    }
}
