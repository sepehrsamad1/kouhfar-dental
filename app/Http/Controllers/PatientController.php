<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PatientLookupRequest;
use App\Http\Requests\PatientStoreRequest;
use App\Http\Requests\PatientUpdateRequest;
use App\Patient;
use App\Support\AuditLogger;
use App\Support\IranianPhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController
{
    public function index(PatientLookupRequest $request): JsonResponse
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

        $patients->through(fn (Patient $patient): array => $this->presentPatient($patient, false));

        return response()->json($patients);
    }

    public function store(PatientStoreRequest $request): JsonResponse
    {
        $normalized = $request->string('mobile_normalized')->toString();

        $duplicateCount = Patient::query()
            ->where('mobile_normalized', $normalized)
            ->count();

        if ($duplicateCount > 0 && ! $request->boolean('confirm_duplicate')) {
            return response()->json([
                'message' => 'یک بیمار با این شماره موبایل از قبل وجود دارد. بررسی و تأیید صریح برای ثبت بیمار جدید لازم است.',
                'duplicate_warning' => true,
                'duplicate_count' => $duplicateCount,
            ], 422);
        }

        $staff = $request->user('staff');

        $patient = DB::transaction(function () use ($request, $normalized, $staff): Patient {
            $patient = Patient::query()->create([
                'full_name' => trim($request->string('full_name')->toString()),
                'mobile_normalized' => $normalized,
            ]);

            AuditLogger::record($staff, 'patient.created', $patient, null, [
                'reason_code' => 'patient_created',
            ]);

            return $patient;
        });

        return response()->json($this->presentPatient($patient, false), 201);
    }

    public function update(PatientUpdateRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);

        $expectedVersion = $request->integer('version');
        $normalized = $request->string('mobile_normalized')->toString();
        $staff = $request->user('staff');

        $updated = DB::transaction(function () use ($request, $patient, $expectedVersion, $normalized, $staff): bool {
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
                return false;
            }

            AuditLogger::record($staff, 'patient.updated', $patient, null, [
                'reason_code' => 'patient_updated',
            ]);

            return true;
        });

        if (! $updated) {
            return response()->json([
                'message' => 'اطلاعات بیمار تغییر کرده است. صفحه را دوباره بارگذاری کنید و تغییرات خود را بررسی کنید.',
            ], 409);
        }

        $patient->refresh();

        return response()->json($this->presentPatient($patient, false));
    }

    public function archive(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('archive', $patient);

        $staff = $request->user('staff');

        DB::transaction(function () use ($patient, $staff): void {
            $patient->forceFill(['archived_at' => now('UTC')])->save();

            AuditLogger::record($staff, 'patient.archived', $patient, null, [
                'reason_code' => 'patient_archived',
            ]);
        });

        return response()->json([
            'message' => 'بیمار بایگانی شد.',
        ]);
    }

    public function revealMobile(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('revealMobile', $patient);

        AuditLogger::record($request->user('staff'), 'patient.mobile_revealed', $patient, null, [
            'reason_code' => 'authorized_mobile_reveal',
        ]);

        return response()->json([
            'mobile' => $patient->mobile_normalized,
        ]);
    }

    private function presentPatient(Patient $patient, bool $revealMobile): array
    {
        return [
            'id' => $patient->id,
            'full_name' => $patient->full_name,
            'mobile' => $revealMobile
                ? $patient->mobile_normalized
                : IranianPhoneNumber::mask($patient->mobile_normalized),
            'archived_at' => $patient->archived_at?->toISOString(),
            'version' => $patient->version,
        ];
    }
}
