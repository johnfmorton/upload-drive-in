<?php

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

Route::middleware(['web', 'require.setup.enabled'])->prefix('setup')->name('setup.')->group(function () {
    
    // Setup instructions route - accessible without authentication
    // This route handles its own redirect logic when setup is complete
    Route::get('/instructions', [\App\Http\Controllers\SetupInstructionsController::class, 'show'])->name('instructions');
    
    // AJAX endpoints for real-time status updates
    // These routes require CSRF protection (handled by VerifyCsrfToken middleware)
    // TODO: Re-add rate limiting middleware once container resolution is fixed
    Route::post('/status/refresh', [\App\Http\Controllers\SetupInstructionsController::class, 'refreshStatus'])->name('status.refresh');
    Route::post('/status/refresh-step', [\App\Http\Controllers\SetupInstructionsController::class, 'refreshSingleStep'])->name('status.refresh-step');
    
    // Setup disable endpoint
    Route::post('/disable', [\App\Http\Controllers\SetupInstructionsController::class, 'disableSetup'])->name('disable');
    
});