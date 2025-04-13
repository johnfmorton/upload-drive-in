<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\PublicUploadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

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

// Admin routes
Route::middleware(['auth'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(\App\Http\Middleware\AdminMiddleware::class)
        ->name('admin.dashboard');

    // File management
    Route::delete('/files/{file}', [DashboardController::class, 'destroy'])
        ->middleware(\App\Http\Middleware\AdminMiddleware::class)
        ->name('admin.files.destroy');

    // User Management
    Route::resource('/users', \App\Http\Controllers\Admin\AdminUserController::class)
        ->middleware(\App\Http\Middleware\AdminMiddleware::class)
        ->only(['index', 'destroy'])
        ->names('admin.users');
    Route::post('/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])
        ->middleware(\App\Http\Middleware\AdminMiddleware::class)
        ->name('admin.users.store');
});

// Google Drive routes
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
    Route::get('/google-drive/connect', [GoogleDriveController::class, 'connect'])->name('google-drive.connect');
    Route::get('/google-drive/callback', [GoogleDriveController::class, 'callback'])->name('google-drive.callback');
    Route::post('/google-drive/disconnect', [GoogleDriveController::class, 'disconnect'])->name('google-drive.disconnect');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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
