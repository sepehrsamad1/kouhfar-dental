<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Appointment;
use App\AppointmentEvent;
use App\Domain\Availability\AvailabilityRepository;
use App\Domain\Booking\AppointmentConcurrencyException;
use App\Domain\Booking\AppointmentStateMachine;
use App\Enums\AppointmentStatus;
use App\Service;
use App\Support\AuditLogger;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use LogicException;

final class StaffAppointmentController
{
    private const TIMEZONE = 'Asia/Tehran';

    public function index(Request $request): View
    {
        $user = $request->user('staff');
        $view = $request->string('view', 'day')->toString();
        $view = in_array($view, ['day', 'week', 'list'], true) ? $view : 'day';
        $date = $this->parseDate($request->string('date')->toString());

        $rangeStartLocal = $view === 'week'
            ? $date->startOfWeek(CarbonImmutable::SATURDAY)
            : $date->startOfDay();
        $rangeEndLocal = $view === 'week'
            ? $rangeStartLocal->addWeek()
            : $rangeStartLocal->addDay();

        $appointments = Appointment::query()
            ->with(['patient', 'service', 'practitioners'])
            ->when(
                $user->role === 'practitioner',
                fn ($query) => $query->whereHas(
                    'practitioners.staffUser',
                    fn ($relation) => $relation->whereKey($user->id)
                )
            )
            ->where('start_at', '<', $rangeEndLocal->utc())
            ->where('end_at', '>', $rangeStartLocal->utc())
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->orderBy('start_at')
            ->get();

        return view('staff.appointments.index', [
            'appointments' => $appointments,
            'view' => $view,
            'date' => $date,
            'rangeStartLocal' => $rangeStartLocal,
            'rangeEndLocal' => $rangeEndLocal,
            'statuses' => AppointmentStatus::cases(),
            'canSchedule' => in_array($user->role, ['admin', 'reception'], true),
            'isPractitioner' => $user->role === 'practitioner',
        ]);
    }

    public function confirm(Request $request, Appointment $appointment, AppointmentStateMachine $states): JsonResponse|RedirectResponse
    {
        Gate::forUser($request->user('staff'))->authorize('update', $appointment);

        try {
            DB::transaction(function () use ($request, $appointment, $states): void {
                $current = Appointment::query()->lockForUpdate()->findOrFail($appointment->getKey());

                $this->assertVersion($request, $current);
                $status = AppointmentStatus::from((string) $current->getRawOriginal('status'));
                if ($status !== AppointmentStatus::Pending) {
                    throw new LogicException('This appointment cannot be confirmed from its current state.');
                }
                $expiresAt = $current->getRawOriginal('expires_at');
                if ($expiresAt !== null && CarbonImmutable::parse((string) $expiresAt, 'UTC')->isPast()) {
                    $states->expirePendingHolds();
                    throw new LogicException('This appointment hold has expired.');
                }

                /** @var Service $service */
                $service = $current->service()->firstOrFail();
                $available = app(AvailabilityRepository::class)->forPublicService(
                    $service,
                    now('UTC')->toDateTimeImmutable(),
                    $current->getKey()
                );

                if (! collect($available)->contains(
                    fn (array $slot): bool => $slot['start_at'] === CarbonImmutable::parse((string) $current->getRawOriginal('start_at'), 'UTC')->format('Y-m-d\TH:i:s\Z')
                        && $slot['end_at'] === CarbonImmutable::parse((string) $current->getRawOriginal('end_at'), 'UTC')->format('Y-m-d\TH:i:s\Z')
                )) {
                    throw new AppointmentConcurrencyException('The appointment slot is no longer available.');
                }

                $states->transition($current, AppointmentStatus::Confirmed, 'staff', (string) $request->user('staff')->getKey(), 'staff_confirmed', $current->version);
            });
        } catch (AppointmentConcurrencyException|LogicException $exception) {
            return $this->conflict($request, $exception->getMessage());
        }

        return $this->respond($request, 'appointment confirmed');
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentStateMachine $states): JsonResponse|RedirectResponse
    {
        Gate::forUser($request->user('staff'))->authorize('cancel', $appointment);

        try {
            DB::transaction(function () use ($request, $appointment, $states): void {
                $current = Appointment::query()->lockForUpdate()->findOrFail($appointment->getKey());
                $this->assertVersion($request, $current);

                $status = AppointmentStatus::from((string) $current->getRawOriginal('status'));
                if ($status === AppointmentStatus::Confirmed) {
                    $this->assertCancellationWindow($current);
                } elseif ($status !== AppointmentStatus::Pending) {
                    throw new LogicException('This appointment cannot be cancelled from its current state.');
                }

                $states->transition($current, AppointmentStatus::Cancelled, 'staff', (string) $request->user('staff')->getKey(), 'staff_cancelled', $current->version);
            });
        } catch (AppointmentConcurrencyException|LogicException $exception) {
            return $this->conflict($request, $exception->getMessage());
        }

        return $this->respond($request, 'appointment cancelled');
    }

