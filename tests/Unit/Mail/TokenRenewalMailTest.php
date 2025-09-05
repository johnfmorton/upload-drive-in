<?php

namespace Tests\Unit\Mail;

use App\Enums\TokenRefreshErrorType;
use App\Mail\ConnectionRestoredMail;
use App\Mail\TokenExpiredMail;
use App\Mail\TokenRefreshFailedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenRenewalMailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $adminUser;
    private User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => \App\Enums\UserRole::EMPLOYEE
        ]);
        
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => \App\Enums\UserRole::ADMIN
        ]);
        
        $this->clientUser = User::factory()->create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'role' => \App\Enums\UserRole::CLIENT
        ]);
    }

    public function test_token_expired_mail_envelope()
    {
        $mail = new TokenExpiredMail($this->user, 'google-drive');
        $envelope = $mail->envelope();
        
        $this->assertEquals('Google Drive Connection Expired - Action Required', $envelope->subject);
    }

    public function test_token_expired_mail_content()
    {
        $mail = new TokenExpiredMail($this->user, 'google-drive');
        $content = $mail->content();
        
        $this->assertEquals('emails.token-expired', $content->view);
        $this->assertArrayHasKey('user', $content->with);
        $this->assertArrayHasKey('provider', $content->with);
        $this->assertArrayHasKey('providerName', $content->with);
        $this->assertArrayHasKey('reconnectUrl', $content->with);
        $this->assertArrayHasKey('supportEmail', $content->with);
        
        $this->assertEquals($this->user->id, $content->with['user']->id);
        $this->assertEquals('google-drive', $content->with['provider']);
        $this->assertEquals('Google Drive', $content->with['providerName']);
        $this->assertStringContainsString('/admin/cloud-storage/google-drive/connect', $content->with['reconnectUrl']);
    }

    public function test_token_expired_mail_reconnect_urls()
    {
        $testCases = [
            'google-drive' => '/admin/cloud-storage/google-drive/connect',
            'microsoft-teams' => '/admin/cloud-storage',
            'dropbox' => '/admin/cloud-storage',
            'unknown-provider' => '/admin/cloud-storage',
        ];
        
        foreach ($testCases as $provider => $expectedPath) {
            $mail = new TokenExpiredMail($this->user, $provider);
            $this->assertStringContainsString($expectedPath, $mail->reconnectUrl);
        }
    }

    public function test_token_expired_mail_provider_display_names()
    {
        $testCases = [
            'google-drive' => 'Google Drive',
            'microsoft-teams' => 'Microsoft Teams',
            'dropbox' => 'Dropbox',
            'custom-provider' => 'Custom Provider',
        ];
        
        foreach ($testCases as $provider => $expectedName) {
            $mail = new TokenExpiredMail($this->user, $provider);
            $content = $mail->content();
            $this->assertEquals($expectedName, $content->with['providerName']);
        }
    }

    public function test_token_refresh_failed_mail_envelope()
    {
        $errorType = TokenRefreshErrorType::NETWORK_TIMEOUT;
        $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, 2);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Google Drive Connection Issue - Connection Issue', $envelope->subject);
        
        // Test with error that requires user intervention
        $errorType = TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
        $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, 2);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Google Drive Connection Issue - Action Required', $envelope->subject);
    }

    public function test_token_refresh_failed_mail_content()
    {
        $errorType = TokenRefreshErrorType::NETWORK_TIMEOUT;
        $attemptCount = 3;
        $errorMessage = 'Connection timeout after 30 seconds';
        
        $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, $attemptCount, $errorMessage);
        $content = $mail->content();
        
        $this->assertEquals('emails.token-refresh-failed', $content->view);
        
        $expectedKeys = [
            'user', 'provider', 'providerName', 'errorType', 'errorTypeName', 
            'errorDescription', 'attemptCount', 'errorMessage', 'reconnectUrl', 
            'requiresUserAction', 'nextRetryInfo', 'supportEmail', 'isRecoverable'
        ];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $content->with);
        }
        
        $this->assertEquals($this->user->id, $content->with['user']->id);
        $this->assertEquals('google-drive', $content->with['provider']);
        $this->assertEquals('Google Drive', $content->with['providerName']);
        $this->assertEquals($errorType, $content->with['errorType']);
        $this->assertEquals('Network Timeout', $content->with['errorTypeName']);
        $this->assertEquals($attemptCount, $content->with['attemptCount']);
        $this->assertEquals($errorMessage, $content->with['errorMessage']);
        $this->assertFalse($content->with['requiresUserAction']);
        $this->assertTrue($content->with['isRecoverable']);
    }

    public function test_token_refresh_failed_mail_error_descriptions()
    {
        $testCases = [
            [TokenRefreshErrorType::NETWORK_TIMEOUT, 'network timeout'],
            [TokenRefreshErrorType::INVALID_REFRESH_TOKEN, 'no longer valid'],
            [TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, 'expired and cannot be renewed'],
            [TokenRefreshErrorType::API_QUOTA_EXCEEDED, 'temporarily limited our access'],
            [TokenRefreshErrorType::SERVICE_UNAVAILABLE, 'temporarily unavailable'],
            [TokenRefreshErrorType::UNKNOWN_ERROR, 'unexpected error occurred'],
        ];
        
        foreach ($testCases as [$errorType, $expectedPhrase]) {
            $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, 1);
            $content = $mail->content();
            
            $this->assertStringContainsStringIgnoringCase(
                $expectedPhrase, 
                $content->with['errorDescription']
            );
        }
    }

    public function test_token_refresh_failed_mail_next_retry_info()
    {
        // Test recoverable error with retries remaining
        $errorType = TokenRefreshErrorType::NETWORK_TIMEOUT;
        $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, 1);
        $this->assertStringContainsString('retry', $mail->nextRetryInfo);
        $this->assertStringContainsString('attempts remaining', $mail->nextRetryInfo);
        
        // Test non-recoverable error
        $errorType = TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
        $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, 1);
        $this->assertStringContainsString('No automatic retry', $mail->nextRetryInfo);
        
        // Test max attempts reached
        $errorType = TokenRefreshErrorType::NETWORK_TIMEOUT;
        $maxAttempts = $errorType->getMaxRetryAttempts();
        $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, $maxAttempts);
        $this->assertStringContainsString('Maximum retry attempts reached', $mail->nextRetryInfo);
    }

    public function test_token_refresh_failed_mail_requires_user_action()
    {
        $requiresActionTypes = [
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN,
        ];
        
        $automaticRetryTypes = [
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            TokenRefreshErrorType::API_QUOTA_EXCEEDED,
            TokenRefreshErrorType::SERVICE_UNAVAILABLE,
        ];
        
        foreach ($requiresActionTypes as $errorType) {
            $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, 1);
            $this->assertTrue($mail->requiresUserAction);
        }
        
        foreach ($automaticRetryTypes as $errorType) {
            $mail = new TokenRefreshFailedMail($this->user, 'google-drive', $errorType, 1);
            $this->assertFalse($mail->requiresUserAction);
        }
    }

    public function test_connection_restored_mail_envelope()
    {
        $mail = new ConnectionRestoredMail($this->user, 'google-drive');
        $envelope = $mail->envelope();
        
        $this->assertEquals('Google Drive Connection Restored', $envelope->subject);
    }

    public function test_connection_restored_mail_content()
    {
        $mail = new ConnectionRestoredMail($this->user, 'google-drive');
        $content = $mail->content();
        
        $this->assertEquals('emails.connection-restored', $content->view);
        $this->assertArrayHasKey('user', $content->with);
        $this->assertArrayHasKey('provider', $content->with);
        $this->assertArrayHasKey('providerName', $content->with);
        $this->assertArrayHasKey('dashboardUrl', $content->with);
        $this->assertArrayHasKey('supportEmail', $content->with);
        
        $this->assertEquals($this->user->id, $content->with['user']->id);
        $this->assertEquals('google-drive', $content->with['provider']);
        $this->assertEquals('Google Drive', $content->with['providerName']);
    }

    public function test_connection_restored_mail_dashboard_urls()
    {
        // Test admin user
        $mail = new ConnectionRestoredMail($this->adminUser, 'google-drive');
        $this->assertStringContainsString('/admin/dashboard', $mail->dashboardUrl);
        
        // Test employee user
        $mail = new ConnectionRestoredMail($this->user, 'google-drive');
        $this->assertStringContainsString('/employee/dashboard', $mail->dashboardUrl);
        
        // Test client user
        $mail = new ConnectionRestoredMail($this->clientUser, 'google-drive');
        $this->assertStringContainsString('/client/dashboard', $mail->dashboardUrl);
    }

    public function test_all_mails_include_support_email()
    {
        config(['mail.support_email' => 'support@example.com']);
        
        $tokenExpiredMail = new TokenExpiredMail($this->user, 'google-drive');
        $tokenExpiredContent = $tokenExpiredMail->content();
        $this->assertEquals('support@example.com', $tokenExpiredContent->with['supportEmail']);
        
        $refreshFailedMail = new TokenRefreshFailedMail($this->user, 'google-drive', TokenRefreshErrorType::NETWORK_TIMEOUT, 1);
        $refreshFailedContent = $refreshFailedMail->content();
        $this->assertEquals('support@example.com', $refreshFailedContent->with['supportEmail']);
        
        $restoredMail = new ConnectionRestoredMail($this->user, 'google-drive');
        $restoredContent = $restoredMail->content();
        $this->assertEquals('support@example.com', $restoredContent->with['supportEmail']);
    }

    public function test_all_mails_fallback_to_from_email_when_no_support_email()
    {
        // Set up config without support_email
        config(['mail.support_email' => null]);
        config(['mail.from.address' => 'noreply@example.com']);
        
        $tokenExpiredMail = new TokenExpiredMail($this->user, 'google-drive');
        $tokenExpiredContent = $tokenExpiredMail->content();
        $this->assertEquals('noreply@example.com', $tokenExpiredContent->with['supportEmail']);
        
        $refreshFailedMail = new TokenRefreshFailedMail($this->user, 'google-drive', TokenRefreshErrorType::NETWORK_TIMEOUT, 1);
        $refreshFailedContent = $refreshFailedMail->content();
        $this->assertEquals('noreply@example.com', $refreshFailedContent->with['supportEmail']);
        
        $restoredMail = new ConnectionRestoredMail($this->user, 'google-drive');
        $restoredContent = $restoredMail->content();
        $this->assertEquals('noreply@example.com', $restoredContent->with['supportEmail']);
    }

    public function test_mail_serialization()
    {
        // Test that all mail classes can be serialized (important for queuing)
        $tokenExpiredMail = new TokenExpiredMail($this->user, 'google-drive');
        $serialized = serialize($tokenExpiredMail);
        $unserialized = unserialize($serialized);
        $this->assertEquals($tokenExpiredMail->user->id, $unserialized->user->id);
        $this->assertEquals($tokenExpiredMail->provider, $unserialized->provider);
        
        $refreshFailedMail = new TokenRefreshFailedMail($this->user, 'google-drive', TokenRefreshErrorType::NETWORK_TIMEOUT, 2, 'Test error');
        $serialized = serialize($refreshFailedMail);
        $unserialized = unserialize($serialized);
        $this->assertEquals($refreshFailedMail->user->id, $unserialized->user->id);
        $this->assertEquals($refreshFailedMail->errorType, $unserialized->errorType);
        $this->assertEquals($refreshFailedMail->attemptCount, $unserialized->attemptCount);
        
        $restoredMail = new ConnectionRestoredMail($this->user, 'google-drive');
        $serialized = serialize($restoredMail);
        $unserialized = unserialize($serialized);
        $this->assertEquals($restoredMail->user->id, $unserialized->user->id);
        $this->assertEquals($restoredMail->provider, $unserialized->provider);
    }
}