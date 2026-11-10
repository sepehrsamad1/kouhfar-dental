<?php

declare(strict_types=1);

use App\Http\Controllers\AIAdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingOtpController;
use App\Http\Controllers\ClinicConfigurationController;
use App\Http\Controllers\ContentItemController;
use App\Http\Controllers\DeletionRequestController;
use App\Http\Controllers\MediaAssetController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\SmartSmileController;
use App\Http\Controllers\StaffAppointmentController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Middleware\PreventStoreCache;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/booking/availability/{service}', AvailabilityController::class)->middleware('throttle:60,1')->name('booking.availability');
Route::get('/content/{contentKey}/{locale}', [ContentItemController::class, 'show'])->where('locale', 'fa|en')->name('content.show');
Route::post('/api/v1/assistant', AssistantController::class)->middleware('throttle:20,1')->name('assistant');
Route::post('/api/v1/booking-otp/issue', [BookingOtpController::class, 'issue'])->middleware(['web', ValidateCsrfToken::class, 'throttle:30,1'])->name('api.v1.booking-otp.issue');
Route::post('/api/v1/booking-otp/verify', [BookingOtpController::class, 'verify'])->middleware(['web', ValidateCsrfToken::class, 'throttle:30,1'])->name('api.v1.booking-otp.verify');
Route::post('/api/v1/appointments', [AppointmentController::class, 'store'])->middleware(['web', ValidateCsrfToken::class, 'throttle:30,1'])->name('api.v1.appointments.store');
Route::post('/api/v1/smart-smile/consent', [SmartSmileController::class, 'consent'])->middleware('throttle:10,1')->name('smart-smile.consent');
Route::post('/api/v1/smart-smile/upload', [SmartSmileController::class, 'upload'])->middleware('throttle:5,1')->name('smart-smile.upload');
Route::post('/api/v1/smart-smile/status', [SmartSmileController::class, 'status'])->middleware('throttle:30,1')->name('smart-smile.status');
Route::delete('/api/v1/smart-smile', [SmartSmileController::class, 'delete'])->middleware('throttle:10,1')->name('smart-smile.delete');

Route::middleware('guest:staff')->group(function (): void {
    Route::get('/staff/login', [StaffAuthController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffAuthController::class, 'store'])->middleware('throttle:10,1')->name('staff.login.store');
    Route::get('/staff/mfa/setup', [StaffAuthController::class, 'setup'])->name('staff.mfa.setup');
    Route::post('/staff/mfa/setup', [StaffAuthController::class, 'completeSetup'])->middleware('throttle:10,1')->name('staff.mfa.setup.complete');
    Route::get('/staff/mfa/challenge', [StaffAuthController::class, 'challenge'])->name('staff.mfa.challenge');
    Route::post('/staff/mfa/challenge', [StaffAuthController::class, 'verify'])->middleware('throttle:10,1')->name('staff.mfa.verify');
});

Route::middleware(['auth:staff', 'staff.session.fresh', PreventStoreCache::class])->group(function (): void {
    Route::view('/staff', 'staff.dashboard')->name('staff.dashboard');
    Route::get('/staff/ai', [AIAdminController::class, 'index'])->name('staff.ai.index');
    Route::patch('/staff/ai', [AIAdminController::class, 'update'])->name('staff.ai.update');
    Route::post('/staff/media', [MediaAssetController::class, 'store'])->name('staff.media.store');
    Route::get('/staff/media/{mediaAsset}/download', [MediaAssetController::class, 'download'])->middleware('signed')->name('staff.media.download');
    Route::delete('/staff/media/{mediaAsset}', [MediaAssetController::class, 'delete'])->name('staff.media.delete');
    Route::post('/staff/logout', [StaffAuthController::class, 'destroy'])->name('staff.logout');
    Route::get('/staff/mfa/recovery-codes', [StaffAuthController::class, 'recoveryCodes'])->name('staff.mfa.recovery-codes');
    Route::post('/staff/deletion-requests/{deletionRequest}/review', [DeletionRequestController::class, 'review'])->name('staff.deletion-requests.review');
    Route::post('/staff/deletion-requests/{deletionRequest}/close', [DeletionRequestController::class, 'close'])->name('staff.deletion-requests.close');

    Route::get('/staff/admin/audit', function () {
        Gate::authorize('view-audit-log');

        return response()->noContent();
    })->name('staff.admin.audit');

    Route::get('/staff/content', [ContentItemController::class, 'index'])->name('staff.content.index');
    Route::get('/staff/content/create', [ContentItemController::class, 'create'])->name('staff.content.create');
    Route::post('/staff/content', [ContentItemController::class, 'store'])->name('staff.content.store');
    Route::get('/staff/content/{contentItem}/edit', [ContentItemController::class, 'edit'])->name('staff.content.edit');
    Route::patch('/staff/content/{contentItem}', [ContentItemController::class, 'update'])->name('staff.content.update');
    Route::get('/staff/content/{contentItem}/preview', [ContentItemController::class, 'preview'])->name('staff.content.preview');
    Route::post('/staff/content/{contentItem}/review', [ContentItemController::class, 'submitReview'])->name('staff.content.review');
    Route::post('/staff/content/{contentItem}/approve', [ContentItemController::class, 'approve'])->name('staff.content.approve');
    Route::post('/staff/content/{contentItem}/publish', [ContentItemController::class, 'publish'])->name('staff.content.publish');
    Route::post('/staff/content/{contentItem}/archive', [ContentItemController::class, 'archive'])->name('staff.content.archive');
    Route::post('/staff/content/{contentItem}/version', [ContentItemController::class, 'createVersion'])->name('staff.content.version');

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
