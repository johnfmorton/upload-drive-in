<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\UploadController;
use App\Http\Controllers\Employee\ProfileController;

// Employee Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Employee portal upload routes (protected by 'auth' + 'employee' middleware)
Route::get('/', [UploadController::class, 'show'])->name('upload.show');
Route::get('/google-drive/connect', [UploadController::class, 'connect'])->name('google-drive.connect');
Route::get('/google-drive/callback', [UploadController::class, 'callback'])->name('google-drive.callback');
Route::put('/google-drive/folder', [UploadController::class, 'updateFolder'])->name('google-drive.folder.update');
Route::post('/upload', [UploadController::class, 'upload'])->name('upload');

// Profile Routes
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
