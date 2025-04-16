<?php

use Illuminate\Support\Facades\Route;
use UploadDriveIn\LaravelAdmin2FA\Http\Controllers\TwoFactorAuthController;

Route::middleware(['web', 'auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
    // 2FA Setup Routes
    Route::get('/admin/2fa/setup', [TwoFactorAuthController::class, 'setup'])
        ->name('admin.2fa.setup');
    Route::post('/admin/2fa/enable', [TwoFactorAuthController::class, 'enable'])
        ->name('admin.2fa.enable');
    Route::post('/admin/2fa/disable', [TwoFactorAuthController::class, 'disable'])
        ->name('admin.2fa.disable');

    // 2FA Verification Routes
    Route::get('/admin/2fa/verify', [TwoFactorAuthController::class, 'showVerifyForm'])
        ->name('admin.2fa.verify');
    Route::post('/admin/2fa/verify', [TwoFactorAuthController::class, 'verify'])
        ->name('admin.2fa.verify.store');
});
