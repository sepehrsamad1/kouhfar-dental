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
            AuditLogger::record($request->user('staff'), 'configuration.service_updated', $service, [
                'is_active' => $service->is_active,
                'duration_minutes' => $service->duration_minutes,
                'buffer_before_minutes' => $service->buffer_before_minutes,
                'buffer_after_minutes' => $service->buffer_after_minutes,
            ], [
                'is_active' => (bool) $data['is_active'],
                'duration_minutes' => (int) $data['duration_minutes'],
                'buffer_before_minutes' => (int) $data['buffer_before_minutes'],
                'buffer_after_minutes' => (int) $data['buffer_after_minutes'],
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
        AuditLogger::record($request->user('staff'), 'configuration.practitioner_updated', $practitioner, [
            'is_active' => $practitioner->is_active,
        ], ['is_active' => (bool) $data['is_active']]);

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
        AuditLogger::record($request->user('staff'), 'configuration.resource_updated', $resource, [
            'is_active' => $resource->is_active,
        ], ['is_active' => (bool) $data['is_active']]);

        return back()->with('success', 'تنظیمات منبع ذخیره شد.');
    }

    public function storeClinicHour(Request $request): RedirectResponse
    {
        Gate::authorize('manage-configuration');
        $data = $request->validate([
            'weekday' => ['required', 'integer', Rule::in([1, 2, 3, 4, 6, 7]), Rule::unique('clinic_working_hours', 'weekday')],
            'start_local' => ['required', 'date_format:H:i'],
            'end_local' => ['required', 'date_format:H:i', 'after:start_local'],
            'timezone' => ['required', Rule::in(['Asia/Tehran'])],
        ]);
        $hour = ClinicWorkingHour::query()->create($data);
        AuditLogger::record($request->user('staff'), 'configuration.clinic_hours_created', $hour, null, ['status' => 'configured']);

        return back()->with('success', 'ساعات کلینیک ثبت شد.');
    }

    public function updateClinicHour(Request $request, ClinicWorkingHour $hour): RedirectResponse
    {
        Gate::authorize('manage-configuration');
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'weekday' => ['required', 'integer', Rule::in([1, 2, 3, 4, 6, 7]), Rule::unique('clinic_working_hours', 'weekday')->ignore($hour->id)],
            'start_local' => ['required', 'date_format:H:i'],
            'end_local' => ['required', 'date_format:H:i', 'after:start_local'],
            'timezone' => ['required', Rule::in(['Asia/Tehran'])],
        ]);
        if ((int) $data['weekday'] !== $hour->weekday) {
            $this->assertNoConfirmedAppointmentsOnWeekday($hour->weekday);
        }
        $this->assertNoConfirmedAppointmentsOutsidePeriod((int) $data['weekday'], $data['start_local'], $data['end_local']);
        $version = (int) $data['version'];
        unset($data['version']);
        $updated = ClinicWorkingHour::query()->whereKey($hour->id)->where('version', $version)
            ->update($data + ['version' => DB::raw('version + 1'), 'updated_at' => now('UTC')]);
        if ($updated !== 1) {
            throw ValidationException::withMessages(['version' => 'ساعات کلینیک هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.clinic_hours_updated', $hour, [
            'weekday' => $hour->weekday,
            'start_local' => substr((string) $hour->start_local, 0, 5),
            'end_local' => substr((string) $hour->end_local, 0, 5),
            'timezone' => $hour->timezone,
        ], [
            'weekday' => (int) $data['weekday'],
            'start_local' => $data['start_local'],
            'end_local' => $data['end_local'],
            'timezone' => $data['timezone'],
        ]);

        return back()->with('success', 'ساعات کلینیک ذخیره شد.');
    }

    public function deleteClinicHour(Request $request, ClinicWorkingHour $hour): RedirectResponse
    {
        Gate::authorize('manage-configuration');
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $this->assertNoConfirmedAppointmentsOnWeekday($hour->weekday);
        $deleted = ClinicWorkingHour::query()->whereKey($hour->id)->where('version', (int) $data['version'])->delete();
        if ($deleted !== 1) {
            throw ValidationException::withMessages(['version' => 'ساعات کلینیک هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.clinic_hours_deleted', $hour, [
            'weekday' => $hour->weekday,
            'start_local' => substr((string) $hour->start_local, 0, 5),
            'end_local' => substr((string) $hour->end_local, 0, 5),
            'timezone' => $hour->timezone,
        ], ['status' => 'deleted']);

        return back()->with('success', 'بازه ساعات کلینیک حذف شد.');
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
        $overlap = WorkingHour::query()->where('practitioner_id', $data['practitioner_id'])
            ->where('weekday', $data['weekday'])
            ->where('start_local', '<', $data['end_local'])
            ->where('end_local', '>', $data['start_local'])->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_local' => 'ساعات کاری این پزشک در این روز با بازه موجود هم‌پوشانی دارد.']);
        }
        $hour = WorkingHour::query()->create($data);
        AuditLogger::record($request->user('staff'), 'configuration.practitioner_hours_created', $hour, null, ['practitioner_id' => $hour->practitioner_id]);

        return back()->with('success', 'ساعات پزشک ثبت شد.');
    }

    public function updateWorkingHour(Request $request, WorkingHour $hour): RedirectResponse
    {
        Gate::authorize('update', $hour);
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'practitioner_id' => ['required', 'uuid', 'exists:practitioners,id'],
            'weekday' => ['required', 'integer', Rule::in([1, 2, 3, 4, 6, 7])],
            'start_local' => ['required', 'date_format:H:i'],
            'end_local' => ['required', 'date_format:H:i', 'after:start_local'],
        ]);
        if ((int) $data['weekday'] !== $hour->weekday || $data['practitioner_id'] !== $hour->practitioner_id) {
            $this->assertNoConfirmedAppointmentsOnWeekday($hour->weekday, $hour->practitioner_id);
        }
        $this->assertNoConfirmedAppointmentsOutsidePeriod((int) $data['weekday'], $data['start_local'], $data['end_local'], $data['practitioner_id']);
        $overlap = WorkingHour::query()->where('id', '!=', $hour->id)
            ->where('practitioner_id', $data['practitioner_id'])->where('weekday', $data['weekday'])
            ->where('start_local', '<', $data['end_local'])->where('end_local', '>', $data['start_local'])->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_local' => 'ساعات کاری پزشک با بازه دیگری هم‌پوشانی دارد.']);
        }
        $version = (int) $data['version'];
        unset($data['version']);
        $updated = WorkingHour::query()->whereKey($hour->id)->where('version', $version)
            ->update($data + ['version' => DB::raw('version + 1'), 'updated_at' => now('UTC')]);
        if ($updated !== 1) {
            throw ValidationException::withMessages(['version' => 'ساعات پزشک هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.practitioner_hours_updated', $hour, [
            'practitioner_id' => $hour->practitioner_id,
            'weekday' => $hour->weekday,
            'start_local' => substr((string) $hour->start_local, 0, 5),
            'end_local' => substr((string) $hour->end_local, 0, 5),
        ], [
            'practitioner_id' => $data['practitioner_id'],
            'weekday' => (int) $data['weekday'],
            'start_local' => $data['start_local'],
            'end_local' => $data['end_local'],
        ]);

        return back()->with('success', 'ساعات پزشک ذخیره شد.');
    }

    public function deleteWorkingHour(Request $request, WorkingHour $hour): RedirectResponse
    {
        Gate::authorize('delete', $hour);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $this->assertNoConfirmedAppointmentsOnWeekday($hour->weekday, $hour->practitioner_id);
        $deleted = WorkingHour::query()->whereKey($hour->id)->where('version', (int) $data['version'])->delete();
        if ($deleted !== 1) {
            throw ValidationException::withMessages(['version' => 'ساعات پزشک هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.practitioner_hours_deleted', $hour, [
            'practitioner_id' => $hour->practitioner_id,
            'weekday' => $hour->weekday,
            'start_local' => substr((string) $hour->start_local, 0, 5),
            'end_local' => substr((string) $hour->end_local, 0, 5),
        ], ['status' => 'deleted']);

        return back()->with('success', 'بازه ساعات پزشک حذف شد.');
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
        $overlapQuery = ScheduleBreak::query()->where('weekday', $data['weekday'])
            ->where('start_local', '<', $data['end_local'])
            ->where('end_local', '>', $data['start_local']);
        if (($data['practitioner_id'] ?? null) === null) {
            $overlapQuery->whereNull('practitioner_id');
        } else {
            $overlapQuery->where('practitioner_id', $data['practitioner_id']);
        }
        if ($overlapQuery->exists()) {
            throw ValidationException::withMessages(['start_local' => 'وقفه با وقفه موجود در همین محدوده تداخل دارد.']);
        }
        $this->assertNoConfirmedAppointmentImpactForRecurringPeriod(
            (int) $data['weekday'],
            $data['start_local'],
            $data['end_local'],
            $data['practitioner_id'] ?? null
        );
        $data['timezone'] = 'Asia/Tehran';
        $break = ScheduleBreak::query()->create($data);
        AuditLogger::record($request->user('staff'), 'configuration.schedule_break_created', $break, null, ['practitioner_id' => $break->practitioner_id]);

        return back()->with('success', 'وقفه ثبت شد.');
    }

    public function updateBreak(Request $request, ScheduleBreak $break): RedirectResponse
    {
        Gate::authorize('manage-configuration');
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'practitioner_id' => ['nullable', 'uuid', 'exists:practitioners,id'],
            'weekday' => ['required', 'integer', Rule::in([1, 2, 3, 4, 6, 7])],
            'start_local' => ['required', 'date_format:H:i'],
            'end_local' => ['required', 'date_format:H:i', 'after:start_local'],
        ]);
        $scope = $data['practitioner_id'] ?? null;
        $overlapQuery = ScheduleBreak::query()->where('id', '!=', $break->id)
            ->where('weekday', $data['weekday'])->where('start_local', '<', $data['end_local'])
            ->where('end_local', '>', $data['start_local']);
        $scope === null ? $overlapQuery->whereNull('practitioner_id') : $overlapQuery->where('practitioner_id', $scope);
        if ($overlapQuery->exists()) {
            throw ValidationException::withMessages(['start_local' => 'وقفه با وقفه دیگری در همین محدوده تداخل دارد.']);
        }
        $this->assertNoConfirmedAppointmentImpactForRecurringPeriod((int) $data['weekday'], $data['start_local'], $data['end_local'], $scope);
        $data['timezone'] = 'Asia/Tehran';
        $version = (int) $data['version'];
        unset($data['version']);
        $updated = ScheduleBreak::query()->whereKey($break->id)->where('version', $version)
            ->update($data + ['version' => DB::raw('version + 1'), 'updated_at' => now('UTC')]);
        if ($updated !== 1) {
            throw ValidationException::withMessages(['version' => 'وقفه هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.schedule_break_updated', $break, [
            'practitioner_id' => $break->practitioner_id,
            'weekday' => $break->weekday,
            'start_local' => substr((string) $break->start_local, 0, 5),
            'end_local' => substr((string) $break->end_local, 0, 5),
        ], [
            'practitioner_id' => $scope,
            'weekday' => (int) $data['weekday'],
            'start_local' => $data['start_local'],
            'end_local' => $data['end_local'],
        ]);

        return back()->with('success', 'وقفه ذخیره شد.');
    }

    public function deleteBreak(Request $request, ScheduleBreak $break): RedirectResponse
    {
        Gate::authorize('manage-configuration');
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $deleted = ScheduleBreak::query()->whereKey($break->id)->where('version', (int) $data['version'])->delete();
        if ($deleted !== 1) {
            throw ValidationException::withMessages(['version' => 'وقفه هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.schedule_break_deleted', $break, [
            'practitioner_id' => $break->practitioner_id,
            'weekday' => $break->weekday,
            'start_local' => substr((string) $break->start_local, 0, 5),
            'end_local' => substr((string) $break->end_local, 0, 5),
        ], ['status' => 'deleted']);

        return back()->with('success', 'وقفه حذف شد.');
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
        $overlapQuery = ScheduleException::query()
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at']);
        if (($data['practitioner_id'] ?? null) === null) {
            $overlapQuery->whereNull('practitioner_id');
        } else {
            $overlapQuery->where('practitioner_id', $data['practitioner_id']);
        }
        if ($overlapQuery->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'این بازه با استثنای دیگری برای همین محدوده تداخل دارد.']);
        }
        if (in_array($data['kind'], ['closed', 'holiday'], true)) {
            $this->assertNoConfirmedAppointmentImpactForRange($data['starts_at'], $data['ends_at'], $data['practitioner_id'] ?? null);
        }
        $exception = ScheduleException::query()->create($data);
        AuditLogger::record($request->user('staff'), 'configuration.schedule_exception_created', $exception, null, ['status' => $exception->kind, 'practitioner_id' => $exception->practitioner_id]);

        return back()->with('success', 'استثنای برنامه ثبت شد. بررسی تعارض با سیاست تخصیص منابع هنوز در دسترس نیست.');
    }

    public function updateException(Request $request, ScheduleException $exception): RedirectResponse
    {
        Gate::authorize('update', $exception);
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'practitioner_id' => ['nullable', 'uuid', 'exists:practitioners,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'kind' => ['required', Rule::in(['closed', 'holiday', 'special_opening'])],
        ]);
        $startsAt = Carbon::parse($data['starts_at'], 'Asia/Tehran')->utc();
        $endsAt = Carbon::parse($data['ends_at'], 'Asia/Tehran')->utc();
        $data['starts_at'] = $startsAt;
        $data['ends_at'] = $endsAt;
        $scope = $data['practitioner_id'] ?? null;
        $overlapQuery = ScheduleException::query()->where('id', '!=', $exception->id)
            ->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at']);
        if ($scope === null) {
            $overlapQuery->whereNull('practitioner_id');
        } else {
            $overlapQuery->where('practitioner_id', $scope);
        }
        if ($overlapQuery->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'این بازه با استثنای دیگری در همین محدوده تداخل دارد.']);
        }
        if (in_array($data['kind'], ['closed', 'holiday'], true)) {
            $this->assertNoConfirmedAppointmentImpactForRange($data['starts_at'], $data['ends_at'], $scope);
        }
        $version = (int) $data['version'];
        unset($data['version']);
        $updated = ScheduleException::query()->whereKey($exception->id)->where('version', $version)
            ->update($data + ['version' => DB::raw('version + 1'), 'updated_at' => now('UTC')]);
        if ($updated !== 1) {
            throw ValidationException::withMessages(['version' => 'استثنای برنامه هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.schedule_exception_updated', $exception, [
            'status' => $exception->kind,
            'practitioner_id' => $exception->practitioner_id,
            'starts_at' => $exception->starts_at->toIso8601String(),
            'ends_at' => $exception->ends_at->toIso8601String(),
        ], [
            'status' => $data['kind'],
            'practitioner_id' => $scope,
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $endsAt->toIso8601String(),
        ]);

        return back()->with('success', 'استثنای برنامه ذخیره شد.');
    }

    public function deleteException(Request $request, ScheduleException $exception): RedirectResponse
    {
        Gate::authorize('delete', $exception);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $deleted = ScheduleException::query()->whereKey($exception->id)->where('version', (int) $data['version'])->delete();
        if ($deleted !== 1) {
            throw ValidationException::withMessages(['version' => 'استثنای برنامه هم‌زمان تغییر کرده است. صفحه را تازه‌سازی کنید.']);
        }
        AuditLogger::record($request->user('staff'), 'configuration.schedule_exception_deleted', $exception, [
            'status' => $exception->kind,
            'practitioner_id' => $exception->practitioner_id,
            'starts_at' => $exception->starts_at->toIso8601String(),
            'ends_at' => $exception->ends_at->toIso8601String(),
        ], ['status' => 'deleted']);

        return back()->with('success', 'استثنای برنامه حذف شد.');
    }

    private function assertNoConfirmedAppointmentsOutsidePeriod(int $weekday, string $start, string $end, ?string $practitionerId = null): void
    {
        $query = Appointment::query()->where('status', 'confirmed')
            ->where('start_at', '>=', now('UTC'))
            ->whereRaw("EXTRACT(ISODOW FROM start_at AT TIME ZONE 'Asia/Tehran') = ?", [$weekday])
            ->where(function ($builder) use ($start, $end): void {
                $builder->whereRaw("CAST(start_at AT TIME ZONE 'Asia/Tehran' AS time) < ?", [$start])
                    ->orWhereRaw("CAST(end_at AT TIME ZONE 'Asia/Tehran' AS time) > ?", [$end]);
            });
        if ($practitionerId !== null) {
            $query->whereHas('practitioners', fn ($builder) => $builder->whereKey($practitionerId));
        }
        $impacted = $query->orderBy('start_at')->get(['id']);
        if ($impacted->isNotEmpty()) {
            throw ValidationException::withMessages([
                'schedule' => 'تغییر ساعات، نوبت‌های تأییدشده خارج از بازه جدید دارد. تغییری ذخیره نشد. شناسه نوبت‌ها: '.$impacted->pluck('id')->implode(', '),
            ]);
        }
    }

    private function assertNoConfirmedAppointmentsOnWeekday(int $weekday, ?string $practitionerId = null): void
    {
        $query = Appointment::query()->where('status', 'confirmed')
            ->where('start_at', '>=', now('UTC'))
            ->whereRaw("EXTRACT(ISODOW FROM start_at AT TIME ZONE 'Asia/Tehran') = ?", [$weekday]);
        if ($practitionerId !== null) {
            $query->whereHas('practitioners', fn ($builder) => $builder->whereKey($practitionerId));
        }
        $impacted = $query->orderBy('start_at')->get(['id']);
        if ($impacted->isNotEmpty()) {
            throw ValidationException::withMessages([
                'schedule' => 'حذف این برنامه با نوبت‌های تأییدشده در آن روز تداخل دارد. برنامه حذف نشد. شناسه نوبت‌ها: '.$impacted->pluck('id')->implode(', '),
            ]);
        }
    }

    private function assertNoConfirmedAppointmentImpactForRecurringPeriod(int $weekday, string $start, string $end, ?string $practitionerId): void
    {
        $query = Appointment::query()
            ->where('status', 'confirmed')
            ->where('start_at', '>=', now('UTC'))
            ->whereRaw("EXTRACT(ISODOW FROM start_at AT TIME ZONE 'Asia/Tehran') = ?", [$weekday])
            ->whereRaw("CAST(start_at AT TIME ZONE 'Asia/Tehran' AS time) < ?", [$end])
            ->whereRaw("CAST(end_at AT TIME ZONE 'Asia/Tehran' AS time) > ?", [$start]);
        if ($practitionerId !== null) {
            $query->whereHas('practitioners', fn ($builder) => $builder->whereKey($practitionerId));
        }
        $impacted = $query->orderBy('start_at')->get(['id', 'start_at', 'end_at']);
        if ($impacted->isNotEmpty()) {
            throw ValidationException::withMessages([
                'schedule' => 'وقفه با نوبت‌های تأییدشده تداخل دارد. وقفه ذخیره نشد و هیچ نوبتی تغییر نکرد. شناسه نوبت‌ها: '.$impacted->pluck('id')->implode(', '),
            ]);
        }
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
