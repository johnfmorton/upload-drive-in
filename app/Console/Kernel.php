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
