<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\PublicUploadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Public routes
Route::get('/', [PublicUploadController::class, 'index'])->name('home');

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

// Client Routes
Route::middleware(['auth', 'client'])
    ->prefix('client')
    ->name('client.')
    ->group(fn() => require base_path('routes/client.php'));

// Admin Routes
Route::middleware(['auth', 'admin', '2fa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(fn() => require base_path('routes/admin.php'));

// Public "drop files for employee" page (no auth)
Route::prefix('u/{username}')
    ->name('public.employee.')
    ->group(fn() => require base_path('routes/public-employee.php'));

// Employee portal (protected)
Route::middleware(['auth', 'employee'])
    ->prefix('employee/{username}')
    ->name('employee.')
    ->group(fn() => require base_path('routes/employee-portal.php'));

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
