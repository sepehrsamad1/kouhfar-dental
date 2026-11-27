<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PatientLookupRequest;
use App\Http\Requests\PatientStoreRequest;
use App\Http\Requests\PatientUpdateRequest;
use App\Patient;
use App\Support\AuditLogger;
use App\Support\IranianPhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PatientController
{
    public function index(PatientLookupRequest $request): View|JsonResponse
    {
        $user = $request->user('staff');

        $query = Patient::query()
            ->select(['id', 'full_name', 'mobile_normalized', 'archived_at', 'version'])
            ->when(
                ! $request->boolean('include_archived'),
                fn ($builder) => $builder->whereNull('archived_at')
            )
            ->when(
                $request->filled('q'),
                function ($builder) use ($request): void {
                    $term = trim($request->string('q')->toString());
                    $digits = IranianPhoneNumber::digitsOnly($term);

                    $builder->where(function ($search) use ($term, $digits): void {
                        $search->where('full_name', 'ilike', '%'.$term.'%');

                        if ($digits !== '') {
                            $search->orWhere('mobile_normalized', 'like', '%'.$digits.'%');
                        }
                    });
                }
            );

        if ($user->role === 'practitioner') {
            $query->whereHas(
                'appointments.practitioners.staffUser',
                fn ($builder) => $builder->whereKey($user->id)
            );
        }

        $patients = $query
            ->orderBy('full_name')
            ->paginate((int) ($request->input('per_page') ?? 20))
            ->withQueryString();

        $patients->through(fn (Patient $patient): array => $this->presentPatient($patient));

        if ($request->expectsJson()) {
            return response()->json($patients);
        }

        return view('staff.patients.index', [
            'patients' => $patients,
            'canCreate' => in_array($user->role, ['admin', 'reception'], true),
            'canUpdate' => in_array($user->role, ['admin', 'reception', 'practitioner'], true),
            'canArchive' => $user->role === 'admin',
        ]);
    }

    public function store(PatientStoreRequest $request): JsonResponse|RedirectResponse
    {
        $normalized = $request->string('mobile_normalized')->toString();
        $staff = $request->user('staff');

        [$patient, $duplicateCount] = DB::transaction(
            function () use ($request, $normalized, $staff): array {
                $this->lockMobileNumber($normalized);

                $duplicateCount = Patient::query()
                    ->where('mobile_normalized', $normalized)
                    ->count();

                if ($duplicateCount > 0 && ! $request->boolean('confirm_duplicate')) {
                    return [null, $duplicateCount];
                }

                $patient = Patient::query()->create([
                    'full_name' => trim($request->string('full_name')->toString()),
                    'mobile_normalized' => $normalized,
                ]);

                AuditLogger::record($staff, 'patient.created', $patient, null, [
                    'reason_code' => 'patient_created',
                ]);

                return [$patient, 0];
            }
        );

        if ($duplicateCount > 0) {
            return $this->duplicateResponse(
                $request,
                'برای این شماره موبایل بیمار قبلی وجود دارد. ثبت بیمار جدید فقط پس از تأیید صریح انجام می‌شود.',
                $duplicateCount
            );
        }

        if ($request->expectsJson()) {
            return response()->json($this->presentPatient($patient), 201);
        }

        return redirect()
            ->route('staff.patients.index')
            ->with('success', 'بیمار با موفقیت ثبت شد.');
    }

    public function update(PatientUpdateRequest $request, Patient $patient): JsonResponse|RedirectResponse|\Illuminate\Http\Response
    {
        Gate::forUser($request->user('staff'))->authorize('update', $patient);

        $expectedVersion = $request->integer('version');

        $normalized = $request->filled('mobile')
            ? $request->string('mobile_normalized')->toString()
            : $patient->mobile_normalized;

        $staff = $request->user('staff');

        [$updated, $duplicateCount] = DB::transaction(
            function () use (
                $request,
                $patient,
                $expectedVersion,
                $normalized,
                $staff
            ): array {
                $this->lockMobileNumber($normalized);

                $duplicateCount = Patient::query()
                    ->where('mobile_normalized', $normalized)
                    ->where($patient->getKeyName(), '!=', $patient->getKey())
                    ->count();

                if ($duplicateCount > 0 && ! $request->boolean('confirm_duplicate')) {
                    return [false, $duplicateCount];
                }

                $affected = Patient::query()
                    ->whereKey($patient->getKey())
                    ->where('version', $expectedVersion)
                    ->update([
                        'full_name' => trim($request->string('full_name')->toString()),
                        'mobile_normalized' => $normalized,
                        'version' => DB::raw('version + 1'),
                        'updated_at' => now('UTC'),
                    ]);

                if ($affected !== 1) {
                    return [false, 0];
                }

                AuditLogger::record($staff, 'patient.updated', $patient, null, [
                    'reason_code' => 'patient_updated',
                ]);

                return [true, 0];
            }
        );

        if ($duplicateCount > 0) {
            return $this->duplicateResponse(
                $request,
                'شماره موبایل واردشده برای بیمار دیگری ثبت شده است. تأیید صریح برای استفاده از شماره تکراری لازم است.',
                $duplicateCount
            );
        }

        if (! $updated) {
            $message = 'اطلاعات بیمار تغییر کرده است. صفحه را دوباره بارگذاری کنید و تغییرات خود را بررسی کنید.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 409);
            }

            return response()->view('staff.patients.conflict', [
                'message' => $message,
            ], 409);
        }

        $patient->refresh();

        if ($request->expectsJson()) {
            return response()->json($this->presentPatient($patient));
        }

        return redirect()
            ->route('staff.patients.index')
            ->with('success', 'اطلاعات بیمار به‌روزرسانی شد.');
    }

    public function archive(Request $request, Patient $patient): JsonResponse|RedirectResponse
    {
        Gate::forUser($request->user('staff'))->authorize('archive', $patient);

        $staff = $request->user('staff');

        DB::transaction(function () use ($patient, $staff): void {
            if ($patient->archived_at !== null) {
                return;
            }

            $patient->forceFill(['archived_at' => now('UTC')])->save();

            AuditLogger::record($staff, 'patient.archived', $patient, null, [
                'reason_code' => 'patient_archived',
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'بیمار بایگانی شد.']);
        }

        return redirect()
            ->route('staff.patients.index')
            ->with('success', 'بیمار بایگانی شد.');
    }

    public function revealMobile(Request $request, Patient $patient): View
    {
        Gate::forUser($request->user('staff'))->authorize('revealMobile', $patient);

        AuditLogger::record($request->user('staff'), 'patient.mobile_revealed', $patient, null, [
            'reason_code' => 'authorized_mobile_reveal',
        ]);

        return view('staff.patients.mobile', [
            'patient' => $patient,
            'mobile' => $patient->mobile_normalized,
        ]);
    }

    private function lockMobileNumber(string $normalized): void
    {
        DB::select(
            'SELECT pg_advisory_xact_lock(hashtextextended(?, 0))',
            ['kouhfar:patient-mobile:'.$normalized]
        );
    }

    private function duplicateResponse(
        Request $request,
        string $message,
        int $count
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'duplicate_warning' => true,
                'duplicate_count' => $count,
            ], 422);
        }

        return back()
            ->withInput()
            ->withErrors(['mobile' => $message])
            ->with('duplicate_warning', true)
            ->with('duplicate_count', $count);
    }

    private function presentPatient(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'full_name' => $patient->full_name,
            'mobile' => IranianPhoneNumber::mask($patient->mobile_normalized),
            'archived_at' => ($archivedAt = $patient->getRawOriginal('archived_at')) === null
                ? null
                : CarbonImmutable::parse($archivedAt)->toISOString(),
            'version' => $patient->version,
        ];
    }
}
