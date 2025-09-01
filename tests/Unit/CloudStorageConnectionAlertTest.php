<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Notifications\CloudStorageConnectionAlert;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudStorageConnectionAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CloudStorageHealthStatus $healthStatus;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => UserRole::ADMIN,
        ]);
        
        $this->healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'degraded',
            'consecutive_failures' => 3,
            'last_error_message' => 'Token expired',
            'token_expires_at' => Carbon::now()->addHours(2),
        ]);
    }

    public function test_token_expiring_notification_has_correct_content(): void
    {
        $notification = new CloudStorageConnectionAlert(
            'google-drive',
            'token_expiring',
            $this->healthStatus
        );

        $mailMessage = $notification->toMail($this->user);

        $this->assertEquals('Google Drive Token Will Refresh Soon', $mailMessage->subject);
        $this->assertStringContainsString('Hello Test User,', $mailMessage->greeting);
        $this->assertStringContainsString('Google Drive access token will refresh automatically', $mailMessage->introLines[0]);
        $this->assertMatchesRegularExpression('/approximately \d+(\.\d+)? hours/', $mailMessage->introLines[0]);
        $this->assertEquals('View Connection Status', $mailMessage->actionText);
    }

    public function test_connection_unhealthy_notification_has_correct_content(): void
    {
        $notification = new CloudStorageConnectionAlert(
            'google-drive',
            'connection_unhealthy',
            $this->healthStatus
        );

        $mailMessage = $notification->toMail($this->user);

        $this->assertEquals('Google Drive Connection Issue Detected', $mailMessage->subject);
        $this->assertStringContainsString('Hello Test User,', $mailMessage->greeting);
        $this->assertStringContainsString('detected an issue', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Error: Token expired', $mailMessage->introLines[1]);
        $this->assertStringContainsString('Consecutive failures: 3', $mailMessage->introLines[2]);
        $this->assertEquals('Check Connection', $mailMessage->actionText);
    }

    public function test_multiple_failures_notification_has_correct_content(): void
    {
        $this->healthStatus->update(['consecutive_failures' => 5]);
        
        $notification = new CloudStorageConnectionAlert(
            'google-drive',
            'multiple_failures',
            $this->healthStatus
        );

        $mailMessage = $notification->toMail($this->user);

        $this->assertEquals('Google Drive Multiple Upload Failures', $mailMessage->subject);
        $this->assertStringContainsString('Hello Test User,', $mailMessage->greeting);
        $this->assertStringContainsString('5 consecutive failures', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Last error: Token expired', $mailMessage->introLines[1]);
        $this->assertEquals('Troubleshoot Now', $mailMessage->actionText);
    }

    public function test_notification_uses_correct_action_url_for_admin(): void
    {
        $this->user->update(['role' => UserRole::ADMIN]);
        
        $notification = new CloudStorageConnectionAlert(
            'google-drive',
            'token_expiring',
            $this->healthStatus
        );

        $mailMessage = $notification->toMail($this->user);
        
        $this->assertStringContainsString('admin/cloud-storage', $mailMessage->actionUrl);
    }

    public function test_notification_uses_correct_action_url_for_employee(): void
    {
        $this->user->update(['role' => UserRole::EMPLOYEE]);
        
        $notification = new CloudStorageConnectionAlert(
            'google-drive',
            'token_expiring',
            $this->healthStatus
        );

        $mailMessage = $notification->toMail($this->user);
        
        $this->assertStringContainsString('employee/', $mailMessage->actionUrl);
        $this->assertStringContainsString('/cloud-storage', $mailMessage->actionUrl);
    }

    public function test_notification_can_be_queued(): void
    {
        $notification = new CloudStorageConnectionAlert(
            'google-drive',
            'token_expiring',
            $this->healthStatus
        );

        $this->assertContains('Illuminate\Contracts\Queue\ShouldQueue', class_implements($notification));
        $this->assertEquals('notifications', $notification->queue);
    }

    public function test_notification_to_array_contains_expected_data(): void
    {
        $notification = new CloudStorageConnectionAlert(
            'google-drive',
            'token_expiring',
            $this->healthStatus,
            ['custom_context' => 'test']
        );

        $array = $notification->toArray($this->user);

        $this->assertEquals('google-drive', $array['provider']);
        $this->assertEquals('token_expiring', $array['alert_type']);
        $this->assertEquals('degraded', $array['status']);
        $this->assertEquals(3, $array['consecutive_failures']);
        $this->assertEquals('Token expired', $array['last_error']);
        $this->assertNotNull($array['token_expires_at']);
        $this->assertEquals(['custom_context' => 'test'], $array['context']);
    }
}