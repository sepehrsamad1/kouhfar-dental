<?php

declare(strict_types=1);

namespace App\Providers;

use App\AI\FakeAIProvider;
use App\Services\AIProviderManager;
use App\Services\AIRequestRecorder;
use App\Services\AIUsageRecorder;
use App\Services\Clock;
use App\Services\SystemClock;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(FakeAIProvider::class);
        $this->app->singleton(AIRequestRecorder::class);
        $this->app->singleton(AIUsageRecorder::class);
        $this->app->singleton(AIProviderManager::class);
    }

    public function boot(): void
    {
        //
    }
}
