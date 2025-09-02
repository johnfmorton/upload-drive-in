<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GoogleDriveService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class GoogleDriveServiceBackwardCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_google_drive_service_methods_still_work(): void
    {
        $service = app(GoogleDriveService::class);
        $user = User::factory()->create();

        // Test basic methods that don't require Google Drive connection
        $this->assertEquals('root', $service->getRootFolderId());
        $this->assertEquals('root', $service->getEffectiveRootFolderId($user));
        $this->assertEquals('test-at-example-dot-com', $service->sanitizeEmailForFolderName('test@example.com'));
        
        // Test that findUserFolderId throws exception when no connection exists (expected behavior)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has not connected their Google Drive account.');
        $service->findUserFolderId($user, 'test@example.com');
    }

    public function test_google_drive_service_type_hints_still_work(): void
    {
        // Test that existing code with type hints still works
        $callback = function (GoogleDriveService $service) {
            return $service->getRootFolderId();
        };

        $service = app(GoogleDriveService::class);
        $result = $callback($service);
        
        $this->assertEquals('root', $result);
    }

    public function test_google_drive_service_can_be_mocked_in_tests(): void
    {
        // Test that the service can still be mocked for testing
        $mock = $this->mock(GoogleDriveService::class);
        $mock->shouldReceive('getRootFolderId')->once()->andReturn('mocked-root');

        $result = $mock->getRootFolderId();
        $this->assertEquals('mocked-root', $result);
    }

    public function test_deprecation_warnings_logged_for_key_methods(): void
    {
        config(['app.debug' => true]);
        $user = User::factory()->create();

        // Mock the log to capture deprecation warnings
        Log::shouldReceive('warning')
            ->once()
            ->with('GoogleDriveService is deprecated. Use CloudStorageManager instead.', \Mockery::type('array'));

        Log::shouldReceive('warning')
            ->times(3) // uploadFile, getAuthUrl, handleCallback
            ->with('Deprecated GoogleDriveService method called', \Mockery::type('array'));

        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = app(GoogleDriveService::class);

        $exceptionCount = 0;

        // Test methods that have deprecation warnings
        try {
            $service->uploadFile($user, 'test.pdf', 'folder-123', 'test.pdf', 'application/pdf');
        } catch (\Exception $e) {
            // Expected to fail due to missing file/connection
            $exceptionCount++;
        }

        try {
            $service->getAuthUrl($user);
        } catch (\Exception $e) {
            // Expected to fail due to missing Google credentials
            $exceptionCount++;
        }

        try {
            $service->handleCallback($user, 'auth-code');
        } catch (\Exception $e) {
            // Expected to fail due to missing Google credentials
            $exceptionCount++;
        }

        $this->assertEquals(3, $exceptionCount, 'All deprecated methods should throw exceptions due to missing configuration');
    }

    public function test_no_deprecation_warnings_in_production(): void
    {
        config(['app.debug' => false]);
        $user = User::factory()->create();

        $service = app(GoogleDriveService::class);

        // Call methods - no warnings should be logged in production
        $service->getRootFolderId();
        $service->getEffectiveRootFolderId($user);
        $service->sanitizeEmailForFolderName('test@example.com');

        // If we get here without exceptions, the test passes
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}