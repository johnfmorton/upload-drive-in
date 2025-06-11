<?php

namespace App\Providers;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ClientMiddleware;
use App\Http\Middleware\EmployeeMiddleware;
use App\Http\Middleware\TwoFactorMiddleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Add explicit route parameter binding for User
        Route::bind('user', function ($value) {
            return \App\Models\User::findOrFail($value);
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // API Routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Web Routes (including auth, public routes, etc)
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Admin Routes
            Route::middleware(['web', 'auth', AdminMiddleware::class, TwoFactorMiddleware::class])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            // Client Routes
            Route::middleware(['web', 'auth', ClientMiddleware::class])
                ->prefix('client')
                ->name('client.')
                ->group(base_path('routes/client.php'));

            // Public Employee Upload Routes (no auth)
            Route::middleware('web')
                ->prefix('u/{username}')
                ->name('public.employee.')
                ->group(base_path('routes/public-employee.php'));

            // Employee Portal Routes (protected)
            Route::middleware(['web', 'auth', EmployeeMiddleware::class])
                ->prefix('employee/{username}')
                ->name('employee.')
                ->group(base_path('routes/employee-portal.php'));
        });
    }
}
