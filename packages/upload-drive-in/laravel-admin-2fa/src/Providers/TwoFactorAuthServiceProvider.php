<?php

namespace UploadDriveIn\LaravelAdmin2FA\Providers;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use UploadDriveIn\LaravelAdmin2FA\Http\Middleware\RequireTwoFactorAuth;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\View;

class TwoFactorAuthServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-admin-2fa')
            ->hasConfigFile()
            ->hasMigration('add_two_factor_columns_to_users_table')
            ->hasViews('laravel-admin-2fa')
            ->hasRoute('web');
    }

    public function packageRegistered(): void
    {
        // Register any bindings or services
    }

    public function packageBooted(): void
    {
        // Register the 2FA middleware
        $this->app['router']->aliasMiddleware('2fa', RequireTwoFactorAuth::class);

        // Register the view namespace
        View::addNamespace('laravel-admin-2fa', __DIR__.'/../../resources/views');
    }
}
