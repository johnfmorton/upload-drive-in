<?php

use App\Http\Controllers\Admin\CloudStorageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\SecuritySettingsController;
use App\Http\Controllers\CloudStorage\DropboxAuthController;
use App\Http\Controllers\CloudStorage\MicrosoftTeamsAuthController;
use App\Http\Controllers\Admin\GoogleDriveFolderController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\TwoFactorAuthController;
use Illuminate\Support\Facades\Route;

// Two Factor Authentication Routes are now handled by the LaravelAdmin2FA package
// See packages/upload-drive-in/laravel-admin-2fa/routes/web.php for the routes

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Queue Testing Routes for admin users
// TODO: Re-add rate limiting middleware once container resolution is fixed
Route::post('/queue/test', [DashboardController::class, 'testQueue'])->name('queue.test');
Route::get('/queue/test/status', [DashboardController::class, 'checkQueueTestStatus'])->name('queue.test.status');
Route::get('/queue/health', [DashboardController::class, 'getQueueHealth'])->name('queue.health');

// Admin profile routes
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

// Enhanced File Management
Route::prefix('file-manager')
    ->name('file-manager.')
    ->group(function () {
        // Static endpoints should be defined before dynamic /{file} routes to avoid collisions
        Route::get('/', [\App\Http\Controllers\Admin\FileManagerController::class, 'index'])->name('index');
        Route::post('/bulk-delete', [\App\Http\Controllers\Admin\FileManagerController::class, 'bulkDestroy'])->name('bulk-delete');
        Route::delete('/', [\App\Http\Controllers\Admin\FileManagerController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/process-pending', [\App\Http\Controllers\Admin\FileManagerController::class, 'processPending'])->name('process-pending');
        Route::post('/bulk-retry', [\App\Http\Controllers\Admin\FileManagerController::class, 'bulkRetry'])->name('bulk-retry');

        // Dynamic file-specific routes
        Route::get('/{file}', [\App\Http\Controllers\Admin\FileManagerController::class, 'show'])->name('show');
        Route::patch('/{file}', [\App\Http\Controllers\Admin\FileManagerController::class, 'update'])->name('update');
        Route::delete('/{file}', [\App\Http\Controllers\Admin\FileManagerController::class, 'destroy'])->name('destroy');
        Route::post('/{file}/retry', [\App\Http\Controllers\Admin\FileManagerController::class, 'retry'])->name('retry');

        // Rate-limited download endpoints
        Route::middleware([\App\Http\Middleware\FileDownloadRateLimitMiddleware::class . ':30,1'])
            ->group(function () {
                Route::get('/{file}/download', [\App\Http\Controllers\Admin\FileManagerController::class, 'download'])->name('download');
                Route::post('/bulk-download', [\App\Http\Controllers\Admin\FileManagerController::class, 'bulkDownload'])->name('bulk-download');
            });

        // Preview endpoints with lighter rate limiting
        Route::middleware([\App\Http\Middleware\FileDownloadRateLimitMiddleware::class . ':120,1'])
            ->group(function () {
                Route::get('/{file}/preview', [\App\Http\Controllers\Admin\FileManagerController::class, 'preview'])->name('preview');
                Route::get('/{file}/thumbnail', [\App\Http\Controllers\Admin\FileManagerController::class, 'thumbnail'])->name('thumbnail');
            });
    });

// Legacy File management (for backward compatibility)
Route::delete('/files/{file}', [DashboardController::class, 'destroy'])
    ->name('files.destroy');
Route::post('/files/process-pending', [DashboardController::class, 'processPendingUploads'])
    ->name('files.process-pending');
Route::post('/files/retry-failed', [DashboardController::class, 'retryFailedUploads'])
    ->name('files.retry-failed');

// User Management
Route::resource('/users', AdminUserController::class)
    ->only(['index', 'show', 'destroy'])
    ->names('users');
Route::post('/users', [AdminUserController::class, 'store'])
    ->name('users.store');
Route::post('/users/{user}/team', [AdminUserController::class, 'updateTeamAssignments'])
    ->name('users.team.update');

// Employee Management
Route::resource('/employees', EmployeeController::class)
    ->only(['index', 'store'])
    ->names('employees');

// Application Settings
Route::get('/settings', [AdminSettingsController::class, 'edit'])
    ->name('settings.edit');
Route::patch('/settings', [AdminSettingsController::class, 'update'])
    ->name('settings.update');
Route::delete('/settings/icon', [AdminSettingsController::class, 'destroyIcon'])
    ->name('settings.icon.destroy');

// Security Settings
Route::get('/security-settings', [SecuritySettingsController::class, 'index'])
    ->name('security.settings');
Route::put('/security-settings/registration', [SecuritySettingsController::class, 'updateRegistration'])
    ->name('security.update-registration');
Route::put('/security-settings/domain-rules', [SecuritySettingsController::class, 'updateDomainRules'])
    ->name('security.update-domain-rules');

// Cloud Storage Configuration
Route::prefix('cloud-storage')
    ->name('cloud-storage.')
    ->group(function () {
        Route::get('/', [CloudStorageController::class, 'index'])->name('index');
        Route::get('/status', [CloudStorageController::class, 'getStatus'])->name('status');
        Route::post('/reconnect', [CloudStorageController::class, 'reconnectProvider'])->name('reconnect');
        Route::post('/test', [CloudStorageController::class, 'testConnection'])->name('test');

        // Microsoft Teams routes
        Route::put('/microsoft-teams', [CloudStorageController::class, 'updateMicrosoftTeams'])->name('microsoft-teams.update');
        Route::get('/microsoft-teams/connect', [MicrosoftTeamsAuthController::class, 'connect'])->name('microsoft-teams.connect');
        Route::get('/microsoft-teams/callback', [MicrosoftTeamsAuthController::class, 'callback'])->name('microsoft-teams.callback');
        Route::post('/microsoft-teams/disconnect', [MicrosoftTeamsAuthController::class, 'disconnect'])->name('microsoft-teams.disconnect');

        // Dropbox routes
        Route::put('/dropbox', [CloudStorageController::class, 'updateDropbox'])->name('dropbox.update');
        Route::get('/dropbox/connect', [DropboxAuthController::class, 'connect'])->name('dropbox.connect');
        Route::post('/dropbox/disconnect', [DropboxAuthController::class, 'disconnect'])->name('dropbox.disconnect');

        // Google Drive: credentials, connect, callback, root folder & disconnect
        // Save client ID & secret
        Route::put('/google-drive/credentials', [CloudStorageController::class, 'updateGoogleDriveCredentials'])
            ->name('google-drive.credentials.update');
        // Connect: save credentials and redirect to Google OAuth
        Route::post('/google-drive/connect', [CloudStorageController::class, 'saveAndConnectGoogleDrive'])
            ->name('google-drive.connect');
        // OAuth callback — handled in CloudStorageController
        Route::get('/google-drive/callback', [CloudStorageController::class, 'callback'])
            ->name('google-drive.callback');
        // Update selected root folder
        Route::put('/google-drive/folder', [CloudStorageController::class, 'updateGoogleDriveRootFolder'])
            ->name('google-drive.folder.update');
        // Disconnect
        Route::post('/google-drive/disconnect', [CloudStorageController::class, 'disconnect'])
            ->name('google-drive.disconnect');
        Route::get('/google-drive/folders', [GoogleDriveFolderController::class, 'index'])->name('google-drive.folders');
        Route::get('/google-drive/folders/{folderId}', [GoogleDriveFolderController::class, 'show'])->name('google-drive.folders.show');
        Route::post('/google-drive/folders', [GoogleDriveFolderController::class, 'store'])->name('google-drive.folders.store');

        // Default provider route
        Route::put('/default', [CloudStorageController::class, 'updateDefault'])->name('default');
        
        // Provider management routes
        Route::get('/providers', [CloudStorageController::class, 'getAvailableProviders'])->name('providers');
        Route::post('/set-provider', [CloudStorageController::class, 'setUserProvider'])->name('set-provider');
        Route::get('/provider-management', [CloudStorageController::class, 'providerManagement'])->name('provider-management');
        Route::get('/providers/{provider}/details', [CloudStorageController::class, 'getProviderDetails'])->name('providers.details');
        Route::post('/providers/{provider}/validate', [CloudStorageController::class, 'validateProviderConfig'])->name('providers.validate');
        Route::put('/providers/{provider}/config', [CloudStorageController::class, 'updateProviderConfig'])->name('providers.config.update');
    });
