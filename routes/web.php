<?php

declare(strict_types=1);

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ClinicConfigurationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\StaffAppointmentController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Middleware\PreventStoreCache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/booking/availability/{service}', AvailabilityController::class)->middleware('throttle:60,1')->name('booking.availability');

Route::middleware('guest:staff')->group(function (): void {
    Route::get('/staff/login', [StaffAuthController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffAuthController::class, 'store'])->name('staff.login.store');
});

Route::middleware(['auth:staff', 'staff.session.fresh', PreventStoreCache::class])->group(function (): void {
    Route::view('/staff', 'staff.dashboard')->name('staff.dashboard');
    Route::post('/staff/logout', [StaffAuthController::class, 'destroy'])->name('staff.logout');
    Route::get('/staff/admin/audit', function () {
        Gate::authorize('view-audit-log');

        return response()->noContent();
    })->name('staff.admin.audit');

    Route::get('/staff/configuration', [ClinicConfigurationController::class, 'index'])->name('staff.configuration.index');
    Route::post('/staff/configuration/services', [ClinicConfigurationController::class, 'storeService'])->name('staff.configuration.services.store');
    Route::patch('/staff/configuration/services/{service}', [ClinicConfigurationController::class, 'updateService'])->name('staff.configuration.services.update');
    Route::post('/staff/configuration/practitioners', [ClinicConfigurationController::class, 'storePractitioner'])->name('staff.configuration.practitioners.store');
    Route::patch('/staff/configuration/practitioners/{practitioner}', [ClinicConfigurationController::class, 'updatePractitioner'])->name('staff.configuration.practitioners.update');
    Route::patch('/staff/configuration/practitioners/{practitioner}/services', [ClinicConfigurationController::class, 'updatePractitionerServices'])->name('staff.configuration.practitioners.services.update');
    Route::post('/staff/configuration/resources', [ClinicConfigurationController::class, 'storeResource'])->name('staff.configuration.resources.store');
    Route::patch('/staff/configuration/resources/{resource}', [ClinicConfigurationController::class, 'updateResource'])->name('staff.configuration.resources.update');
    Route::post('/staff/configuration/clinic-hours', [ClinicConfigurationController::class, 'storeClinicHour'])->name('staff.configuration.clinic-hours.store');
    Route::patch('/staff/configuration/clinic-hours/{hour}', [ClinicConfigurationController::class, 'updateClinicHour'])->name('staff.configuration.clinic-hours.update');
    Route::delete('/staff/configuration/clinic-hours/{hour}', [ClinicConfigurationController::class, 'deleteClinicHour'])->name('staff.configuration.clinic-hours.delete');
    Route::post('/staff/configuration/working-hours', [ClinicConfigurationController::class, 'storeWorkingHour'])->name('staff.configuration.working-hours.store');
    Route::patch('/staff/configuration/working-hours/{hour}', [ClinicConfigurationController::class, 'updateWorkingHour'])->name('staff.configuration.working-hours.update');
    Route::delete('/staff/configuration/working-hours/{hour}', [ClinicConfigurationController::class, 'deleteWorkingHour'])->name('staff.configuration.working-hours.delete');
    Route::post('/staff/configuration/breaks', [ClinicConfigurationController::class, 'storeBreak'])->name('staff.configuration.breaks.store');
    Route::patch('/staff/configuration/breaks/{break}', [ClinicConfigurationController::class, 'updateBreak'])->name('staff.configuration.breaks.update');
    Route::delete('/staff/configuration/breaks/{break}', [ClinicConfigurationController::class, 'deleteBreak'])->name('staff.configuration.breaks.delete');
    Route::post('/staff/configuration/exceptions', [ClinicConfigurationController::class, 'storeException'])->name('staff.configuration.exceptions.store');
    Route::patch('/staff/configuration/exceptions/{exception}', [ClinicConfigurationController::class, 'updateException'])->name('staff.configuration.exceptions.update');
    Route::delete('/staff/configuration/exceptions/{exception}', [ClinicConfigurationController::class, 'deleteException'])->name('staff.configuration.exceptions.delete');

    Route::get('/staff/appointments', [StaffAppointmentController::class, 'index'])->name('staff.appointments.index');
    Route::post('/staff/appointments/{appointment}/confirm', [StaffAppointmentController::class, 'confirm'])->name('staff.appointments.confirm');
    Route::post('/staff/appointments/{appointment}/cancel', [StaffAppointmentController::class, 'cancel'])->name('staff.appointments.cancel');
    Route::post('/staff/appointments/{appointment}/complete', [StaffAppointmentController::class, 'complete'])->name('staff.appointments.complete');
    Route::post('/staff/appointments/{appointment}/no-show', [StaffAppointmentController::class, 'noShow'])->name('staff.appointments.no-show');
    Route::patch('/staff/appointments/{appointment}/reschedule', [StaffAppointmentController::class, 'reschedule'])->name('staff.appointments.reschedule');

    Route::get('/staff/patients', [PatientController::class, 'index'])->name('staff.patients.index');
    Route::post('/staff/patients', [PatientController::class, 'store'])->name('staff.patients.store');
    Route::patch('/staff/patients/{patient}', [PatientController::class, 'update'])->name('staff.patients.update');
    Route::post('/staff/patients/{patient}/archive', [PatientController::class, 'archive'])->name('staff.patients.archive');
    Route::get('/staff/patients/{patient}/mobile', [PatientController::class, 'revealMobile'])->name('staff.patients.reveal-mobile');
});
