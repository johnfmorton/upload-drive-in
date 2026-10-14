<?php

namespace Tests\Feature;

use App\Services\CloudStorageProviderMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Health tasks that run on a schedule must not alert about providers the
 * installation doesn't use, or re-send the same alert on every run.
 */
class CloudStorageHealthSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_health_skips_when_google_drive_is_not_in_use()
    {
        Notification::fake();
        config([
            'cloud-storage.default' => 'amazon-s3',
            'cloud-storage.providers.google-drive.config.client_id' => null,
            'cloud-storage.providers.google-drive.config.client_secret' => null,
        ]);

        $this->artisan('cloud-storage:check-health --notify')
            ->expectsOutputToContain('Google Drive is not in use')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_alert_fingerprint_ignores_the_per_run_timestamp()
    {
        $service = app(CloudStorageProviderMonitoringService::class);
        $fingerprint = new ReflectionMethod($service, 'alertFingerprint');

        $first = ['type' => 'provider_down', 'provider' => 'amazon-s3', 'message' => 'down', 'timestamp' => '2026-10-14T10:00:00Z'];
        $second = ['timestamp' => '2026-10-14T10:05:00Z'] + $first;

        $this->assertSame($fingerprint->invoke($service, $first), $fingerprint->invoke($service, $second));
    }
}
