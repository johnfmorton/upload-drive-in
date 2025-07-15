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
        Commands\Remove2FAToken::class,
        Commands\ExportSqliteData::class,
        Commands\ImportToMariaDB::class,
        Commands\ListUsers::class,
        Commands\RefreshGoogleDriveTokens::class,
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
