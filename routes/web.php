<?php

declare(strict_types=1);

use App\Http\Controllers\StaffAuthController;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest:staff')->group(function (): void {
    Route::get('/staff/login', [StaffAuthController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffAuthController::class, 'store'])->name('staff.login.store');
});

Route::middleware(['auth:staff', 'staff.session.fresh'])->group(function (): void {
    Route::view('/staff', 'staff.dashboard')->name('staff.dashboard');
    Route::post('/staff/logout', [StaffAuthController::class, 'destroy'])->name('staff.logout');
    Route::get('/staff/admin/audit', function () {
        Gate::authorize('view-audit-log');

        return response()->noContent();
    })->name('staff.admin.audit');
});
