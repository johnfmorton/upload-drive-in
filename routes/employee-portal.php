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
Route::get('/google-drive/connect', [UploadController::class, 'connect'])
    ->middleware('token.refresh.rate.limit')
    ->name('google-drive.connect');
Route::get('/google-drive/callback', [UploadController::class, 'callback'])
    ->middleware('token.refresh.rate.limit')
    ->name('google-drive.callback');
Route::delete('/google-drive/disconnect', [UploadController::class, 'disconnect'])
    ->middleware('token.refresh.rate.limit')
    ->name('google-drive.disconnect');
Route::put('/google-drive/folder', [UploadController::class, 'updateFolder'])
    ->middleware('token.refresh.rate.limit')
    ->name('google-drive.folder.update');
Route::get('/google-drive/folders', [\App\Http\Controllers\Employee\GoogleDriveFolderController::class, 'index'])
    ->middleware('token.refresh.rate.limit')
    ->name('google-drive.folders');
Route::get('/google-drive/folders/{folderId}', [\App\Http\Controllers\Employee\GoogleDriveFolderController::class, 'show'])
    ->middleware('token.refresh.rate.limit')
    ->name('google-drive.folders.show');
Route::post('/google-drive/folders', [\App\Http\Controllers\Employee\GoogleDriveFolderController::class, 'store'])
    ->middleware('token.refresh.rate.limit')
    ->name('google-drive.folders.store');
Route::post('/upload', [UploadController::class, 'upload'])->name('upload');

// File Manager Routes
Route::prefix('file-manager')
    ->name('file-manager.')
    ->group(function () {
        // Static endpoints first
        Route::get('/', [\App\Http\Controllers\Employee\FileManagerController::class, 'index'])->name('index');
        Route::post('/bulk-delete', [\App\Http\Controllers\Employee\FileManagerController::class, 'bulkDestroy'])->name('bulk-delete');
        Route::delete('/', [\App\Http\Controllers\Employee\FileManagerController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/bulk-retry', [\App\Http\Controllers\Employee\FileManagerController::class, 'bulkRetry'])->name('bulk-retry');

        // Dynamic routes next
        Route::get('/{fileUpload}', [\App\Http\Controllers\Employee\FileManagerController::class, 'show'])->name('show');
        Route::patch('/{fileUpload}', [\App\Http\Controllers\Employee\FileManagerController::class, 'update'])->name('update');
        Route::delete('/{fileUpload}', [\App\Http\Controllers\Employee\FileManagerController::class, 'destroy'])->name('destroy');
        Route::post('/{fileUpload}/retry', [\App\Http\Controllers\Employee\FileManagerController::class, 'retry'])->name('retry');

        // Rate-limited download endpoints
        Route::middleware([\App\Http\Middleware\FileDownloadRateLimitMiddleware::class . ':30,1'])
            ->group(function () {
                Route::get('/{fileUpload}/download', [\App\Http\Controllers\Employee\FileManagerController::class, 'download'])->name('download');
                Route::post('/bulk-download', [\App\Http\Controllers\Employee\FileManagerController::class, 'bulkDownload'])->name('bulk-download');
            });

        // Preview and thumbnail routes (rate-limited)
        Route::middleware([\App\Http\Middleware\FileDownloadRateLimitMiddleware::class . ':120,1'])
            ->group(function () {
                Route::get('/{fileUpload}/preview', [\App\Http\Controllers\Employee\FileManagerController::class, 'preview'])->name('preview');
                Route::get('/{fileUpload}/thumbnail', [\App\Http\Controllers\Employee\FileManagerController::class, 'thumbnail'])->name('thumbnail');
            });
    });

// Cloud Storage Routes
Route::get('/cloud-storage', [\App\Http\Controllers\Employee\CloudStorageController::class, 'index'])
    ->middleware('token.refresh.rate.limit')
    ->name('cloud-storage.index');
Route::get('/cloud-storage/status', [\App\Http\Controllers\Employee\CloudStorageController::class, 'getStatus'])
    ->middleware('token.refresh.rate.limit')
    ->name('cloud-storage.status');
Route::post('/cloud-storage/reconnect', [\App\Http\Controllers\Employee\CloudStorageController::class, 'reconnectProvider'])
    ->middleware('token.refresh.rate.limit')
    ->name('cloud-storage.reconnect');
Route::post('/cloud-storage/test', [\App\Http\Controllers\Employee\CloudStorageController::class, 'testConnection'])
    ->middleware('token.refresh.rate.limit')
    ->name('cloud-storage.test');

// File retry routes
Route::post('/files/retry-failed', [\App\Http\Controllers\Employee\FileManagerController::class, 'retryFailedUploads'])
    ->name('files.retry-failed');

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
