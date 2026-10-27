<?php

declare(strict_types=1);

use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/appointments', [AppointmentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('api.v1.appointments.store');