    public function complete(Request $request, Appointment $appointment, AppointmentStateMachine $states): JsonResponse|RedirectResponse
    {
        Gate::forUser($request->user('staff'))->authorize('complete', $appointment);

        try {
            DB::transaction(function () use ($request, $appointment, $states): void {
                $current = Appointment::query()->lockForUpdate()->findOrFail($appointment->getKey());
                $this->assertVersion($request, $current);

                $status = AppointmentStatus::from((string) $current->getRawOriginal('status'));
                $startAt = CarbonImmutable::parse((string) $current->getRawOriginal('start_at'), 'UTC');
                if ($status !== AppointmentStatus::Confirmed || $startAt->isFuture()) {
                    throw new LogicException('Only a started confirmed appointment can be completed.');
                }

                $states->transition($current, AppointmentStatus::Completed, 'staff', (string) $request->user('staff')->getKey(), 'staff_completed', $current->version);
            });
        } catch (AppointmentConcurrencyException|LogicException $exception) {
            return $this->conflict($request, $exception->getMessage());
        }

        return $this->respond($request, 'appointment completed');
    }

    public function noShow(Request $request, Appointment $appointment, AppointmentStateMachine $states): JsonResponse|RedirectResponse
    {
        Gate::forUser($request->user('staff'))->authorize('complete', $appointment);

        try {
            DB::transaction(function () use ($request, $appointment, $states): void {
                $current = Appointment::query()->lockForUpdate()->findOrFail($appointment->getKey());
                $this->assertVersion($request, $current);

                $status = AppointmentStatus::from((string) $current->getRawOriginal('status'));
                $startAt = CarbonImmutable::parse((string) $current->getRawOriginal('start_at'), 'UTC');
                if ($status !== AppointmentStatus::Confirmed || $startAt->isFuture()) {
                    throw new LogicException('Only a started confirmed appointment can be marked as no-show.');
                }

                $states->transition($current, AppointmentStatus::NoShow, 'staff', (string) $request->user('staff')->getKey(), 'staff_no_show', $current->version);
            });
        } catch (AppointmentConcurrencyException|LogicException $exception) {
            return $this->conflict($request, $exception->getMessage());
        }

        return $this->respond($request, 'appointment marked as no-show');
    }

