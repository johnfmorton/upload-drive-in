<?php

use App\Jobs\CleanupFailedRefreshAttemptsJob;
use App\Jobs\TokenMaintenanceJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Laravel 12 reads the schedule from here; app/Console/Kernel.php is never
| loaded, so tasks defined there silently never ran. Times are in the app
| timezone (UTC). The server needs `php artisan schedule:run` every minute.
|
| Not scheduled (each was in the old Kernel but is broken or harmful):
| - cloud-storage:check-health --notify: emails every Google Drive user a
|   "token will refresh soon" notice with a negative time on every run.
| - cloud-storage:monitor and cloud-storage:health-check: both crash with
|   "Cannot access private property CloudStorageManager::$factory".
| - cloud-storage:validate-config: fails whenever a provider that isn't in
|   use (e.g. Google Drive on an S3 install) is unconfigured.
| - HealthStatusValidationJob: calls the undefined
|   CloudStorageHealthService::updateHealthStatus().
|
*/

// Uploads: retry files that were received but never transferred to cloud storage
Schedule::command('uploads:process-pending --limit=25')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/pending-uploads.log'));

// Google Drive tokens: refresh before they expire (emails admins only on failure)
Schedule::command('google-drive:refresh-tokens')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/token-refresh.log'));

Schedule::job(new TokenMaintenanceJob)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->name('token-maintenance')
    ->onFailure(fn () => Log::error('TokenMaintenanceJob failed'));

Schedule::job(new CleanupFailedRefreshAttemptsJob)
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->name('cleanup-failed-refresh-attempts')
    ->onFailure(fn () => Log::error('CleanupFailedRefreshAttemptsJob failed'));

// Cloud storage health: refresh the status records the dashboard reads (no notifications)
Schedule::command('cloud-storage:check-health')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cloud-storage-health.log'));

Schedule::command('cloud-storage:fix-health-status')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/health-status-fix.log'));

// Caches and housekeeping
Schedule::command('cache:warm-up --files=200 --thumbnails=100')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cache-warmup.log'));

Schedule::command('performance:optimize')
    ->weeklyOn(0, '02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/performance-optimization.log'));

Schedule::command('queue-worker:cleanup --force')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-worker-cleanup.log'));

// Email verification monitoring (alerts admins only when thresholds are exceeded)
Schedule::command('email-verification:check-alerts --threshold-bypasses=20 --threshold-restrictions=50')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/email-verification-alerts.log'));

Schedule::command('email-verification:metrics --hours=24 --format=log')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/email-verification-metrics.log'));
