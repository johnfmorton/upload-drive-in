<?php

use App\Http\Controllers\PublicUploadController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [PublicUploadController::class, 'index'])->name('home')->middleware(\App\Http\Middleware\SetupDetectionMiddleware::class);

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

// Google Drive unified callback (no auth middleware - handles authentication internally)
Route::get('/google-drive/callback', [\App\Http\Controllers\GoogleDriveUnifiedCallbackController::class, 'callback'])
    ->name('google-drive.unified-callback');

// Auth routes
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
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

// File preview and thumbnail routes (accessible to all authenticated users)
Route::middleware(['auth', \App\Http\Middleware\FileDownloadRateLimitMiddleware::class . ':120,1'])->group(function () {
    Route::get('/files/{file}/preview', [\App\Http\Controllers\Admin\FileManagerController::class, 'preview'])
        ->name('files.preview');
    Route::get('/files/{file}/thumbnail', [\App\Http\Controllers\Admin\FileManagerController::class, 'thumbnail'])
        ->name('files.thumbnail');
    Route::get('/files/{file}/download', [\App\Http\Controllers\Admin\FileManagerController::class, 'download'])
        ->name('files.download');
});

// Health check routes
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'check'])->name('health.check');
Route::get('/health/detailed', [\App\Http\Controllers\HealthController::class, 'detailed'])->name('health.detailed');

// Cloud storage health check routes
Route::prefix('health/cloud-storage')->name('health.cloud-storage.')->group(function () {
    Route::get('/basic', [\App\Http\Controllers\CloudStorageHealthController::class, 'basic'])->name('basic');
    Route::get('/comprehensive', [\App\Http\Controllers\CloudStorageHealthController::class, 'comprehensive'])->name('comprehensive');
    Route::get('/provider/{provider}', [\App\Http\Controllers\CloudStorageHealthController::class, 'provider'])->name('provider');
    Route::get('/configuration', [\App\Http\Controllers\CloudStorageHealthController::class, 'configuration'])->name('configuration');
    Route::get('/readiness', [\App\Http\Controllers\CloudStorageHealthController::class, 'readiness'])->name('readiness');
    Route::get('/liveness', [\App\Http\Controllers\CloudStorageHealthController::class, 'liveness'])->name('liveness');
});

// Authenticated cloud storage health routes
Route::middleware(['auth'])->group(function () {
    Route::get('/health/cloud-storage/user', [\App\Http\Controllers\CloudStorageHealthController::class, 'user'])->name('health.cloud-storage.user');
});

// Token Monitoring Dashboard Routes
Route::middleware(['auth', 'admin'])->prefix('admin/token-monitoring')->name('admin.token-monitoring.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\TokenMonitoringController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard-data', [\App\Http\Controllers\Admin\TokenMonitoringController::class, 'dashboardData'])->name('dashboard.data');
    Route::get('/performance-metrics', [\App\Http\Controllers\Admin\TokenMonitoringController::class, 'performanceMetrics'])->name('performance.metrics');
    Route::get('/log-analysis-queries', [\App\Http\Controllers\Admin\TokenMonitoringController::class, 'logAnalysisQueries'])->name('log.analysis.queries');
    Route::get('/export', [\App\Http\Controllers\Admin\TokenMonitoringController::class, 'exportData'])->name('export');
    Route::post('/reset-metrics', [\App\Http\Controllers\Admin\TokenMonitoringController::class, 'resetMetrics'])->name('reset.metrics');
    Route::get('/system-status', [\App\Http\Controllers\Admin\TokenMonitoringController::class, 'systemStatus'])->name('system.status');
    Route::get('/health-trends', [\App\Http\Controllers\Admin\TokenMonitoringController::class, 'healthTrends'])->name('health.trends');
    Route::get('/recent-operations', [\App\Http\Controllers\Admin\TokenMonitoringController::class, 'recentOperations'])->name('recent.operations');
});

// Token Refresh Configuration Routes
Route::middleware(['auth', 'admin'])->prefix('admin/token-refresh')->name('admin.token-refresh.')->group(function () {
    Route::get('/config', [\App\Http\Controllers\Admin\TokenRefreshConfigController::class, 'index'])->name('config');
    Route::post('/update-setting', [\App\Http\Controllers\Admin\TokenRefreshConfigController::class, 'updateSetting'])->name('update-setting');
    Route::post('/toggle-feature', [\App\Http\Controllers\Admin\TokenRefreshConfigController::class, 'toggleFeature'])->name('toggle-feature');
    Route::post('/clear-cache', [\App\Http\Controllers\Admin\TokenRefreshConfigController::class, 'clearCache'])->name('clear-cache');
    Route::get('/status', [\App\Http\Controllers\Admin\TokenRefreshConfigController::class, 'getStatus'])->name('status');
});

// Cloud storage dashboard routes
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard/cloud-storage-status', [\App\Http\Controllers\CloudStorageDashboardController::class, 'getStatus'])
        ->middleware('token.refresh.rate.limit')
        ->name('admin.dashboard.cloud-storage-status');
    Route::get('/admin/dashboard/cloud-storage/{provider}/errors', [\App\Http\Controllers\CloudStorageDashboardController::class, 'getProviderErrors'])
        ->middleware('token.refresh.rate.limit')
        ->name('admin.dashboard.cloud-storage.errors');
    Route::post('/admin/dashboard/cloud-storage/{provider}/health-check', [\App\Http\Controllers\CloudStorageDashboardController::class, 'checkHealth'])
        ->middleware('token.refresh.rate.limit')
        ->name('admin.dashboard.cloud-storage.health-check');
    
    // File manager bulk retry routes
    Route::post('/admin/file-manager/bulk-retry', [\App\Http\Controllers\FileManagerBulkRetryController::class, 'bulkRetry'])
        ->name('admin.file-manager.bulk-retry');
    Route::post('/admin/file-manager/uploads/{upload}/retry', [\App\Http\Controllers\FileManagerBulkRetryController::class, 'retryUpload'])
        ->name('admin.file-manager.retry-upload');
});

// Public Queue Testing Routes (for setup instructions)
Route::post('/setup/queue/test', [\App\Http\Controllers\SetupController::class, 'testQueue'])
    ->name('setup.queue.test')
    ->middleware(['require.setup.enabled']);
Route::get('/setup/queue/test/status', [\App\Http\Controllers\SetupController::class, 'checkQueueTestStatus'])
    ->name('setup.queue.test.status')
    ->middleware(['require.setup.enabled']);