    public function reschedule(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        Gate::forUser($request->user('staff'))->authorize('update', $appointment);

        $validated = $request->validate([
            'start_at' => ['required', 'date'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($request, $appointment, $validated): void {
                $current = Appointment::query()->with('service')->lockForUpdate()->findOrFail($appointment->getKey());
                $this->assertVersion($request, $current);

                $status = AppointmentStatus::from((string) $current->getRawOriginal('status'));
                if ($status !== AppointmentStatus::Confirmed) {
                    throw new LogicException('Only confirmed appointments can be rescheduled.');
                }
                $this->assertCancellationWindow($current);

                $newStart = new DateTimeImmutable($validated['start_at'], new DateTimeZone(self::TIMEZONE));
                /** @var Service $service */
                $service = $current->service;
                $newEnd = $newStart->modify(sprintf('+%d minutes', $service->duration_minutes));

                $available = app(AvailabilityRepository::class)->forPublicService(
                    $service,
                    now('UTC')->toDateTimeImmutable(),
                    $current->getKey()
                );
                $matches = array_values(array_filter(
                    $available,
                    static fn (array $slot): bool => $slot['start_at'] === $newStart->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')
                        && $slot['end_at'] === $newEnd->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')
                ));

                if ($matches === []) {
                    throw new AppointmentConcurrencyException('The requested reschedule slot is no longer available.');
                }

                usort($matches, static fn (array $left, array $right): int => [$left['practitioner_id'], $left['resource_id']] <=> [$right['practitioner_id'], $right['resource_id']]);
                $match = $matches[0];

                $occupiedStart = $newStart->setTimezone(new DateTimeZone('UTC'))->modify(sprintf('-%d minutes', $service->buffer_before_minutes));
                $occupiedEnd = $newEnd->setTimezone(new DateTimeZone('UTC'))->modify(sprintf('+%d minutes', $service->buffer_after_minutes));
                $range = sprintf(
                    "tstzrange('%s'::timestamptz, '%s'::timestamptz, '[)')",
                    $occupiedStart->format('Y-m-d H:i:sP'),
                    $occupiedEnd->format('Y-m-d H:i:sP'),
                );

                $affected = Appointment::query()
                    ->whereKey($current->getKey())
                    ->where('version', $current->version)
                    ->update([
                        'start_at' => $newStart->setTimezone(new DateTimeZone('UTC')),
                        'end_at' => $newEnd->setTimezone(new DateTimeZone('UTC')),
                        'version' => DB::raw('version + 1'),
                        'updated_at' => now('UTC'),
                    ]);

                if ($affected !== 1) {
                    throw new AppointmentConcurrencyException('The appointment was changed by another staff member. Refresh the calendar and try again.');
                }

                DB::table('appointment_practitioners')->where('appointment_id', $current->getKey())->delete();
                DB::table('appointment_resources')->where('appointment_id', $current->getKey())->delete();

                DB::table('appointment_practitioners')->insert([
                    'appointment_id' => $current->getKey(),
                    'practitioner_id' => $match['practitioner_id'],
                    'active' => true,
                    'occupied' => DB::raw($range),
                ]);
                DB::table('appointment_resources')->insert([
                    'appointment_id' => $current->getKey(),
                    'resource_id' => $match['resource_id'],
                    'active' => true,
                    'occupied' => DB::raw($range),
                ]);

                AppointmentEvent::query()->create([
                    'appointment_id' => $current->getKey(),
                    'actor_type' => 'staff',
                    'actor_id' => (string) $request->user('staff')->getKey(),
                    'from_status' => AppointmentStatus::Confirmed,
                    'to_status' => AppointmentStatus::Confirmed,
                    'occurred_at' => now('UTC'),
                ]);

                $current->refresh();
                AuditLogger::record($request->user('staff'), 'appointment.rescheduled', $current, [
                    'status' => AppointmentStatus::Confirmed->value,
                    'version' => $current->version - 1,
                ], [
                    'status' => AppointmentStatus::Confirmed->value,
                    'version' => $current->version,
                    'reason_code' => 'staff_rescheduled',
                ]);
            });
        } catch (AppointmentConcurrencyException|LogicException|QueryException $exception) {
            if ($exception instanceof QueryException && ! str_contains($exception->getMessage(), 'no_overlap') && $exception->getCode() !== '23P01') {
                throw $exception;
            }

            return $this->conflict($request, $exception->getMessage());
        }

        return $this->respond($request, 'appointment rescheduled');
    }

    private function parseDate(string $value): CarbonImmutable
    {
        try {
            $date = $value === '' ? CarbonImmutable::now(self::TIMEZONE) : CarbonImmutable::parse($value, self::TIMEZONE);
        } catch (\Throwable) {
            $date = CarbonImmutable::now(self::TIMEZONE);
        }

        return $date->startOfDay();
    }

    private function assertVersion(Request $request, Appointment $appointment): void
    {
        if ((int) $request->input('version') !== $appointment->version) {
            throw new AppointmentConcurrencyException('This appointment was changed by another staff member. Refresh the calendar and try again.');
        }
    }

    private function assertCancellationWindow(Appointment $appointment): void
    {
        $startAt = CarbonImmutable::parse((string) $appointment->getRawOriginal('start_at'), 'UTC');
        if (now('UTC')->greaterThanOrEqualTo($startAt->subDay())) {
            throw new LogicException('Cancellation and rescheduling are closed within 24 hours of the appointment.');
        }
    }

    private function conflict(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['code' => 'APPOINTMENT_CONFLICT', 'message' => $message], 409);
        }

        return back()->withInput()->withErrors(['appointment' => $message]);
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', 'عملیات با موفقیت انجام شد.');
    }
}
