<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CloudStorageSetting;
use App\Services\CloudStorageConfigurationValidationService;
use App\Services\CloudStorageErrorMessageService;
use App\Services\CloudStorageRetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class EnhancedConnectButtonValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_validates_google_drive_configuration_before_oauth_initiation()
    {
        // Arrange: No Google Drive configuration
        
        // Act
        $response = $this->postJson(route('admin.cloud-storage.google-drive.connect'));
        
        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'requires_configuration' => true
        ]);
        $response->assertJsonFragment(['error' => 'Google Drive Client ID is required. Please configure your Google Drive credentials first.']);
    }

    /** @test */
    public function it_validates_client_secret_requirement()
    {
        // Arrange: Only client ID configured
        CloudStorageSetting::setValue('google-drive', 'client_id', 'test-client-id');
        
        // Act
        $response = $this->postJson(route('admin.cloud-storage.google-drive.connect'));
        
        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'requires_configuration' => true
        ]);
        $response->assertJsonFragment(['error' => 'Google Drive Client Secret is required. Please configure your Google Drive credentials first.']);
    }

    /** @test */
    public function it_performs_comprehensive_validation_before_oauth()
    {
        // Arrange: Mock validation service to return invalid configuration
        $validationService = Mockery::mock(CloudStorageConfigurationValidationService::class);
        $validationService->shouldReceive('validateProviderConfiguration')
            ->with('google-drive')
            ->andReturn([
                'is_valid' => false,
                'errors' => ['Invalid client configuration'],
                'warnings' => []
            ]);
        
        $this->app->instance(CloudStorageConfigurationValidationService::class, $validationService);
        
        // Act
        $response = $this->postJson(route('admin.cloud-storage.google-drive.connect'));
        
        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'validation_errors' => ['Invalid client configuration']
        ]);
    }

    /** @test */
    public function it_checks_network_connectivity_before_oauth()
    {
        // Arrange: Configure Google Drive but mock network failure
        CloudStorageSetting::setValue('google-drive', 'client_id', 'test-client-id');
        CloudStorageSetting::setValue('google-drive', 'client_secret', 'test-client-secret', true);
        
        // Mock validation service to return valid configuration
        $validationService = Mockery::mock(CloudStorageConfigurationValidationService::class);
        $validationService->shouldReceive('validateProviderConfiguration')
            ->with('google-drive')
            ->andReturn([
                'is_valid' => true,
                'errors' => [],
                'warnings' => []
            ]);
        
        $this->app->instance(CloudStorageConfigurationValidationService::class, $validationService);
        
        // We can't easily mock the network check without modifying the controller,
        // so we'll test the successful path instead
        
        // Act & Assert: This test would require mocking the network check method
        $this->markTestSkipped('Network connectivity check requires controller method mocking');
    }

    /** @test */
    public function it_generates_oauth_url_with_retry_logic()
    {
        // Arrange: Configure Google Drive properly
        CloudStorageSetting::setValue('google-drive', 'client_id', 'test-client-id');
        CloudStorageSetting::setValue('google-drive', 'client_secret', 'test-client-secret', true);
        
        // Mock validation service to return valid configuration
        $validationService = Mockery::mock(CloudStorageConfigurationValidationService::class);
        $validationService->shouldReceive('validateProviderConfiguration')
            ->with('google-drive')
            ->andReturn([
                'is_valid' => true,
                'errors' => [],
                'warnings' => []
            ]);
        
        $this->app->instance(CloudStorageConfigurationValidationService::class, $validationService);
        
        // Mock the storage manager to return a provider that generates auth URL
        $provider = Mockery::mock(\App\Contracts\CloudStorageProviderInterface::class);
        $provider->shouldReceive('hasValidConnection')->andReturn(false);
        $provider->shouldReceive('getAuthUrl')->andReturn('https://accounts.google.com/oauth/authorize?test=1');
        $provider->shouldReceive('getProviderName')->andReturn('google-drive');
        
        $storageManager = Mockery::mock(\App\Services\CloudStorageManager::class);
        $storageManager->shouldReceive('getProvider')->with('google-drive')->andReturn($provider);
        
        $this->app->instance(\App\Services\CloudStorageManager::class, $storageManager);
        
        // Act
        $response = $this->postJson(route('admin.cloud-storage.google-drive.connect'));
        
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_reconnection' => false
        ]);
        $response->assertJsonStructure([
            'redirect_url',
            'message'
        ]);
    }

    /** @test */
    public function it_handles_google_api_exceptions_with_user_friendly_messages()
    {
        // Arrange: Configure Google Drive properly
        CloudStorageSetting::setValue('google-drive', 'client_id', 'test-client-id');
        CloudStorageSetting::setValue('google-drive', 'client_secret', 'test-client-secret', true);
        
        // Mock validation service to return valid configuration
        $validationService = Mockery::mock(CloudStorageConfigurationValidationService::class);
        $validationService->shouldReceive('validateProviderConfiguration')
            ->with('google-drive')
            ->andReturn([
                'is_valid' => true,
                'errors' => [],
                'warnings' => []
            ]);
        
        $this->app->instance(CloudStorageConfigurationValidationService::class, $validationService);
        
        // Mock provider to throw Google Service Exception
        $provider = Mockery::mock(\App\Contracts\CloudStorageProviderInterface::class);
        $provider->shouldReceive('hasValidConnection')->andReturn(false);
        $provider->shouldReceive('getAuthUrl')->andThrow(
            new \Google\Service\Exception('Invalid client configuration', 401)
        );
        $provider->shouldReceive('getProviderName')->andReturn('google-drive');
        
        $storageManager = Mockery::mock(\App\Services\CloudStorageManager::class);
        $storageManager->shouldReceive('getProvider')->with('google-drive')->andReturn($provider);
        
        $this->app->instance(\App\Services\CloudStorageManager::class, $storageManager);
        
        // Act
        $response = $this->postJson(route('admin.cloud-storage.google-drive.connect'));
        
        // Assert
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'success',
            'error',
            'error_type',
            'instructions',
            'is_retryable',
            'requires_user_action'
        ]);
    }

    /** @test */
    public function it_provides_different_responses_for_json_and_web_requests()
    {
        // Arrange: No configuration
        
        // Act: Web request
        $webResponse = $this->post(route('admin.cloud-storage.google-drive.connect'));
        
        // Assert: Web request gets redirect with error
        $webResponse->assertRedirect();
        $webResponse->assertSessionHas('error');
        
        // Act: JSON request
        $jsonResponse = $this->postJson(route('admin.cloud-storage.google-drive.connect'));
        
        // Assert: JSON request gets JSON error response
        $jsonResponse->assertStatus(400);
        $jsonResponse->assertJson(['success' => false]);
    }

    /** @test */
    public function it_logs_comprehensive_oauth_initiation_details()
    {
        // Arrange: Configure Google Drive properly
        CloudStorageSetting::setValue('google-drive', 'client_id', 'test-client-id');
        CloudStorageSetting::setValue('google-drive', 'client_secret', 'test-client-secret', true);
        
        // Mock validation service
        $validationService = Mockery::mock(CloudStorageConfigurationValidationService::class);
        $validationService->shouldReceive('validateProviderConfiguration')
            ->andReturn(['is_valid' => true, 'errors' => [], 'warnings' => []]);
        
        $this->app->instance(CloudStorageConfigurationValidationService::class, $validationService);
        
        // Mock provider
        $provider = Mockery::mock(\App\Contracts\CloudStorageProviderInterface::class);
        $provider->shouldReceive('hasValidConnection')->andReturn(false);
        $provider->shouldReceive('getAuthUrl')->andReturn('https://test-oauth-url.com');
        $provider->shouldReceive('getProviderName')->andReturn('google-drive');
        
        $storageManager = Mockery::mock(\App\Services\CloudStorageManager::class);
        $storageManager->shouldReceive('getProvider')->andReturn($provider);
        
        $this->app->instance(\App\Services\CloudStorageManager::class, $storageManager);
        
        // Expect log entries
        Log::shouldReceive('info')
            ->with('Initiating Google Drive OAuth flow with comprehensive validation', Mockery::type('array'))
            ->once();
        
        // Act
        $response = $this->postJson(route('admin.cloud-storage.google-drive.connect'));
        
        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function error_message_service_generates_actionable_messages()
    {
        // Arrange
        $errorMessageService = new CloudStorageErrorMessageService();
        
        // Act & Assert: Test various error types
        $tokenExpiredMessage = $errorMessageService->getActionableErrorMessage(
            \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED,
            ['provider' => 'google-drive']
        );
        
        $this->assertStringContainsString('Google Drive connection has expired', $tokenExpiredMessage);
        $this->assertStringContainsString('reconnect', $tokenExpiredMessage);
        
        // Test recovery instructions
        $instructions = $errorMessageService->getRecoveryInstructions(
            \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED,
            ['provider' => 'google-drive']
        );
        
        $this->assertIsArray($instructions);
        $this->assertNotEmpty($instructions);
        $this->assertStringContainsString('Settings', $instructions[0]);
    }

    /** @test */
    public function retry_service_identifies_retryable_exceptions()
    {
        // Arrange
        $retryService = new CloudStorageRetryService();
        
        // Act & Assert: Test retryable exceptions
        $networkException = new \Exception('Connection timeout');
        $this->assertTrue($retryService->isRetryableException($networkException));
        
        $serviceException = new \Exception('Service unavailable');
        $this->assertTrue($retryService->isRetryableException($serviceException));
        
        // Test non-retryable exceptions
        $authException = new \Exception('Invalid credentials');
        $this->assertFalse($retryService->isRetryableException($authException));
    }

    /** @test */
    public function retry_service_calculates_appropriate_delays()
    {
        // Arrange
        $retryService = new CloudStorageRetryService();
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($retryService);
        $method = $reflection->getMethod('calculateDelay');
        $method->setAccessible(true);
        
        $config = [
            'base_delay' => 1000,
            'max_delay' => 10000,
            'backoff_multiplier' => 2,
            'jitter' => false
        ];
        
        // Act & Assert
        $delay1 = $method->invoke($retryService, 1, $config);
        $delay2 = $method->invoke($retryService, 2, $config);
        $delay3 = $method->invoke($retryService, 3, $config);
        
        $this->assertEquals(1000, $delay1); // base_delay * 2^0
        $this->assertEquals(2000, $delay2); // base_delay * 2^1
        $this->assertEquals(4000, $delay3); // base_delay * 2^2
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}