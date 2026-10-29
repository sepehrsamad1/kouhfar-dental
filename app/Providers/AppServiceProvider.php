<?php

declare(strict_types=1);

namespace App\Providers;

use App\AIRequest;
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
use App\StaffUser;
use App\Services\SmsProviderManager;
use App\WorkingHour;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsProviderManager::class);
        $this->app->singleton(SmsChannel::class, fn ($app): SmsChannel => new SmsChannel(
            $app->make(SmsProviderManager::class)->resolve(),
        ));
    }

    public function boot(): void
    {
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
