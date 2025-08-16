<?php

use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Setup Routes
|--------------------------------------------------------------------------
|
| These routes handle the initial setup wizard for the application.
| They are only accessible when setup is required and are protected
| by the SetupCompleteMiddleware to prevent access after setup completion.
|
*/

Route::middleware(['web', \App\Http\Middleware\RequireSetupMiddleware::class, \App\Http\Middleware\ExtendSetupSession::class])->prefix('setup')->name('setup.')->group(function () {
    
    // Asset build instructions - first step for new installations
    Route::get('/assets', [SetupController::class, 'showAssetBuildInstructions'])->name('assets');
    
    // Welcome screen - entry point for setup wizard
    Route::get('/', [SetupController::class, 'welcome'])->name('welcome');
    Route::get('/welcome', [SetupController::class, 'welcome'])->name('welcome.alt');
    
    // Database configuration step
    Route::get('/database', [SetupController::class, 'showDatabaseForm'])->name('database');
    Route::post('/database', [SetupController::class, 'configureDatabase'])->name('database.configure');
    
    // Admin user creation step
    Route::get('/admin', [SetupController::class, 'showAdminForm'])->name('admin');
    Route::post('/admin', [SetupController::class, 'createAdmin'])->name('admin.create');
    
    // Cloud storage configuration step
    Route::get('/storage', [SetupController::class, 'showStorageForm'])->name('storage');
    Route::post('/storage', [SetupController::class, 'configureStorage'])->name('storage.configure');
    
    // Setup completion step
    Route::get('/complete', [SetupController::class, 'showComplete'])->name('complete');
    Route::post('/complete', [SetupController::class, 'complete'])->name('finish');
    
    // AJAX endpoints for real-time validation and testing
    Route::post('/ajax/check-assets', [SetupController::class, 'checkAssetBuildStatus'])->name('ajax.check-assets');
    Route::post('/ajax/test-database', [SetupController::class, 'testDatabaseConnection'])->name('ajax.test-database');
    Route::post('/ajax/test-storage', [SetupController::class, 'testStorageConnection'])->name('ajax.test-storage');
    Route::post('/ajax/validate-email', [SetupController::class, 'validateEmail'])->name('ajax.validate-email');
    Route::post('/ajax/validate-database-field', [SetupController::class, 'validateDatabaseField'])->name('ajax.validate-database-field');
    Route::get('/ajax/database-config-hints', [SetupController::class, 'getDatabaseConfigHints'])->name('ajax.database-config-hints');
    Route::post('/ajax/refresh-csrf-token', [SetupController::class, 'refreshCsrfToken'])->name('ajax.refresh-csrf-token');
    
    // Setup recovery and state management endpoints
    Route::get('/ajax/recovery-info', [SetupController::class, 'getRecoveryInfo'])->name('ajax.recovery-info');
    Route::post('/ajax/restore-backup', [SetupController::class, 'restoreFromBackup'])->name('ajax.restore-backup');
    Route::post('/ajax/force-recovery', [SetupController::class, 'forceRecovery'])->name('ajax.force-recovery');
    
    // Dynamic step routing for better UX
    Route::get('/step/{step}', function (string $step) {
        return match ($step) {
            'assets' => redirect()->route('setup.assets'),
            'welcome' => redirect()->route('setup.welcome'),
            'database' => redirect()->route('setup.database'),
            'admin' => redirect()->route('setup.admin'),
            'storage' => redirect()->route('setup.storage'),
            'complete' => redirect()->route('setup.complete'),
            default => redirect()->route('setup.assets')
        };
    })->name('step')->where('step', 'assets|welcome|database|admin|storage|complete');
});