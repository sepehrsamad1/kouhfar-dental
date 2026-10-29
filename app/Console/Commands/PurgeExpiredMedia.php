<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\PurgeMediaAsset;
use App\MediaAsset;
use App\Services\MediaAssetService;
use Illuminate\Console\Command;

final class PurgeExpiredMedia extends Command
{
    protected $signature = 'media:purge-expired {--sync : Purge eligible assets immediately instead of dispatching jobs}';

    protected $description = 'Dispatch queued jobs for media that reached its approved purge deadline.';

    public function handle(MediaAssetService $service): int
    {
        $count = 0;

        MediaAsset::withTrashed()
            ->where(function ($query): void {
                $query->where(function ($q): void {
                    $q->whereNull('deleted_at')
                        ->whereNotNull('owner_patient_id')
                        ->where('created_at', '<=', now('UTC')->subYears(2));
                })->orWhere(function ($q): void {
                    $q->whereNull('deleted_at')
                        ->whereNull('owner_patient_id')
                        ->where('created_at', '<=', now('UTC')->subDays(90));
                })->orWhere(function ($q): void {
                    $q->whereNotNull('deleted_at')
                        ->where('deleted_at', '<=', now('UTC')->subDays(30));
                });
            })
            ->orderBy('id')
            ->chunkById(100, function ($assets) use ($service, &$count): void {
                foreach ($assets as $asset) {
                    if ($this->option('sync')) {
                        $count += $service->purge($asset) ? 1 : 0;
                    } else {
                        PurgeMediaAsset::dispatch($asset->id);
                        $count++;
                    }
                }
            });

        $this->info("Queued {$count} media purge job(s).");

        return self::SUCCESS;
    }
}
