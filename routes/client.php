<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\UploadController;

// Client Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// File Upload Routes
Route::get('/upload-files', [FileUploadController::class, 'create'])->name('upload-files');
Route::post('/upload-files', [FileUploadController::class, 'store'])->name('upload-files.store');
Route::get('/my-uploads', [FileUploadController::class, 'index'])->name('my-uploads');
Route::post('/chunk-upload', [UploadController::class, 'store'])->name('chunk.upload');
Route::post('/uploads/associate-message', [UploadController::class, 'associateMessage'])->name('uploads.associate-message');
Route::post('/uploads/batch-complete', [UploadController::class, 'batchComplete'])->name('uploads.batch-complete');

// Profile Routes
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
