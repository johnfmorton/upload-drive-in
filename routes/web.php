<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicUploadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Public routes
Route::get('/', [PublicUploadController::class, 'index'])->name('home');

// Employee upload page (public access)
Route::get('/upload/{name}', [\App\Http\Controllers\PublicEmployeeUploadController::class, 'showByName'])->name('upload.employee');
Route::post('/upload/{name}', [\App\Http\Controllers\PublicEmployeeUploadController::class, 'uploadByName'])->name('upload.employee.submit');
Route::post('/upload/{name}/chunk', [\App\Http\Controllers\PublicEmployeeUploadController::class, 'chunkUpload'])->name('upload.employee.chunk');
Route::post('/upload/{name}/associate-message', [\App\Http\Controllers\PublicEmployeeUploadController::class, 'associateMessage'])->name('upload.employee.associate-message');
Route::post('/upload/{name}/batch-complete', [\App\Http\Controllers\PublicEmployeeUploadController::class, 'batchComplete'])->name('upload.employee.batch-complete');

// Token-based login route (needs to be accessible to everyone)
Route::get('/login/token/{user}', [AuthenticatedSessionController::class, 'loginViaToken'])
    ->middleware('signed')
    ->name('login.via.token');

Route::middleware(['guest'])->group(function () {
    Route::post('/validate-email', [PublicUploadController::class, 'validateEmail'])->name('validate-email');
    Route::get('/verify-email/{code}/{email}', [PublicUploadController::class, 'verifyEmail'])->name('verify-email');

    // Password Reset Routes (only request & email under guest)
    Route::get('forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])
        ->name('password.email');
});

// Expose password reset form & submission publicly so logged-in employees can use it
Route::get('reset-password/{token}', [\App\Http\Controllers\Auth\NewPasswordController::class, 'create'])
    ->name('password.reset');
Route::post('reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])
    ->name('password.store');

// Password reset for authenticated users (from profile page)
Route::post('profile/send-password-reset', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])
    ->middleware('auth')
    ->name('profile.password.reset');

// Auth routes
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(\App\Http\Middleware\PreventClientPasswordLogin::class);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Dashboard route with role-based redirection
Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->isAdmin()) {
        return redirect('/admin/dashboard');
    } elseif ($user->isClient()) {
        return redirect()->route('client.dashboard');
    } elseif ($user->isEmployee()) {
        return redirect()->route('employee.dashboard', ['username' => $user->username]);
    }

    return redirect()->route('home');
})->middleware(['auth'])->name('dashboard');

// Static info pages
Route::get('/privacy-policy', [StaticPageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms-and-conditions', [StaticPageController::class, 'termsAndConditions'])->name('terms-and-conditions');

// API Routes
Route::post('/api/uploads/batch-complete', [UploadController::class, 'batchComplete'])
    ->middleware('auth')
    ->name('uploads.batch.complete');

// Unsubscribe route
Route::get('/notifications/upload/unsubscribe/{user}', [NotificationSettingsController::class, 'unsubscribeUploads'])
    ->name('notifications.upload.unsubscribe')
    ->middleware('signed');
