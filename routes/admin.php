<?php

use App\Http\Controllers\Admin\CloudStorageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\CloudStorage\DropboxAuthController;
use App\Http\Controllers\CloudStorage\MicrosoftTeamsAuthController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\Admin\GoogleDriveFolderController;

// All Admin Routes
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class, '2fa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Admin profile routes
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // File management
        Route::delete('/files/{file}', [DashboardController::class, 'destroy'])
            ->name('files.destroy');

        // User Management
        Route::resource('/users', AdminUserController::class)
            ->only(['index', 'destroy'])
            ->names('users');
        Route::post('/users', [AdminUserController::class, 'store'])
            ->name('users.store');

        // Application Settings
        Route::get('/settings', [AdminSettingsController::class, 'edit'])
            ->name('settings.edit');
        Route::put('/settings', [AdminSettingsController::class, 'update'])
            ->name('settings.update');
        Route::delete('/settings/icon', [AdminSettingsController::class, 'destroyIcon'])
            ->name('settings.icon.destroy');

        // User Management Settings
        Route::get('/user-management', [UserManagementController::class, 'settings'])
            ->name('user-management.settings');
        Route::put('/user-management/registration', [UserManagementController::class, 'updateRegistration'])
            ->name('user-management.update-registration');
        Route::put('/user-management/domain-rules', [UserManagementController::class, 'updateDomainRules'])
            ->name('user-management.update-domain-rules');
        Route::post('/user-management/clients', [UserManagementController::class, 'createClient'])
            ->name('user-management.create-client');

        // Cloud Storage Configuration
        Route::prefix('cloud-storage')
            ->name('cloud-storage.')
            ->group(function () {
                Route::get('/', [CloudStorageController::class, 'index'])->name('index');

                // Microsoft Teams routes
                Route::put('/microsoft-teams', [CloudStorageController::class, 'updateMicrosoftTeams'])->name('microsoft-teams.update');
                Route::get('/microsoft-teams/connect', [MicrosoftTeamsAuthController::class, 'connect'])->name('microsoft-teams.connect');
                Route::get('/microsoft-teams/callback', [MicrosoftTeamsAuthController::class, 'callback'])->name('microsoft-teams.callback');
                Route::post('/microsoft-teams/disconnect', [MicrosoftTeamsAuthController::class, 'disconnect'])->name('microsoft-teams.disconnect');

                // Dropbox routes
                Route::put('/dropbox', [CloudStorageController::class, 'updateDropbox'])->name('dropbox.update');
                Route::get('/dropbox/connect', [DropboxAuthController::class, 'connect'])->name('dropbox.connect');
                Route::post('/dropbox/disconnect', [DropboxAuthController::class, 'disconnect'])->name('dropbox.disconnect');

                // Google Drive routes
                Route::put('/google-drive', [CloudStorageController::class, 'updateGoogleDrive'])->name('google-drive.update');
                Route::get('/google-drive/connect', [GoogleDriveController::class, 'connect'])->name('google-drive.connect');
                Route::get('/google-drive/callback', [GoogleDriveController::class, 'callback'])->name('google-drive.callback');
                Route::post('/google-drive/disconnect', [GoogleDriveController::class, 'disconnect'])->name('google-drive.disconnect');
                Route::get('/google-drive/folders', [GoogleDriveFolderController::class, 'index'])->name('google-drive.folders');
                Route::post('/google-drive/folders', [GoogleDriveFolderController::class, 'store'])->name('google-drive.folders.store');

                // Default provider route
                Route::put('/default', [CloudStorageController::class, 'updateDefault'])->name('default');
            });
    });
