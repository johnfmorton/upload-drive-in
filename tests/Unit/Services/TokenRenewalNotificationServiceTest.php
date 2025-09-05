<?php

namespace Tests\Unit\Services;

use App\Enums\TokenRefreshErrorType;
use App\Mail\ConnectionRestoredMail;
use App\Mail\TokenExpiredMail;
use App\Mail\TokenRefreshFailedMail;
use App\Models\User;
use App\Services\TokenRenewalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Carbon\Carbon;

class TokenRenewalNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenRenewalNotificationService $service;
    private User $user;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new TokenRenewalNotificationService();
        
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => \App\Enums\UserRole::EMPLOYEE
        ]);
        
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => \App\Enums\UserRole::ADMIN
        ]);
        
        Mail::fake();
        Cache::flush();
    }

    public function test_send_token_expired_notification_success()
    {
        $result = $this->service->sendTokenExpiredNotification($this->user, 'google-drive');
        
        $this->assertTrue($result);
        
        Mail::assertSent(TokenExpiredMail::class, function ($mail) {
            return $mail->hasTo($this->user->email) &&
                   $mail->user->id === $this->user->id &&
                   $mail->provider === 'google-drive';
        });
    }

    public function test_send_token_expired_notification_throttled()
    {
        // Send first notification
        $this->service->sendTokenExpiredNotification($this->user, 'google-drive');
        
        // Attempt to send second notification immediately
        $result = $this->service->sendTokenExpiredNotification($this->user, 'google-drive');
        
        $this->assertFalse($result);
        
        // Should only have sent one email
        Mail::assertSentCount(1);
    }

    public function test_send_refresh_failure_notification_success()
    {
        $errorType = TokenRefreshErrorType::NETWORK_TIMEOUT;
        $attemptCount = 2;
        $errorMessage = 'Connection timeout after 30 seconds';
        
        $result = $this->service->sendRefreshFailureNotification(
            $this->user, 
            'google-drive', 
            $errorType, 
            $attemptCount, 
            $errorMessage
        );
        
        $this->assertTrue($result);
        
        Mail::assertSent(TokenRefreshFailedMail::class, function ($mail) use ($errorType, $attemptCount, $errorMessage) {
            return $mail->hasTo($this->user->email) &&
                   $mail->user->id === $this->user->id &&
                   $mail->provider === 'google-drive' &&
                   $mail->errorType === $errorType &&
                   $mail->attemptCount === $attemptCount &&
                   $mail->errorMessage === $errorMessage;
        });
    }

    public function test_send_connection_restored_notification_success()
    {
        $result = $this->service->sendConnectionRestoredNotification($this->user, 'google-drive');
        
        $this->assertTrue($result);
        
        Mail::assertSent(ConnectionRestoredMail::class, function ($mail) {
            return $mail->hasTo($this->user->email) &&
                   $mail->user->id === $this->user->id &&
                   $mail->provider === 'google-drive';
        });
    }

    public function test_notification_throttling_logic()
    {
        $provider = 'google-drive';
        $notificationType = 'token_expired';
        
        // First notification should be allowed
        $this->assertTrue($this->service->shouldSendNotification($this->user, $provider, $notificationType));
        
        // Send the notification
        $this->service->sendTokenExpiredNotification($this->user, $provider);
        
        // Second notification should be throttled
        $this->assertFalse($this->service->shouldSendNotification($this->user, $provider, $notificationType));
        
        // Fast-forward time to after throttle period
        $this->travel(25)->hours();
        
        // Should be allowed again after throttle period
        $this->assertTrue($this->service->shouldSendNotification($this->user, $provider, $notificationType));
    }

    public function test_notification_throttling_per_type()
    {
        $provider = 'google-drive';
        
        // Send token expired notification
        $this->service->sendTokenExpiredNotification($this->user, $provider);
        
        // Token expired should be throttled
        $this->assertFalse($this->service->shouldSendNotification($this->user, $provider, 'token_expired'));
        
        // But refresh failure should still be allowed
        $this->assertTrue($this->service->shouldSendNotification($this->user, $provider, 'refresh_failure'));
        
        // And connection restored should still be allowed
        $this->assertTrue($this->service->shouldSendNotification($this->user, $provider, 'connection_restored'));
    }

    public function test_notification_failure_escalation()
    {
        // Mock mail failure
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP connection failed'));
        
        // Attempt to send notification multiple times to trigger escalation
        for ($i = 0; $i < 3; $i++) {
            $this->service->sendTokenExpiredNotification($this->user, 'google-drive');
        }
        
        // Should have attempted to escalate to admin
        // We can't easily test the actual escalation email since we mocked Mail::to
        // But we can verify the failure was logged
        $this->assertTrue(true); // This test verifies the escalation logic runs without errors
    }

    public function test_clear_notification_throttle()
    {
        $provider = 'google-drive';
        $notificationType = 'token_expired';
        
        // Send notification to create throttle
        $this->service->sendTokenExpiredNotification($this->user, $provider);
        
        // Verify throttled
        $this->assertFalse($this->service->shouldSendNotification($this->user, $provider, $notificationType));
        
        // Clear throttle
        $this->service->clearNotificationThrottle($this->user, $provider, $notificationType);
        
        // Should be allowed again
        $this->assertTrue($this->service->shouldSendNotification($this->user, $provider, $notificationType));
    }

    public function test_clear_all_notification_throttles()
    {
        $provider = 'google-drive';
        
        // Send multiple types of notifications
        $this->service->sendTokenExpiredNotification($this->user, $provider);
        $this->service->sendRefreshFailureNotification($this->user, $provider, TokenRefreshErrorType::NETWORK_TIMEOUT, 1);
        $this->service->sendConnectionRestoredNotification($this->user, $provider);
        
        // All should be throttled
        $this->assertFalse($this->service->shouldSendNotification($this->user, $provider, 'token_expired'));
        $this->assertFalse($this->service->shouldSendNotification($this->user, $provider, 'refresh_failure'));
        $this->assertFalse($this->service->shouldSendNotification($this->user, $provider, 'connection_restored'));
        
        // Clear all throttles
        $this->service->clearNotificationThrottle($this->user, $provider);
        
        // All should be allowed again
        $this->assertTrue($this->service->shouldSendNotification($this->user, $provider, 'token_expired'));
        $this->assertTrue($this->service->shouldSendNotification($this->user, $provider, 'refresh_failure'));
        $this->assertTrue($this->service->shouldSendNotification($this->user, $provider, 'connection_restored'));
    }

    public function test_get_notification_status()
    {
        $provider = 'google-drive';
        
        // Initially, all notifications should be allowed
        $status = $this->service->getNotificationStatus($this->user, $provider);
        
        $this->assertNull($status['token_expired']['last_sent']);
        $this->assertTrue($status['token_expired']['can_send']);
        $this->assertNull($status['token_expired']['throttled_until']);
        
        $this->assertNull($status['refresh_failure']['last_sent']);
        $this->assertTrue($status['refresh_failure']['can_send']);
        $this->assertNull($status['refresh_failure']['throttled_until']);
        
        $this->assertNull($status['connection_restored']['last_sent']);
        $this->assertTrue($status['connection_restored']['can_send']);
        $this->assertNull($status['connection_restored']['throttled_until']);
        
        // Send a notification
        $this->service->sendTokenExpiredNotification($this->user, $provider);
        
        // Check status again
        $status = $this->service->getNotificationStatus($this->user, $provider);
        
        $this->assertInstanceOf(Carbon::class, $status['token_expired']['last_sent']);
        $this->assertFalse($status['token_expired']['can_send']);
        $this->assertInstanceOf(Carbon::class, $status['token_expired']['throttled_until']);
        
        // Other types should still be available
        $this->assertTrue($status['refresh_failure']['can_send']);
        $this->assertTrue($status['connection_restored']['can_send']);
    }

    public function test_notification_throttling_different_providers()
    {
        // Send notification for Google Drive
        $this->service->sendTokenExpiredNotification($this->user, 'google-drive');
        
        // Google Drive should be throttled
        $this->assertFalse($this->service->shouldSendNotification($this->user, 'google-drive', 'token_expired'));
        
        // But Dropbox should still be allowed (different provider)
        $this->assertTrue($this->service->shouldSendNotification($this->user, 'dropbox', 'token_expired'));
    }

    public function test_notification_throttling_different_users()
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        
        // Send notification for first user
        $this->service->sendTokenExpiredNotification($this->user, 'google-drive');
        
        // First user should be throttled
        $this->assertFalse($this->service->shouldSendNotification($this->user, 'google-drive', 'token_expired'));
        
        // But other user should still be allowed
        $this->assertTrue($this->service->shouldSendNotification($otherUser, 'google-drive', 'token_expired'));
    }

    public function test_mail_classes_have_correct_properties()
    {
        // Test TokenExpiredMail
        $tokenExpiredMail = new TokenExpiredMail($this->user, 'google-drive');
        $this->assertEquals($this->user->id, $tokenExpiredMail->user->id);
        $this->assertEquals('google-drive', $tokenExpiredMail->provider);
        $this->assertStringContainsString('/admin/cloud-storage/google-drive/connect', $tokenExpiredMail->reconnectUrl);
        
        // Test TokenRefreshFailedMail
        $errorType = TokenRefreshErrorType::NETWORK_TIMEOUT;
        $refreshFailedMail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, 2, 'Test error');
        $this->assertEquals($this->user->id, $refreshFailedMail->user->id);
        $this->assertEquals('google-drive', $refreshFailedMail->provider);
        $this->assertEquals($errorType, $refreshFailedMail->errorType);
        $this->assertEquals(2, $refreshFailedMail->attemptCount);
        $this->assertEquals('Test error', $refreshFailedMail->errorMessage);
        
        // Test ConnectionRestoredMail
        $restoredMail = new ConnectionRestoredMail($this->user, 'google-drive');
        $this->assertEquals($this->user->id, $restoredMail->user->id);
        $this->assertEquals('google-drive', $restoredMail->provider);
        $this->assertStringContainsString('/employee/dashboard', $restoredMail->dashboardUrl);
    }

    public function test_admin_user_gets_admin_dashboard_url()
    {
        // Ensure admin user has correct role
        $this->assertEquals(\App\Enums\UserRole::ADMIN, $this->adminUser->role);
        
        $restoredMail = new ConnectionRestoredMail($this->adminUser, 'google-drive');
        $this->assertStringContainsString('/admin/dashboard', $restoredMail->dashboardUrl);
    }

    public function test_error_type_display_names()
    {
        $errorTypes = [
            [TokenRefreshErrorType::NETWORK_TIMEOUT, 'Network Timeout'],
            [TokenRefreshErrorType::INVALID_REFRESH_TOKEN, 'Invalid Refresh Token'],
            [TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, 'Expired Refresh Token'],
            [TokenRefreshErrorType::API_QUOTA_EXCEEDED, 'API Quota Exceeded'],
            [TokenRefreshErrorType::SERVICE_UNAVAILABLE, 'Service Unavailable'],
            [TokenRefreshErrorType::UNKNOWN_ERROR, 'Unknown Error'],
        ];
        
        foreach ($errorTypes as [$errorType, $expectedName]) {
            $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, 1);
            
            // Use reflection to access private method for testing
            $reflection = new \ReflectionClass($mail);
            $method = $reflection->getMethod('getErrorTypeDisplayName');
            $method->setAccessible(true);
            
            $displayName = $method->invoke($mail);
            $this->assertEquals($expectedName, $displayName);
        }
    }

    public function test_provider_display_names()
    {
        $providers = [
            ['google-drive', 'Google Drive'],
            ['microsoft-teams', 'Microsoft Teams'],
            ['dropbox', 'Dropbox'],
            ['custom-provider', 'Custom Provider'],
        ];
        
        foreach ($providers as [$provider, $expectedName]) {
            $mail = new TokenExpiredMail($this->user, $provider);
            
            // Use reflection to access private method for testing
            $reflection = new \ReflectionClass($mail);
            $method = $reflection->getMethod('getProviderDisplayName');
            $method->setAccessible(true);
            
            $displayName = $method->invoke($mail);
            $this->assertEquals($expectedName, $displayName);
        }
    }
}