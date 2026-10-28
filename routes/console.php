<?php

use App\Jobs\CleanupFailedRefreshAttemptsJob;
use App\Jobs\HealthStatusValidationJob;
use App\Jobs\PurgeExpiredShares;
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
| Laravel 12 reads the schedule from here (an app/Console/Kernel.php schedule
| is never loaded). Times are in the app timezone (UTC). The server needs
| `php artisan schedule:run` every minute.
|
*/

// Uploads: retry files that were received but never transferred to cloud storage
Schedule::command('uploads:process-pending --limit=25')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/pending-uploads.log'));

// Outgoing shares: expire overdue links and delete ended shares' files
Schedule::job(new PurgeExpiredShares)
    ->hourly()
    ->withoutOverlapping()
    ->name('purge-expired-shares')
    ->onFailure(fn () => Log::error('PurgeExpiredShares failed'));

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

Schedule::job(new HealthStatusValidationJob)
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->name('health-status-validation')
    ->onFailure(fn () => Log::error('HealthStatusValidationJob failed'));

// Cloud storage health. All checks only look at providers in use
// (CloudConfigurationService::getProvidersInUse()), so unused or unreleased
// providers don't count as failures.

// Per-user Google Drive connections; emails a user only after 3+ consecutive
// failures, at most every 12 hours. No-op when Google Drive isn't in use.
Schedule::command('cloud-storage:check-health --notify')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cloud-storage-health.log'));

// Provider monitoring; alerts admins on outages (same issue at most hourly)
Schedule::command('cloud-storage:monitor --alert --quiet')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cloud-storage-monitoring.log'));

Schedule::command('cloud-storage:health-check --cache --quiet')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/comprehensive-health-check.log'));

Schedule::command('cloud-storage:validate-config --log --quiet')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/config-validation.log'));

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
