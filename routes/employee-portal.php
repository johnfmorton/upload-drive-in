<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\UploadController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\ClientManagementController;

// Employee Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Employee portal upload routes (protected by 'auth' + 'employee' middleware)
Route::get('/', [UploadController::class, 'show'])->name('upload.show');
Route::get('/google-drive/connect', [UploadController::class, 'connect'])->name('google-drive.connect');
Route::get('/google-drive/callback', [UploadController::class, 'callback'])->name('google-drive.callback');
Route::delete('/google-drive/disconnect', [UploadController::class, 'disconnect'])->name('google-drive.disconnect');
Route::put('/google-drive/folder', [UploadController::class, 'updateFolder'])->name('google-drive.folder.update');
Route::get('/google-drive/folders', [\App\Http\Controllers\Employee\GoogleDriveFolderController::class, 'index'])->name('google-drive.folders');
Route::get('/google-drive/folders/{folderId}', [\App\Http\Controllers\Employee\GoogleDriveFolderController::class, 'show'])->name('google-drive.folders.show');
Route::post('/google-drive/folders', [\App\Http\Controllers\Employee\GoogleDriveFolderController::class, 'store'])->name('google-drive.folders.store');
Route::post('/upload', [UploadController::class, 'upload'])->name('upload');

// File Manager Routes
Route::get('/file-manager', [\App\Http\Controllers\Employee\FileManagerController::class, 'index'])->name('file-manager.index');

// Cloud Storage Routes
Route::get('/cloud-storage', [\App\Http\Controllers\Employee\CloudStorageController::class, 'index'])->name('cloud-storage.index');

// Client Management Routes
Route::get('/clients', [ClientManagementController::class, 'index'])->name('clients.index');
Route::post('/clients', [ClientManagementController::class, 'store'])->name('clients.store');

// Profile Routes
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

// Email Verification Routes
Route::post('/email/verification-notification', [App\Http\Controllers\Auth\EmailVerificationNotificationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('verification.send');
Route::get('/email/verify', [App\Http\Controllers\Auth\EmailVerificationPromptController::class, 'show'])
    ->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerifyEmailController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
