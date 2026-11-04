<?php

declare(strict_types=1);

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PublicAvailabilityController;
use App\Http\Controllers\PublicServicesController;
use App\Http\Controllers\PublicPhoneVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/services', PublicServicesController::class)
    ->middleware('throttle:60,1')
    ->name('api.v1.services.index');

Route::get('/v1/availability/{service}', PublicAvailabilityController::class)
    ->middleware('throttle:60,1')
    ->name('api.v1.availability.show');

Route::post('/v1/phone-verification/send', [PublicPhoneVerificationController::class, 'send'])
    ->middleware(['web', 'throttle:10,1'])
    ->name('api.v1.phone-verification.send');

Route::post('/v1/phone-verification/verify', [PublicPhoneVerificationController::class, 'verify'])
    ->middleware(['web', 'throttle:20,1'])
    ->name('api.v1.phone-verification.verify');

Route::post('/v1/appointments', [AppointmentController::class, 'store'])
    ->middleware(['web', 'throttle:30,1'])
    ->name('api.v1.appointments.store');
