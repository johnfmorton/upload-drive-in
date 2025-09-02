<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ProcessPendingUploads::class,
        Commands\DiagnoseUploadsCommand::class,
        Commands\Remove2FAToken::class,
        Commands\ExportSqliteData::class,
        Commands\ImportToMariaDB::class,
        Commands\ListUsers::class,
        Commands\ListUsersAlias::class,
        Commands\RefreshGoogleDriveTokens::class,
        Commands\WarmUpCaches::class,
        Commands\OptimizePerformance::class,
        Commands\QueueWorkerCleanupCommand::class,
        // User Management Commands
        Commands\CreateUser::class,
        Commands\ShowUser::class,
        Commands\DeleteUser::class,
        Commands\ResetUserPassword::class,
        Commands\ToggleUserNotifications::class,
        Commands\GenerateUserLoginUrl::class,
        Commands\CheckCloudStorageHealth::class,
        Commands\FixCloudStorageHealthStatus::class,
        Commands\MigrateCloudStorageConfig::class,
        Commands\TestCloudStorageProviders::class,
        Commands\ValidateCloudStorageConfiguration::class,
        Commands\ComprehensiveCloudStorageHealthCheck::class,
        Commands\MonitorCloudStorageProviders::class,
        Commands\RunComprehensiveCloudStorageValidation::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * These schedules are run in the console.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Refresh Google Drive tokens every 6 hours to prevent expiration
        $schedule->command('google-drive:refresh-tokens')
                 ->everySixHours()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/token-refresh.log'));
        
        // Optional: Run a more frequent check during business hours
        $schedule->command('google-drive:refresh-tokens')
                 ->dailyAt('09:00')
                 ->timezone('America/New_York') // Adjust to your timezone
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/token-refresh.log'));

        // Process pending uploads every 30 minutes
        $schedule->command('uploads:process-pending --limit=25')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/pending-uploads.log'));

        // Warm up caches daily at 6 AM
        $schedule->command('cache:warm-up --files=200 --thumbnails=100')
                 ->dailyAt('06:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/cache-warmup.log'));

        // Run performance optimization weekly
        $schedule->command('performance:optimize')
                 ->weekly()
                 ->sundays()
                 ->at('02:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/performance-optimization.log'));

        // Clean up queue worker test data daily at 3 AM
        $schedule->command('queue-worker:cleanup --force')
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/queue-worker-cleanup.log'));

        // Check cloud storage health every 4 hours
        $schedule->command('cloud-storage:check-health --notify')
                 ->everyFourHours()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/cloud-storage-health.log'));

        // More frequent health checks during business hours (every hour from 8 AM to 6 PM)
        $schedule->command('cloud-storage:check-health')
                 ->hourly()
                 ->between('08:00', '18:00')
                 ->weekdays()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/cloud-storage-health.log'));

        // Fix inconsistent health status records daily at 4 AM
        $schedule->command('cloud-storage:fix-health-status')
                 ->dailyAt('04:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/health-status-fix.log'));

        // Monitor cloud storage providers every 5 minutes with alerting
        $schedule->command('cloud-storage:monitor --alert --quiet')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/cloud-storage-monitoring.log'));

        // Comprehensive health check every hour with caching and notifications
        $schedule->command('cloud-storage:health-check --cache --notify --quiet')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/comprehensive-health-check.log'));

        // Configuration validation daily at 2 AM
        $schedule->command('cloud-storage:validate-config --log --quiet')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/config-validation.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
