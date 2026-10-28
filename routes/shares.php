<?php

use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Outgoing File Share Routes (sender side)
|--------------------------------------------------------------------------
|
| Included by routes/admin.php (admin.shares.*) and
| routes/employee-portal.php (employee.shares.*).
|
*/

Route::prefix('shares')
    ->name('shares.')
    ->group(function () {
        Route::get('/', [ShareController::class, 'index'])->name('index');
        Route::get('/create', [ShareController::class, 'create'])->name('create');
        Route::post('/', [ShareController::class, 'store'])->name('store');
        Route::get('/{share}', [ShareController::class, 'show'])->name('show');
        Route::get('/{share}/status', [ShareController::class, 'status'])->name('status');
        Route::post('/{share}/finalize', [ShareController::class, 'finalize'])->name('finalize');
        Route::post('/{share}/revoke', [ShareController::class, 'revoke'])->name('revoke');

        // One request per 5MB chunk, so it uses the chunk limiter
        Route::post('/{share}/files', [ShareController::class, 'uploadChunk'])
            ->middleware('throttle:upload-chunks')
            ->name('files.store');
        Route::delete('/{share}/files/{shareFile}', [ShareController::class, 'destroyFile'])->name('files.destroy');
        Route::post('/{share}/files/{shareFile}/retry', [ShareController::class, 'retryFile'])->name('files.retry');
    });
