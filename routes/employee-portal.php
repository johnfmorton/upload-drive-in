<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\UploadController;

// Employee portal upload routes (protected by 'auth' + 'employee' middleware)
Route::get('/', [UploadController::class, 'show'])->name('upload.show');
Route::get('/google-drive/connect', [UploadController::class, 'connect'])->name('google-drive.connect');
Route::get('/google-drive/callback', [UploadController::class, 'callback'])->name('google-drive.callback');
Route::put('/google-drive/folder', [UploadController::class, 'updateFolder'])->name('google-drive.folder.update');
Route::post('/upload', [UploadController::class, 'upload'])->name('upload');
