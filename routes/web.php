<?php

use App\Http\Controllers\PublicUploadController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;

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

// Google Drive unified callback (no auth middleware - handles authentication internally)
Route::get('/google-drive/callback', [\App\Http\Controllers\GoogleDriveUnifiedCallbackController::class, 'callback'])
    ->name('google-drive.unified-callback');

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

// File preview and thumbnail routes (accessible to all authenticated users)
Route::middleware(['auth', \App\Http\Middleware\FileDownloadRateLimitMiddleware::class . ':120,1'])->group(function () {
    Route::get('/files/{file}/preview', [\App\Http\Controllers\Admin\FileManagerController::class, 'preview'])
        ->name('files.preview');
    Route::get('/files/{file}/thumbnail', [\App\Http\Controllers\Admin\FileManagerController::class, 'thumbnail'])
        ->name('files.thumbnail');
});

// Health check routes
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'check'])->name('health.check');
Route::get('/health/detailed', [\App\Http\Controllers\HealthController::class, 'detailed'])->name('health.detailed');

// Temporary debug route - remove after debugging
Route::get('/debug-setup-status', function () {
    $setupService = app(\App\Services\SetupService::class);
    
    // Get setup checks configuration
    $checks = config('setup.checks', []);
    
    // Manually check each condition
    $adminExists = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->exists();
    $usersTableExists = \Illuminate\Support\Facades\Schema::hasTable('users');
    
    // Check assets
    $assetService = app(\App\Services\AssetValidationService::class);
    $assetsValid = $assetService->areAssetRequirementsMet();
    
    // Check cloud storage
    $googleClientId = config('services.google.client_id');
    $googleClientSecret = config('services.google.client_secret');
    $cloudStorageConfigured = !empty($googleClientId) && !empty($googleClientSecret);
    
    return response()->json([
        'setup_required' => $setupService->isSetupRequired(),
        'setup_complete' => $setupService->isSetupComplete(),
        'current_step' => $setupService->getSetupStep(),
        'individual_checks' => [
            'admin_users_count' => \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->count(),
            'admin_exists' => $adminExists,
            'users_table_exists' => $usersTableExists,
            'assets_valid' => $assetsValid,
            'cloud_storage_configured' => $cloudStorageConfigured,
        ],
        'setup_checks_config' => $checks,
        'environment' => [
            'SETUP_BOOTSTRAP_CHECKS' => config('setup.bootstrap_checks'),
            'SETUP_CACHE_STATE' => config('setup.cache_state'),
        ]
    ]);
})->name('debug.setup.status');

// Force reset setup state - remove after debugging
Route::get('/force-reset-setup', function () {
    try {
        // Remove setup state file
        $stateFile = storage_path('app/setup/setup-state.json');
        if (file_exists($stateFile)) {
            unlink($stateFile);
            $stateFileRemoved = true;
        } else {
            $stateFileRemoved = false;
        }
        
        // Remove backup files
        $backupDir = storage_path('app/setup/backups');
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $backupsRemoved = count($files);
        } else {
            $backupsRemoved = 0;
        }
        
        // Clear caches
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        
        // Clear setup cache specifically
        \Illuminate\Support\Facades\Cache::forget('setup_state_required');
        \Illuminate\Support\Facades\Cache::forget('setup_state_complete');
        
        return response()->json([
            'success' => true,
            'message' => 'Setup state reset successfully',
            'state_file_removed' => $stateFileRemoved,
            'backups_removed' => $backupsRemoved,
            'next_step' => 'Visit home page - should redirect to setup'
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
})->name('debug.reset.setup');

// Detailed setup logic debug - remove after debugging
Route::get('/debug-setup-logic', function () {
    try {
        // Get setup service
        $setupService = app(\App\Services\SetupService::class);
        
        // Check each condition step by step
        $checks = config('setup.checks', []);
        
        // Admin user check
        $adminCheckEnabled = $checks['admin_user_exists'] ?? true;
        $adminExists = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->exists();
        $adminCheckPasses = !$adminCheckEnabled || $adminExists;
        
        // Cloud storage check
        $cloudCheckEnabled = $checks['cloud_storage_configured'] ?? true;
        $googleClientId = config('services.google.client_id');
        $googleClientSecret = config('services.google.client_secret');
        $cloudStorageConfigured = !empty($googleClientId) && !empty($googleClientSecret);
        $cloudCheckPasses = !$cloudCheckEnabled || $cloudStorageConfigured;
        
        // Assets check
        $assetCheckEnabled = $checks['asset_validation'] ?? true;
        $assetService = app(\App\Services\AssetValidationService::class);
        $assetsValid = $assetService->areAssetRequirementsMet();
        $assetCheckPasses = !$assetCheckEnabled || $assetsValid;
        
        // Database checks
        $dbConnectivityEnabled = $checks['database_connectivity'] ?? true;
        $migrationsEnabled = $checks['migrations_run'] ?? true;
        $usersTableExists = \Illuminate\Support\Facades\Schema::hasTable('users');
        
        $dbConnectivityPasses = true; // If we got here, DB is connected
        $migrationsPass = !$migrationsEnabled || $usersTableExists;
        
        // Overall logic
        $allChecksPassed = $adminCheckPasses && $cloudCheckPasses && $assetCheckPasses && $dbConnectivityPasses && $migrationsPass;
        
        return response()->json([
            'setup_checks_config' => $checks,
            'detailed_checks' => [
                'admin_check' => [
                    'enabled' => $adminCheckEnabled,
                    'admin_exists' => $adminExists,
                    'passes' => $adminCheckPasses,
                    'logic' => $adminCheckEnabled ? 'Admin must exist' : 'Check disabled'
                ],
                'cloud_check' => [
                    'enabled' => $cloudCheckEnabled,
                    'client_id' => $googleClientId ? 'SET' : 'NOT SET',
                    'client_secret' => $googleClientSecret ? 'SET' : 'NOT SET',
                    'configured' => $cloudStorageConfigured,
                    'passes' => $cloudCheckPasses,
                    'logic' => $cloudCheckEnabled ? 'Cloud storage must be configured' : 'Check disabled'
                ],
                'asset_check' => [
                    'enabled' => $assetCheckEnabled,
                    'valid' => $assetsValid,
                    'passes' => $assetCheckPasses
                ],
                'database_checks' => [
                    'connectivity_enabled' => $dbConnectivityEnabled,
                    'migrations_enabled' => $migrationsEnabled,
                    'users_table_exists' => $usersTableExists,
                    'connectivity_passes' => $dbConnectivityPasses,
                    'migrations_pass' => $migrationsPass
                ]
            ],
            'logic_summary' => [
                'all_checks_passed' => $allChecksPassed,
                'should_setup_be_complete' => $allChecksPassed,
                'actual_setup_complete' => $setupService->isSetupComplete(),
                'actual_setup_required' => $setupService->isSetupRequired()
            ]
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('debug.setup.logic');
