<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloudStorageHealthServiceConsolidatedStatusTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageHealthService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $logService = new CloudStorageLogService();
        $this->service = new CloudStorageHealthService($logService);
        $this->user = User::factory()->create();
        
        // Mock Log facade to prevent actual logging during tests
        Log::shouldReceive('debug')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
        Log::shouldReceive('info')->andReturn(null);
    }

    public function test_healthy_status_eliminates_token_warnings(): void
    {
        // Create a health status with healthy consolidated status but expired token
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'token_expires_at' => now()->subHour(), // Expired token
        ]);

        $summary = $this->service->getHealthSummary($this->user, 'google-drive');

        // Verify consolidated status is used
        $this->assertEquals('healthy', $summary['consolidated_status']);
        $this->assertEquals('Connection is working properly', $summary['status_message']);
        $this->assertTrue($summary['is_healthy']);
        
        // Verify token warnings are NOT shown when healthy
        $this->assertFalse($summary['token_expiring_soon'], 'Token expiring soon should be false when healthy');
        $this->assertFalse($summary['token_expired'], 'Token expired should be false when healthy');
    }

    public function test_authentication_required_shows_appropriate_message(): void
    {
        // Create a health status with authentication required
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => 3,
            'requires_reconnection' => true,
            'token_expires_at' => now()->subHour(), // Expired token
        ]);

        $summary = $this->service->getHealthSummary($this->user, 'google-drive');

        // Verify consolidated status is used
        $this->assertEquals('authentication_required', $summary['consolidated_status']);
        $this->assertEquals('Please reconnect your account', $summary['status_message']);
        $this->assertFalse($summary['is_healthy']);
        
        // Verify token warnings ARE shown when not healthy
        $this->assertTrue($summary['token_expired'], 'Token expired should be true when not healthy and token is expired');
        $this->assertTrue($summary['requires_reconnection'], 'Should require reconnection');
    }

    public function test_connection_issues_shows_appropriate_message(): void
    {
        // Create a health status with connection issues
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => 2,
            'requires_reconnection' => false,
            'token_expires_at' => now()->addDays(7), // Valid token, not expiring soon
        ]);

        $summary = $this->service->getHealthSummary($this->user, 'google-drive');

        // Verify consolidated status is used
        $this->assertEquals('connection_issues', $summary['consolidated_status']);
        $this->assertEquals('Experiencing connectivity problems', $summary['status_message']);
        $this->assertFalse($summary['is_healthy']);
        
        // Verify token warnings are NOT shown when token is valid
        $this->assertFalse($summary['token_expiring_soon'], 'Token expiring soon should be false when token is valid');
        $this->assertFalse($summary['token_expired'], 'Token expired should be false when token is valid');
        $this->assertFalse($summary['requires_reconnection'], 'Should not require reconnection for connection issues');
    }

    public function test_token_expiring_soon_only_shown_when_not_healthy(): void
    {
        // Create a health status with token expiring soon but healthy connection
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'token_expires_at' => now()->addHours(2), // Expiring soon (within 24 hours)
        ]);

        $summary = $this->service->getHealthSummary($this->user, 'google-drive');

        // When healthy, token warnings should be suppressed
        $this->assertTrue($summary['is_healthy']);
        $this->assertFalse($summary['token_expiring_soon'], 'Token expiring soon should be false when healthy');
        $this->assertFalse($summary['token_expired'], 'Token expired should be false when healthy');

        // Now change to unhealthy status
        $healthStatus->update([
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'requires_reconnection' => true,
        ]);

        $summary = $this->service->getHealthSummary($this->user, 'google-drive');

        // When not healthy, token warnings should be shown
        $this->assertFalse($summary['is_healthy']);
        $this->assertTrue($summary['token_expiring_soon'], 'Token expiring soon should be true when not healthy and token is expiring');
        $this->assertFalse($summary['token_expired'], 'Token expired should be false when token is not yet expired');
    }

    public function test_consolidated_status_message_consistency(): void
    {
        $testCases = [
            ['healthy', 'Connection is working properly'],
            ['authentication_required', 'Please reconnect your account'],
            ['connection_issues', 'Experiencing connectivity problems'],
            ['not_connected', 'Account not connected'],
        ];

        foreach ($testCases as [$status, $expectedMessage]) {
            $healthStatus = CloudStorageHealthStatus::create([
                'user_id' => $this->user->id,
                'provider' => 'google-drive',
                'status' => 'healthy',
                'consolidated_status' => $status,
                'consecutive_failures' => 0,
                'requires_reconnection' => false,
            ]);

            $summary = $this->service->getHealthSummary($this->user, 'google-drive');

            $this->assertEquals($status, $summary['consolidated_status']);
            $this->assertEquals($expectedMessage, $summary['status_message']);

            // Clean up for next iteration
            $healthStatus->delete();
        }
    }
}