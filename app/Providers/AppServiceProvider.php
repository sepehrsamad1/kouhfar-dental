<?php

declare(strict_types=1);

namespace App\Providers;

use App\AI\FakeAIProvider;
use App\AIRequest;
use App\CompletedJobHistory;
use App\Appointment;
use App\ContentItem;
use App\MediaAsset;
use App\Notifications\SmsChannel;
use App\Patient;
use App\Policies\AIRequestPolicy;
use App\Policies\AppointmentPolicy;
use App\Policies\ContentItemPolicy;
use App\Policies\MediaAssetPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PractitionerPolicy;
use App\Policies\ResourcePolicy;
use App\Policies\ScheduleExceptionPolicy;
use App\Policies\ServicePolicy;
use App\Policies\WorkingHourPolicy;
use App\Practitioner;
use App\Resource;
use App\ScheduleException;
use App\Service;
use App\Services\AIControlService;
use App\Services\AIProviderManager;
use App\Services\AIRequestRecorder;
use App\Services\AIUsageRecorder;
use App\Services\Clock;
use App\Services\OtpProviderManager;
use App\Services\SmsProviderManager;
use App\Services\SystemClock;
use App\StaffUser;
use App\WorkingHour;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsProviderManager::class);
        $this->app->singleton(OtpProviderManager::class);
        $this->app->singleton(SmsChannel::class, fn ($app): SmsChannel => new SmsChannel(
            $app->make(SmsProviderManager::class)->resolve(),
        ));
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(FakeAIProvider::class);
        $this->app->singleton(AIRequestRecorder::class);
        $this->app->singleton(AIUsageRecorder::class);
        $this->app->singleton(AIProviderManager::class);
        $this->app->singleton(AIControlService::class);
    }

    public function boot(): void
    {
        Queue::after(function (JobProcessed $event): void {
            CompletedJobHistory::query()->create([
                'job_class' => $event->job->resolveName(),
                'queue_name' => $event->job->getQueue(),
                'attempts' => $event->job->attempts(),
                'completed_at' => now('UTC'),
            ]);
        });

        View::composer('layouts.staff', function ($view): void {
            $view->with('aiControlSummary', app(AIControlService::class)->summary());
        });
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Practitioner::class, PractitionerPolicy::class);
        Gate::policy(Resource::class, ResourcePolicy::class);
        Gate::policy(WorkingHour::class, WorkingHourPolicy::class);
        Gate::policy(ScheduleException::class, ScheduleExceptionPolicy::class);
        Gate::policy(ContentItem::class, ContentItemPolicy::class);
        Gate::policy(MediaAsset::class, MediaAssetPolicy::class);
        Gate::policy(AIRequest::class, AIRequestPolicy::class);

        Gate::define('manage-staff', fn (StaffUser $user): bool => $user->role === 'admin');
        Gate::define('view-audit-log', fn (StaffUser $user): bool => $user->role === 'admin');
        Gate::define('manage-configuration', fn (StaffUser $user): bool => $user->role === 'admin');
    }
}
