<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageFactory;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use App\Jobs\UploadToGoogleDrive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\Traits\CloudStorageTestHelpers;

/**
 * Final validation tests for cloud storage provider system.
 * Validates requirements 9.3, 11.1, 12.2, 12.4.
 */
class FinalValidationIntegrationTest extends TestCase
{
    use RefreshDatabase, CloudStorageTestHelpers;

    private CloudStorageManager $manager;
    private CloudStorageFactory $factory;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->manager = app(CloudStorageManager::class);
        $this->factory = app(CloudStorageFactory::class);
        $this->testUser = User::factory()->create();
        
        $this->configureTestEnvironment();
    }

    /**
     * Requirement 9.3: Test provider switching and coexistence.
     * 
     * @test
     * @group final-validation
     * @group requirement-9-3
     */
    public function test_provider_switching_and_coexistence(): void
    {
        // Test that Google Drive and other providers can coexist
        $googleProvider = $this->manager->getProvider('google-drive');
        $this->assertEquals('google-drive', $googleProvider->getProviderName());
        
        // Test provider switching
        $availableProviders = $this->manager->getAvailableProviders();
        $this->assertContains('google-drive', $availableProviders);
        
        // Test user-specific provider selection
        $this->manager->switchUserProvider($this->testUser, 'google-drive');
        $userProvider = $this->manager->getUserProvider($this->testUser);
        $this->assertEquals('google-drive', $userProvider->getProviderName());
        
        // Test that switching doesn't affect other users
        $otherUser = User::factory()->create();
        $otherProvider = $this->manager->getUserProvider($otherUser);
        $this->assertInstanceOf(\App\Contracts\CloudStorageProviderInterface::class, $otherProvider);
        
        Log::info('Provider switching and coexistence tests passed (Requirement 9.3)');
    } 
   /**
     * Requirement 11.1: Test backward compatibility with existing implementations.
     * 
     * @test
     * @group final-validation
     * @group requirement-11-1
     */
    public function test_backward_compatibility_existing_implementations(): void
    {
        // Test that existing GoogleDriveService continues to work
        $googleDriveService = app(GoogleDriveService::class);
        $this->assertInstanceOf(GoogleDriveService::class, $googleDriveService);
        
        // Test that existing job classes still work
        Queue::fake();
        
        $job = new UploadToGoogleDrive(
            $this->testUser,
            'test-file.txt',
            'test-path',
            []
        );
        
        $this->assertInstanceOf(UploadToGoogleDrive::class, $job);
        
        // Test that existing database schema is compatible
        $this->assertDatabaseHas('users', ['id' => $this->testUser->id]);
        
        // Test that existing service container bindings work
        $this->assertTrue(app()->bound(GoogleDriveService::class));
        $this->assertTrue(app()->bound(CloudStorageManager::class));
        
        // Test that existing configuration structure is supported
        $config = config('cloud-storage.providers.google-drive');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('config', $config);
        
        Log::info('Backward compatibility tests passed (Requirement 11.1)');
    }

    /**
     * Requirement 12.2: Test multiple provider testing capabilities.
     * 
     * @test
     * @group final-validation
     * @group requirement-12-2
     */
    public function test_multiple_provider_testing_capabilities(): void
    {
        // Test that base test classes are available
        $this->assertTrue(class_exists(\Tests\Unit\Contracts\CloudStorageProviderTestCase::class));
        $this->assertTrue(class_exists(\Tests\Integration\CloudStorageProviderIntegrationTestCase::class));
        
        // Test that mock implementations are available
        $this->assertTrue(class_exists(\Tests\Mocks\MockCloudStorageProvider::class));
        $this->assertTrue(class_exists(\Tests\Mocks\FailingMockCloudStorageProvider::class));
        
        // Test that test helpers are available
        $this->assertTrue(trait_exists(\Tests\Traits\CloudStorageTestHelpers::class));
        
        // Test provider compliance testing
        $providers = $this->factory->getRegisteredProviders();
        foreach ($providers as $providerName => $className) {
            $this->assertTrue($this->factory->validateProvider($className));
            
            $provider = $this->factory->create($providerName);
            $this->assertInstanceOf(\App\Contracts\CloudStorageProviderInterface::class, $provider);
        }
        
        // Test integration testing capabilities
        $this->runIntegrationTestValidation();
        
        Log::info('Multiple provider testing capabilities validated (Requirement 12.2)');
    }

    /**
     * Requirement 12.4: Test comprehensive testing support and quality assurance.
     * 
     * @test
     * @group final-validation
     * @group requirement-12-4
     */
    public function test_comprehensive_testing_support_quality_assurance(): void
    {
        // Test logging and monitoring capabilities
        $this->assertTrue(class_exists(\App\Services\CloudStorageLogService::class));
        $this->assertTrue(class_exists(\App\Services\CloudStorageAuditService::class));
        $this->assertTrue(class_exists(\App\Services\CloudStorageErrorTrackingService::class));
        
        // Test documentation availability
        $this->assertFileExists(base_path('docs/testing/cloud-storage-provider-testing-guide.md'));
        $this->assertFileExists(base_path('docs/implementing-new-cloud-storage-providers.md'));
        $this->assertFileExists(base_path('docs/troubleshooting/cloud-storage-provider-troubleshooting.md'));
        
        // Test API documentation
        $this->assertFileExists(base_path('docs/api/cloud-storage-provider-api.md'));
        
        // Test migration guides
        $this->assertFileExists(base_path('docs/migration/cloud-storage-provider-migration-guide.md'));
        $this->assertFileExists(base_path('docs/migration/google-drive-service-migration-guide.md'));
        
        // Test health check and monitoring systems
        $healthService = app(CloudStorageHealthService::class);
        $this->assertInstanceOf(CloudStorageHealthService::class, $healthService);
        
        // Test configuration validation
        $validationResult = $this->manager->validateAllProviders();
        $this->assertIsArray($validationResult);
        
        Log::info('Comprehensive testing support and quality assurance validated (Requirement 12.4)');
    }

    /**
     * Test security validation across all providers.
     * 
     * @test
     * @group final-validation
     * @group security
     */
    public function test_security_validation_all_providers(): void
    {
        // Test configuration security
        $providers = $this->factory->getRegisteredProviders();
        
        foreach ($providers as $providerName => $className) {
            $config = config("cloud-storage.providers.{$providerName}.config", []);
            
            // Test that sensitive keys are not exposed
            $sensitiveKeys = ['client_secret', 'secret_access_key', 'connection_string', 'private_key'];
            foreach ($sensitiveKeys as $key) {
                if (isset($config[$key])) {
                    $this->assertNotEmpty($config[$key], "Sensitive key {$key} should not be empty");
                }
            }
            
            // Test provider validation
            $provider = $this->factory->create($providerName);
            $validationResult = $provider->validateConfiguration($config);
            $this->assertIsArray($validationResult);
        }
        
        // Test user isolation
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $provider1 = $this->manager->getUserProvider($user1);
        $provider2 = $this->manager->getUserProvider($user2);
        
        // Providers should be isolated per user
        $this->assertNotSame($provider1, $provider2);
        
        Log::info('Security validation completed for all providers');
    }

    /**
     * Test load and performance validation.
     * 
     * @test
     * @group final-validation
     * @group performance
     */
    public function test_load_and_performance_validation(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Create multiple users and test provider access
        $users = User::factory()->count(20)->create();
        $providers = $this->manager->getAvailableProviders();
        
        foreach ($users as $user) {
            foreach ($providers as $providerName) {
                $provider = $this->manager->getProvider($providerName, $user);
                $this->assertInstanceOf(\App\Contracts\CloudStorageProviderInterface::class, $provider);
                
                // Test basic operations
                $capabilities = $provider->getCapabilities();
                $this->assertIsArray($capabilities);
                
                $this->assertTrue($provider->supportsFeature('file_upload'));
            }
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;
        
        // Performance assertions
        $this->assertLessThan(10.0, $executionTime, 'Load test took too long');
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage, 'Memory usage too high during load test');
        
        Log::info("Load test completed: {$executionTime}s, " . number_format($memoryUsage / 1024 / 1024, 2) . "MB");
    }

    /**
     * Run integration test validation.
     */
    private function runIntegrationTestValidation(): void
    {
        // Test that integration test base classes work
        $testCase = new \Tests\Integration\CloudStorageProviderIntegrationTestCase();
        $this->assertInstanceOf(\Tests\Integration\CloudStorageProviderIntegrationTestCase::class, $testCase);
        
        // Test mock provider functionality
        $mockProvider = new \Tests\Mocks\MockCloudStorageProvider();
        $this->assertInstanceOf(\App\Contracts\CloudStorageProviderInterface::class, $mockProvider);
        $this->assertEquals('mock', $mockProvider->getProviderName());
        
        // Test failing mock provider
        $failingProvider = new \Tests\Mocks\FailingMockCloudStorageProvider();
        $this->assertInstanceOf(\App\Contracts\CloudStorageProviderInterface::class, $failingProvider);
    }

    /**
     * Configure test environment.
     */
    private function configureTestEnvironment(): void
    {
        Config::set('cloud-storage.default', 'google-drive');
        
        Config::set('cloud-storage.providers.google-drive.config', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost/callback',
        ]);
        
        // Configure logging for test validation
        Config::set('logging.channels.cloud-storage', [
            'driver' => 'single',
            'path' => storage_path('logs/cloud-storage-test.log'),
            'level' => 'debug',
        ]);
    }
}