<?php

use App\Http\Middleware\EnsureStaffSessionIsFresh;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('appointments:expire-pending')->everyMinute()->withoutOverlapping(5);
        $schedule->command('notifications:dispatch-due')->everyMinute()->withoutOverlapping(5);
        $schedule->command('media:purge-expired')->dailyAt('03:15')->timezone('Asia/Tehran')->withoutOverlapping(30);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (): string => route('staff.login'));
        $middleware->alias([
            'staff.session.fresh' => EnsureStaffSessionIsFresh::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
