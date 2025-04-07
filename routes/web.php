<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\PublicUploadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [PublicUploadController::class, 'index'])->name('home');
Route::post('/validate-email', [PublicUploadController::class, 'validateEmail'])->name('validate-email');
Route::get('/verify-email/{code}/{email}', [PublicUploadController::class, 'verifyEmail'])->name('verify-email');

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
        ->middleware([AdminMiddleware::class])
        ->name('admin.dashboard');

    // File management
    Route::delete('/files/{file}', [DashboardController::class, 'destroy'])
        ->middleware([AdminMiddleware::class])
        ->name('admin.files.destroy');

    // Google Drive routes
    Route::middleware([AdminMiddleware::class])->group(function () {
        Route::get('/google-drive/connect', [GoogleDriveController::class, 'connect'])->name('google-drive.connect');
        Route::get('/google-drive/callback', [GoogleDriveController::class, 'callback'])->name('google-drive.callback');
        Route::post('/google-drive/disconnect', [GoogleDriveController::class, 'disconnect'])->name('google-drive.disconnect');
    });
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
