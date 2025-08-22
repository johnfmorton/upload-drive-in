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

Route::middleware(['web'])->prefix('setup')->name('setup.')->group(function () {
    
    // Setup instructions route - accessible without authentication
    // This route handles its own redirect logic when setup is complete
    Route::get('/instructions', [\App\Http\Controllers\SetupInstructionsController::class, 'show'])->name('instructions');
    
});