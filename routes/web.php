<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\PublicUploadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Public routes
Route::get('/', [PublicUploadController::class, 'index'])->name('home');
Route::middleware(['guest'])->group(function () {
    Route::post('/validate-email', [PublicUploadController::class, 'validateEmail'])->name('validate-email');
    Route::get('/verify-email/{code}/{email}', [PublicUploadController::class, 'verifyEmail'])->name('verify-email');
    Route::get('/login/token/{user}', [AuthenticatedSessionController::class, 'loginViaToken'])
        ->middleware('signed')
        ->name('login.via.token');

    // Password Reset Routes
    Route::get('forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])
        ->name('password.email');
    Route::get('reset-password/{token}', [\App\Http\Controllers\Auth\NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])
        ->name('password.store');
});

// Auth routes
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(\App\Http\Middleware\PreventClientPasswordLogin::class);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Client routes
Route::middleware(['auth'])->group(function () {
    Route::get('/upload-files', [FileUploadController::class, 'create'])->name('upload-files');
    Route::post('/upload-files', [FileUploadController::class, 'store'])->name('upload-files.store');
    Route::get('/my-uploads', [FileUploadController::class, 'index'])->name('my-uploads');
});

// Dashboard route with role-based redirection
Route::get('/dashboard', function () {
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('my-uploads');
})->middleware(['auth'])->name('dashboard');

// Profile routes - restructured to handle both admin and client cases
Route::middleware('auth')->group(function () {
    Route::get('/profile', function(Request $request) {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.profile.edit');
        }
        return app()->make(ProfileController::class)->edit($request);
    })->name('profile.edit');

    Route::patch('/profile', function(Request $request) {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.profile.update');
        }
        return app()->make(ProfileController::class)->update($request);
    })->name('profile.update');

    Route::delete('/profile', function(Request $request) {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.profile.destroy');
        }
        return app()->make(ProfileController::class)->destroy($request);
    })->name('profile.destroy');

    // Add this new route for account deletion confirmation
    Route::get('/profile/confirm-deletion/{code}/{email}', [ProfileController::class, 'confirmDeletion'])
        ->name('profile.confirm-deletion');

    // Password update route
    Route::put('password', [\App\Http\Controllers\Auth\PasswordController::class, 'update'])
        ->name('password.update');

    // Email verification routes
    Route::post('/email/verification-notification', function () {
        auth()->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['auth', 'throttle:6,1'])->name('verification.send');
});

// Admin routes
Route::middleware(['web', 'auth', \App\Http\Middleware\AdminMiddleware::class, '2fa'])  // Changed 'admin' to full class name
    ->prefix('admin')
    ->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
        ->name('admin.dashboard');

    // Admin profile routes with unique names
    Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('admin.profile.update');
    Route::delete('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'destroy'])->name('admin.profile.destroy');

    // File management
    Route::delete('/files/{file}', [\App\Http\Controllers\Admin\DashboardController::class, 'destroy'])
        ->name('admin.files.destroy');

    // User Management
    Route::resource('/users', \App\Http\Controllers\Admin\AdminUserController::class)
        ->only(['index', 'destroy'])
        ->names('admin.users');
    Route::post('/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])
        ->name('admin.users.store');

    // Application Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'edit'])
        ->name('admin.settings.edit');
    Route::put('/settings', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'update'])
        ->name('admin.settings.update');
    Route::delete('/settings/icon', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'destroyIcon'])
        ->name('admin.settings.icon.destroy');

    // User Management Settings
    Route::get('/user-management', [\App\Http\Controllers\Admin\UserManagementController::class, 'settings'])
        ->name('admin.user-management.settings');
    Route::put('/user-management/registration', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateRegistration'])
        ->name('admin.user-management.update-registration');
    Route::put('/user-management/domain-rules', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateDomainRules'])
        ->name('admin.user-management.update-domain-rules');
    Route::post('/user-management/clients', [\App\Http\Controllers\Admin\UserManagementController::class, 'createClient'])
        ->name('admin.user-management.create-client');
});

// Google Drive routes
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class, '2fa'])  // Changed 'admin' to full class name
    ->group(function () {
    Route::get('/google-drive/connect', [GoogleDriveController::class, 'connect'])->name('google-drive.connect');
    Route::get('/google-drive/callback', [GoogleDriveController::class, 'callback'])->name('google-drive.callback');
    Route::post('/google-drive/disconnect', [GoogleDriveController::class, 'disconnect'])->name('google-drive.disconnect');
});

// Add this outside the auth middleware group
Route::get('/profile/confirm-deletion/{code}/{email}', [ProfileController::class, 'confirmDeletion'])
    ->name('profile.confirm-deletion')
    ->middleware(['signed', 'throttle:6,1']); // Add signed URL protection and rate limiting

Route::post('/upload', [UploadController::class, 'store'])->name('chunk.upload');

// Route to associate message with uploads
Route::post('/api/uploads/associate-message', [UploadController::class, 'associateMessage'])
    ->middleware('auth') // Protect this route
    ->name('uploads.associate.message');

// Route to notify backend about batch completion (called from JS after queue completes)
Route::post('/api/uploads/batch-complete', [UploadController::class, 'batchComplete'])
    ->middleware('auth') // Protect this route
    ->name('uploads.batch.complete');

// Unsubscribe route
Route::get('/notifications/upload/unsubscribe/{user}', [NotificationSettingsController::class, 'unsubscribeUploads'])
    ->name('notifications.upload.unsubscribe')
    ->middleware('signed'); // Important: Use signed middleware
