<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Appointment;
use App\ClinicWorkingHour;
use App\Practitioner;
use App\Resource;
use App\ScheduleBreak;
use App\ScheduleException;
use App\Service;
use App\Support\AuditLogger;
use App\WorkingHour;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ClinicConfigurationController
{
    public function index(Request $request): View
    {
        Gate::authorize('view', Service::class);
        Gate::authorize('view', Practitioner::class);
        Gate::authorize('view', Resource::class);
        Gate::authorize('view', WorkingHour::class);
        Gate::authorize('view', ScheduleException::class);

        return view('staff.configuration.index', [
            'services' => Service::query()->orderBy('name_fa')->get(),
            'practitioners' => Practitioner::query()->orderBy('display_name_fa')->get(),
            'resources' => Resource::query()->orderBy('name_fa')->get(),
            'clinicHours' => ClinicWorkingHour::query()->orderBy('weekday')->get(),
            'workingHours' => WorkingHour::query()->with('practitioner')->orderBy('weekday')->get(),
            'breaks' => ScheduleBreak::query()->with('practitioner')->orderBy('weekday')->get(),
            'exceptions' => ScheduleException::query()->with('practitioner')->orderBy('starts_at')->get(),
            'canManage' => $request->user('staff')->role === 'admin',
            'weekdays' => [1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 6 => 'شنبه', 7 => 'یکشنبه'],
        ]);
    }

    public function storeService(Request $request): RedirectResponse
    {
        Gate::authorize('create', Service::class);
        $data = $request->validate([
            'name_fa' => ['required', 'string', 'min:2', 'max:200'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'buffer_before_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'buffer_after_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
        ]);
        $service = DB::transaction(fn () => Service::query()->create($data + [
            'is_active' => false,
            'is_public_bookable' => false,
        ]));
        AuditLogger::record($request->user('staff'), 'configuration.service_created', $service, null, [
            'is_active' => false,
        ]);

        return back()->with('success', 'خدمت به صورت غیرفعال ثبت شد. فعال‌سازی عمومی تا تأیید تنظیمات انجام نمی‌شود.');
    }

    public function updateService(Request $request, Service $service): RedirectResponse
    {
        Gate::authorize('update', $service);
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'name_fa' => ['required', 'string', 'min:2', 'max:200'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'buffer_before_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'buffer_after_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'is_active' => ['required', 'boolean'],
        ]);

        $configurationChanged = (int) $data['duration_minutes'] !== $service->duration_minutes
            || (int) $data['buffer_before_minutes'] !== $service->buffer_before_minutes
            || (int) $data['buffer_after_minutes'] !== $service->buffer_after_minutes;
        if ($configurationChanged) {
            $impacted = Appointment::query()
                ->where('service_id', $service->id)
                ->where('status', 'confirmed')
                ->where('start_at', '>=', now('UTC'))
                ->orderBy('start_at')
                ->get(['id', 'start_at']);
            if ($impacted->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'service' => 'تغییر مدت یا زمان حائل می‌تواند بر نوبت‌های تأییدشده اثر بگذارد. تغییر ذخیره نشد. شناسه نوبت‌ها: '.$impacted->pluck('id')->implode(', '),
                ]);
            }
        }

        $updated = DB::transaction(function () use ($request, $service, $data): bool {
            $version = (int) $data['version'];
            unset($data['version']);
            $updated = Service::query()->whereKey($service->getKey())->where('version', $version)
                ->update($data + ['version' => DB::raw('version + 1'), 'updated_at' => now('UTC')]);
            if ($updated !== 1) {
                return false;
            }
            AuditLogger::record($request->user('staff'), 'configuration.service_updated', $service, null, [
                'is_active' => (bool) $data['is_active'],
            ]);

            return true;
        });

        if (! $updated) {
            throw ValidationException::withMessages(['version' => 'این تنظیمات توسط کاربر دیگری تغییر کرده است. صفحه را تازه‌سازی و دوباره بررسی کنید.']);
        }

        return back()->with('success', 'تنظیمات خدمت ذخیره شد. سوابق و نوبت‌های قبلی حذف یا بازنویسی نشدند.');
    }

    public function storePractitioner(Request $request): RedirectResponse
    {
        Gate::authorize('create', Practitioner::class);
        $data = $request->validate(['display_name_fa' => ['required', 'string', 'min:2', 'max:200']]);
        $practitioner = Practitioner::query()->create(['display_name_fa' => $data['display_name_fa'], 'is_active' => false]);
        AuditLogger::record($request->user('staff'), 'configuration.practitioner_created', $practitioner, null, ['is_active' => false]);

        return back()->with('success', 'پزشک به صورت غیرفعال ثبت شد؛ صلاحیت یا خدماتی به او اختصاص داده نشده است.');
    }

    public function updatePractitioner(Request $request, Practitioner $practitioner): RedirectResponse
    {
        Gate::authorize('update', $practitioner);
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'display_name_fa' => ['required', 'string', 'min:2', 'max:200'],
            'is_active' => ['required', 'boolean'],
        ]);
        if (! (bool) $data['is_active'] && $practitioner->is_active) {
            $impacted = Appointment::query()
                ->where('status', 'confirmed')->where('start_at', '>=', now('UTC'))
                ->whereHas('practitioners', fn ($builder) => $builder->whereKey($practitioner->id)->where('appointment_practitioners.active', true))
                ->orderBy('start_at')->get(['id']);
            if ($impacted->isNotEmpty()) {
                throw ValidationException::withMessages(['practitioner' => 'غیرفعال‌سازی پزشک با نوبت‌های تأییدشده مرتبط است. تغییری ذخیره نشد. شناسه نوبت‌ها: '.$impacted->pluck('id')->implode(', ')]);
            }
        }
        $version = (int) $data['version'];
        unset($data['version']);
        $updated = Practitioner::query()->whereKey($practitioner->id)->where('version', $version)
            ->update($data + ['version' => DB::raw('version + 1'), 'updated_at' => now('UTC')]);
        if ($updated !== 1) {
            throw ValidationException::withMessages(['version' => 'تنظیمات پزشک هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.practitioner_updated', $practitioner, null, ['is_active' => (bool) $data['is_active']]);

        return back()->with('success', 'تنظیمات پزشک ذخیره شد.');
    }

    public function storeResource(Request $request): RedirectResponse
    {
        Gate::authorize('create', Resource::class);
        $data = $request->validate([
            'name_fa' => ['required', 'string', 'min:2', 'max:200'],
            'kind' => ['required', 'string', 'min:2', 'max:80'],
        ]);
        $resource = Resource::query()->create($data + ['is_active' => false]);
        AuditLogger::record($request->user('staff'), 'configuration.resource_created', $resource, null, ['is_active' => false]);

        return back()->with('success', 'منبع به صورت غیرفعال ثبت شد؛ سیاست تخصیص نوبت هنوز تعریف نشده است.');
    }

    public function updateResource(Request $request, Resource $resource): RedirectResponse
    {
        Gate::authorize('update', $resource);
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'name_fa' => ['required', 'string', 'min:2', 'max:200'],
            'kind' => ['required', 'string', 'min:2', 'max:80'],
            'is_active' => ['required', 'boolean'],
        ]);
        if (! (bool) $data['is_active'] && $resource->is_active) {
            $impacted = Appointment::query()
                ->where('status', 'confirmed')->where('start_at', '>=', now('UTC'))
                ->whereHas('resources', fn ($builder) => $builder->whereKey($resource->id)->where('appointment_resources.active', true))
                ->orderBy('start_at')->get(['id']);
            if ($impacted->isNotEmpty()) {
                throw ValidationException::withMessages(['resource' => 'غیرفعال‌سازی منبع با نوبت‌های تأییدشده مرتبط است. تغییری ذخیره نشد. شناسه نوبت‌ها: '.$impacted->pluck('id')->implode(', ')]);
            }
        }
        $version = (int) $data['version'];
        unset($data['version']);
        $updated = Resource::query()->whereKey($resource->id)->where('version', $version)
            ->update($data + ['version' => DB::raw('version + 1'), 'updated_at' => now('UTC')]);
        if ($updated !== 1) {
            throw ValidationException::withMessages(['version' => 'تنظیمات منبع هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.resource_updated', $resource, null, ['is_active' => (bool) $data['is_active']]);

        return back()->with('success', 'تنظیمات منبع ذخیره شد.');
    }

    public function storeClinicHour(Request $request): RedirectResponse
    {
        Gate::authorize('manage-configuration');
        $data = $request->validate([
            'weekday' => ['required', 'integer', Rule::in([1, 2, 3, 4, 6, 7])],
            'start_local' => ['required', 'date_format:H:i'],
            'end_local' => ['required', 'date_format:H:i', 'after:start_local'],
            'timezone' => ['required', Rule::in(['Asia/Tehran'])],
        ]);
        $hour = ClinicWorkingHour::query()->create($data);
        AuditLogger::record($request->user('staff'), 'configuration.clinic_hours_created', $hour, null, ['status' => 'configured']);

        return back()->with('success', 'ساعات کلینیک ثبت شد.');
    }

    public function storeWorkingHour(Request $request): RedirectResponse
    {
        Gate::authorize('create', WorkingHour::class);
        $data = $request->validate([
            'practitioner_id' => ['required', 'uuid', 'exists:practitioners,id'],
            'weekday' => ['required', 'integer', Rule::in([1, 2, 3, 4, 6, 7])],
            'start_local' => ['required', 'date_format:H:i'],
            'end_local' => ['required', 'date_format:H:i', 'after:start_local'],
        ]);
        $hour = WorkingHour::query()->create($data);
        AuditLogger::record($request->user('staff'), 'configuration.practitioner_hours_created', $hour, null, ['practitioner_id' => $hour->practitioner_id]);

        return back()->with('success', 'ساعات پزشک ثبت شد.');
    }

    public function storeBreak(Request $request): RedirectResponse
    {
        Gate::authorize('manage-configuration');
        $data = $request->validate([
            'practitioner_id' => ['nullable', 'uuid', 'exists:practitioners,id'],
            'weekday' => ['required', 'integer', Rule::in([1, 2, 3, 4, 6, 7])],
            'start_local' => ['required', 'date_format:H:i'],
            'end_local' => ['required', 'date_format:H:i', 'after:start_local'],
        ]);
        $data['timezone'] = 'Asia/Tehran';
        $break = ScheduleBreak::query()->create($data);
        AuditLogger::record($request->user('staff'), 'configuration.schedule_break_created', $break, null, ['practitioner_id' => $break->practitioner_id]);

        return back()->with('success', 'وقفه ثبت شد.');
    }

    public function storeException(Request $request): RedirectResponse
    {
        Gate::authorize('create', ScheduleException::class);
        $data = $request->validate([
            'practitioner_id' => ['nullable', 'uuid', 'exists:practitioners,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'kind' => ['required', Rule::in(['closed', 'holiday', 'special_opening'])],
        ]);
        $data['starts_at'] = Carbon::parse($data['starts_at'], 'Asia/Tehran')->utc();
        $data['ends_at'] = Carbon::parse($data['ends_at'], 'Asia/Tehran')->utc();
        if (in_array($data['kind'], ['closed', 'holiday'], true)) {
            $this->assertNoConfirmedAppointmentImpactForRange($data['starts_at'], $data['ends_at'], $data['practitioner_id'] ?? null);
        }
        $exception = ScheduleException::query()->create($data);
        AuditLogger::record($request->user('staff'), 'configuration.schedule_exception_created', $exception, null, ['status' => $exception->kind, 'practitioner_id' => $exception->practitioner_id]);

        return back()->with('success', 'استثنای برنامه ثبت شد. بررسی تعارض با سیاست تخصیص منابع هنوز در دسترس نیست.');
    }

    private function assertNoConfirmedAppointmentImpactForRange(DateTimeInterface $startAt, DateTimeInterface $endAt, ?string $practitionerId): void
    {
        $query = Appointment::query()
            ->where('status', 'confirmed')
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt);
        if ($practitionerId !== null) {
            $query->whereHas('practitioners', fn ($builder) => $builder->whereKey($practitionerId));
        }
        $impacted = $query->orderBy('start_at')->get(['id', 'start_at', 'end_at']);
        if ($impacted->isNotEmpty()) {
            throw ValidationException::withMessages([
                'schedule' => 'تعطیلی با نوبت‌های تأییدشده تداخل دارد. تعطیلی ذخیره نشد و هیچ نوبتی تغییر نکرد. شناسه نوبت‌ها: '.$impacted->pluck('id')->implode(', '),
            ]);
        }
    }
}
