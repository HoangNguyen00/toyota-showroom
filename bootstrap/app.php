<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'auth', 'ensure.active', 'role:customer'])
                ->name('customer.')
                ->group(base_path('routes/customer.php'));
            // Public booking routes: guest + customer, name prefix appointments.*
            Route::middleware(['web'])
                ->name('appointments.')
                ->group(base_path('routes/appointments.php'));
            Route::middleware(['web', 'auth', 'ensure.active', 'role:sales,admin'])
                ->prefix('staff')->name('staff.')
                ->group(base_path('routes/staff.php'));
            Route::middleware(['web', 'auth', 'ensure.active', 'role:admin'])
                ->prefix('admin')->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth', 'ensure.active']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('account'));
        $middleware->web(append: [EnsureAccountActive::class]);
        $middleware->alias([
            'ensure.active' => EnsureAccountActive::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
