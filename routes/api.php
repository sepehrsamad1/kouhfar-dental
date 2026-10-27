<?php

declare(strict_types=1);

use App\Http\Controllers\PublicAppointmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:10,1')->group(function (): void {
    Route::post('/booking/appointments', [PublicAppointmentController::class, 'store'])
        ->name('api.v1.booking.appointments.store');
});
