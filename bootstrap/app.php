<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/setup.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'client' => \App\Http\Middleware\ClientMiddleware::class,
            'employee' => \App\Http\Middleware\EmployeeMiddleware::class,
            'prevent.client.password.login' => \App\Http\Middleware\PreventClientPasswordLogin::class,
            '2fa' => \UploadDriveIn\LaravelAdmin2FA\Http\Middleware\RequireTwoFactorAuth::class,
            'file.download.throttle' => \App\Http\Middleware\FileDownloadRateLimitMiddleware::class,
            'setup.status.throttle' => \App\Http\Middleware\SetupStatusRateLimitMiddleware::class,
            'require.setup' => \App\Http\Middleware\RequireSetupMiddleware::class,
            'setup.detection' => \App\Http\Middleware\SetupDetectionMiddleware::class,
            'require.setup.enabled' => \App\Http\Middleware\RequireSetupEnabledMiddleware::class,
            'extend.setup.session' => \App\Http\Middleware\ExtendSetupSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
