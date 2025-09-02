<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageErrorTrackingService;
use App\Models\User;
use App\Enums\CloudStorageErrorType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudStorageErrorTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageErrorTrackingService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CloudStorageErrorTrackingService();
        $this->user = User::factory()->create();
    }

    public function test_tracks_error_successfully()
    {
        Log::shouldReceive('channel')->with('cloud-storage')->andReturnSelf();
        Log::shouldReceive('error')->twice(); // Once direct, once through CloudStorageLogService integration

        $this->service->trackError(
            'google-drive',
            $this->user,
            CloudStorageErrorType::TOKEN_EXPIRED,
            'upload',
            'Token expired',
            null,
            ['context' => 'test']
        );

        // Verify error counters were updated
        $hour = now()->format('Y-m-d-H');
        $totalKey = "cloud_storage_errors:count:google-drive:{$this->user->id}:total:{$hour}";
        $typeKey = "cloud_storage_errors:count:google-drive:{$this->user->id}:type:token_expired:{$hour}";
        
        $this->assertEquals(1, Cache::get($totalKey));
        $this->assertEquals(1, Cache::get($typeKey));
    }

    public function test_tracks_error_with_exception()
    {
        Log::shouldReceive('channel')->with('cloud-storage')->andReturnSelf();
        Log::shouldReceive('error')->once();

        $exception = new \Exception('Test exception');

        $this->service->trackError(
            'google-drive',
            $this->user,
            CloudStorageErrorType::NETWORK_ERROR,
            'upload',
            'Network error occurred',
            $exception
        );

        // Verify error was stored with exception details
        $hour = now()->format('Y-m-d-H');
        $errorsKey = "cloud_storage_errors:errors:google-drive:{$this->user->id}:{$hour}";
        $errors = Cache::get($errorsKey, []);
        
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('exception', $errors[0]);
        $this->assertEquals('Exception', $errors[0]['exception']['class']);
    }

    public function test_tracks_success_and_resets_consecutive_failures()
    {
        Log::shouldReceive('channel')->with('cloud-storage')->andReturnSelf();
        Log::shouldReceive('debug')->once();

        // First set some consecutive failures
        $consecutiveKey = "cloud_storage_errors:consecutive:google-drive:{$this->user->id}:upload";
        Cache::put($consecutiveKey, 3);

        $this->service->trackSuccess('google-drive', $this->user, 'upload');

        // Consecutive failures should be reset
        $this->assertNull(Cache::get($consecutiveKey));
    }

    public function test_gets_error_statistics()
    {
        // Set up test error data
        $hour = now()->format('Y-m-d-H');
        Cache::put("cloud_storage_errors:count:google-drive:{$this->user->id}:total:{$hour}", 5);
        Cache::put("cloud_storage_errors:count:google-drive:{$this->user->id}:type:token_expired:{$hour}", 2);
        Cache::put("cloud_storage_errors:count:google-drive:{$this->user->id}:type:network_error:{$hour}", 3);
        Cache::put("cloud_storage_errors:count:google-drive:{$this->user->id}:operation:upload:{$hour}", 4);
        Cache::put("cloud_storage_errors:count:google-drive:{$this->user->id}:operation:delete:{$hour}", 1);

        $statistics = $this->service->getErrorStatistics('google-drive', $this->user, 1);

        $this->assertEquals('google-drive', $statistics['provider']);
        $this->assertEquals($this->user->id, $statistics['user_id']);
        $this->assertEquals(5, $statistics['total_errors']);
        $this->assertEquals(5.0, $statistics['error_rate_per_hour']);
        $this->assertArrayHasKey('token_expired', $statistics['error_types']);
        $this->assertArrayHasKey('network_error', $statistics['error_types']);
        $this->assertEquals(2, $statistics['error_types']['token_expired']);
        $this->assertEquals(3, $statistics['error_types']['network_error']);
    }

    public function test_detects_high_error_rate_alert()
    {
        // Set up high error rate
        $hour = now()->format('Y-m-d-H');
        Cache::put("cloud_storage_errors:count:google-drive:{$this->user->id}:total:{$hour}", 15);

        $alerts = $this->service->getActiveAlerts('google-drive', $this->user);

        $alertTypes = array_column($alerts, 'type');
        $this->assertContains('high_error_rate', $alertTypes);
        
        $errorRateAlert = collect($alerts)->firstWhere('type', 'high_error_rate');
        $this->assertEquals('medium', $errorRateAlert['severity']);
        $this->assertEquals(15, $errorRateAlert['current_value']);
    }

    public function test_detects_consecutive_failures_alert()
    {
        // Set up consecutive failures
        Cache::put("cloud_storage_errors:consecutive:google-drive:{$this->user->id}:upload", 6);

        $alerts = $this->service->getActiveAlerts('google-drive', $this->user);

        $alertTypes = array_column($alerts, 'type');
        $this->assertContains('consecutive_failures', $alertTypes);
        
        $consecutiveAlert = collect($alerts)->firstWhere('type', 'consecutive_failures');
        $this->assertEquals('high', $consecutiveAlert['severity']);
        $this->assertEquals('upload', $consecutiveAlert['operation']);
        $this->assertEquals(6, $consecutiveAlert['current_value']);
    }

    public function test_detects_critical_errors_alert()
    {
        // Set up critical error
        $hour = now()->format('Y-m-d-H');
        Cache::put("cloud_storage_errors:count:google-drive:{$this->user->id}:type:token_expired:{$hour}", 1);

        $alerts = $this->service->getActiveAlerts('google-drive', $this->user);

        $alertTypes = array_column($alerts, 'type');
        $this->assertContains('critical_errors', $alertTypes);
        
        $criticalAlert = collect($alerts)->firstWhere('type', 'critical_errors');
        $this->assertEquals('critical', $criticalAlert['severity']);
        $this->assertEquals(1, $criticalAlert['current_value']);
    }

    public function test_sends_error_alert_notification()
    {
        Notification::fake();

        $this->service->sendErrorAlert(
            'google-drive',
            $this->user,
            'high_error_rate',
            'High error rate detected'
        );

        Notification::assertSentTo($this->user, \App\Notifications\CloudStorageConnectionAlert::class);
    }

    public function test_throttles_alerts_to_prevent_spam()
    {
        Notification::fake();

        // Send first alert
        $this->service->sendErrorAlert(
            'google-drive',
            $this->user,
            'high_error_rate',
            'High error rate detected'
        );

        // Try to send same alert type immediately
        $this->service->sendErrorAlert(
            'google-drive',
            $this->user,
            'high_error_rate',
            'High error rate detected again'
        );

        // Should only send one notification due to throttling
        Notification::assertSentToTimes($this->user, \App\Notifications\CloudStorageConnectionAlert::class, 1);
    }

    public function test_clears_alerts_successfully()
    {
        Log::shouldReceive('channel')->with('cloud-storage')->andReturnSelf();
        Log::shouldReceive('info')->once();

        // Set up some alert data
        $alertKey = "cloud_storage_errors:alerts:google-drive:{$this->user->id}:high_error_rate";
        Cache::put($alertKey, true);

        $this->service->clearAlerts('google-drive', $this->user, 'high_error_rate');

        $this->assertNull(Cache::get($alertKey));
    }

    public function test_updates_consecutive_failure_tracking()
    {
        $consecutiveKey = "cloud_storage_errors:consecutive:google-drive:{$this->user->id}:upload";

        // Track multiple failures
        $this->service->trackError(
            'google-drive',
            $this->user,
            CloudStorageErrorType::NETWORK_ERROR,
            'upload',
            'Network error 1'
        );

        $this->service->trackError(
            'google-drive',
            $this->user,
            CloudStorageErrorType::NETWORK_ERROR,
            'upload',
            'Network error 2'
        );

        $this->assertEquals(2, Cache::get($consecutiveKey));

        // Track success - should reset counter
        $this->service->trackSuccess('google-drive', $this->user, 'upload');

        $this->assertNull(Cache::get($consecutiveKey));
    }

    public function test_stores_recent_errors_for_analysis()
    {
        Log::shouldReceive('channel')->with('cloud-storage')->andReturnSelf();
        Log::shouldReceive('error')->times(3);

        // Track multiple errors
        for ($i = 1; $i <= 3; $i++) {
            $this->service->trackError(
                'google-drive',
                $this->user,
                CloudStorageErrorType::NETWORK_ERROR,
                'upload',
                "Network error {$i}"
            );
        }

        $hour = now()->format('Y-m-d-H');
        $errorsKey = "cloud_storage_errors:errors:google-drive:{$this->user->id}:{$hour}";
        $errors = Cache::get($errorsKey, []);

        $this->assertCount(3, $errors);
        $this->assertEquals('Network error 1', $errors[0]['error_message']);
        $this->assertEquals('Network error 3', $errors[2]['error_message']);
    }

    public function test_limits_stored_errors_to_prevent_memory_issues()
    {
        Log::shouldReceive('channel')->with('cloud-storage')->andReturnSelf();
        Log::shouldReceive('error')->once();

        $hour = now()->format('Y-m-d-H');
        $errorsKey = "cloud_storage_errors:errors:google-drive:{$this->user->id}:{$hour}";
        
        // Pre-populate with 100 errors
        $existingErrors = array_fill(0, 100, ['error_message' => 'existing error']);
        Cache::put($errorsKey, $existingErrors);

        // Add one more error
        $this->service->trackError(
            'google-drive',
            $this->user,
            CloudStorageErrorType::NETWORK_ERROR,
            'upload',
            'New error'
        );

        $errors = Cache::get($errorsKey, []);
        
        // Should still be 100 items (oldest removed)
        $this->assertCount(100, $errors);
        $this->assertEquals('New error', $errors[99]['error_message']);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}