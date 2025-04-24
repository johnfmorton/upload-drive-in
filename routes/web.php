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
Route::middleware(['guest'])->group(function () {
    Route::post('/validate-email', [PublicUploadController::class, 'validateEmail'])->name('validate-email');
    Route::get('/verify-email/{code}/{email}', [PublicUploadController::class, 'verifyEmail'])->name('verify-email');
    Route::get('/login/token/{user}', [AuthenticatedSessionController::class, 'loginViaToken'])
        ->middleware('signed')
        ->name('login.via.token');

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

// Client routes
Route::middleware(['auth'])->group(function () {
    Route::get('/upload-files', [FileUploadController::class, 'create'])->name('upload-files');
    Route::post('/upload-files', [FileUploadController::class, 'store'])->name('upload-files.store');
    Route::get('/my-uploads', [FileUploadController::class, 'index'])->name('my-uploads');
});

// Admin Routes are defined in routes/admin.php

// Dashboard route with role-based redirection
Route::get('/dashboard', function () {
    if (auth()->user()->isAdmin()) {
        return redirect('/admin/dashboard');  // Use URL instead of route name
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

// Static info pages
Route::get('/privacy-policy', [StaticPageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms-and-conditions', [StaticPageController::class, 'termsAndConditions'])->name('terms-and-conditions');

Route::middleware(['auth','ensure.owner'])
    ->prefix('admin')
    ->name('admin.')
    ->group(fn() => require base_path('routes/owner.php'));

// Public "drop files for employee" page (no auth)
Route::prefix('u/{username}')
    ->name('public.employee.')
    ->group(fn() => require base_path('routes/public-employee.php'));

// Employee portal (protected)
Route::middleware(['auth','employee'])
    ->prefix('employee/{username}')
    ->name('employee.')
    ->group(fn() => require base_path('routes/employee-portal.php'));
