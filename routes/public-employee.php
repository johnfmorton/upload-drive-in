<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicEmployeeUploadController;

// Public “drop files for employee” page (no auth)
Route::get('/', [PublicEmployeeUploadController::class, 'show'])->name('upload.show');
Route::post('/upload', [PublicEmployeeUploadController::class, 'upload'])->name('upload');
