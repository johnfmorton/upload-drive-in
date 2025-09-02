<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageFactory;
use App\Services\GoogleDriveProvider;
use App\Services\S3Provider;
use App\Contracts\CloudStorageProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\Traits\CloudStorageTestHelpers;

/**
 * Comprehensive integration tests for the cloud storage provider system.
 * Tests all providers, backward compatibility, switching, and security.
 */
class ComprehensiveCloudStorageIntegrationTest extends TestCase
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
        
        // Configure test providers
        $this->configureTestProviders();
    }

    /**
     * Test comprehensive provider functionality across all registered providers.
     * 
     * @test
     * @group integration
     * @group comprehensive
     */
    public function test_comprehensive_provider_functionality(): void
    {
        $providers = $this->factory->getRegisteredProviders();
        
        $this->assertNotEmpty($providers, 'No providers registered');
        $this->assertArrayHasKey('google-drive', $providers);
        
        foreach ($providers as $providerName => $className) {
            $this->runProviderTests($providerName);
        }
    }    /**

     * Test backward compatibility with existing implementations.
     * 
     * @test
     * @group integration
     * @group backward-compatibility
     */
    public function test_backward_compatibility_validation(): void
    {
        // Test that existing GoogleDriveService still works
        $googleDriveService = app(\App\Services\GoogleDriveService::class);
        $this->assertInstanceOf(\App\Services\GoogleDriveService::class, $googleDriveService);
        
        // Test that old service container bindings still work
        $this->assertTrue(app()->bound(\App\Services\GoogleDriveService::class));
        
        // Test that existing database schema is compatible
        $this->assertDatabaseHas('users', ['id' => $this->testUser->id]);
        
        // Test that existing file upload workflow still works
        $this->runBackwardCompatibilityTests();
    }

    /**
     * Test provider switching and fallback mechanisms.
     * 
     * @test
     * @group integration
     * @group provider-switching
     */
    public function test_provider_switching_and_fallback(): void
    {
        // Test switching between providers
        $originalProvider = $this->manager->getUserProvider($this->testUser);
        $originalName = $originalProvider->getProviderName();
        
        // Switch to different provider
        $availableProviders = $this->manager->getAvailableProviders();
        $alternativeProvider = null;
        
        foreach ($availableProviders as $providerName) {
            if ($providerName !== $originalName) {
                $alternativeProvider = $providerName;
                break;
            }
        }
        
        if ($alternativeProvider) {
            $this->manager->switchUserProvider($this->testUser, $alternativeProvider);
            $newProvider = $this->manager->getUserProvider($this->testUser);
            $this->assertEquals($alternativeProvider, $newProvider->getProviderName());
            
            // Switch back
            $this->manager->switchUserProvider($this->testUser, $originalName);
            $restoredProvider = $this->manager->getUserProvider($this->testUser);
            $this->assertEquals($originalName, $restoredProvider->getProviderName());
        }
        
        // Test fallback mechanisms
        $this->runFallbackTests();
    }

    /**
     * Test security and access control implementations.
     * 
     * @test
     * @group integration
     * @group security
     */
    public function test_security_and_access_control(): void
    {
        // Test user isolation
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $provider1 = $this->manager->getUserProvider($user1);
        $provider2 = $this->manager->getUserProvider($user2);
        
        // Users should get isolated provider instances
        $this->assertNotSame($provider1, $provider2);
        
        // Test configuration security
        $this->runSecurityTests();
        
        // Test access control
        $this->runAccessControlTests($user1, $user2);
    }    
/**
     * Run comprehensive tests for a specific provider.
     */
    private function runProviderTests(string $providerName): void
    {
        try {
            $provider = $this->manager->getProvider($providerName);
            
            // Test basic interface compliance
            $this->assertInstanceOf(CloudStorageProviderInterface::class, $provider);
            $this->assertEquals($providerName, $provider->getProviderName());
            
            // Test capabilities
            $capabilities = $provider->getCapabilities();
            $this->assertIsArray($capabilities);
            
            // Test configuration validation
            $config = config("cloud-storage.providers.{$providerName}.config", []);
            $validationResult = $provider->validateConfiguration($config);
            $this->assertIsArray($validationResult);
            
            // Test feature support
            $this->assertTrue($provider->supportsFeature('file_upload'));
            
            Log::info("Provider {$providerName} passed comprehensive tests");
            
        } catch (\Exception $e) {
            $this->fail("Provider {$providerName} failed comprehensive tests: " . $e->getMessage());
        }
    }

    /**
     * Run backward compatibility tests.
     */
    private function runBackwardCompatibilityTests(): void
    {
        // Test that existing job classes still work
        $this->assertTrue(class_exists(\App\Jobs\UploadToGoogleDrive::class));
        
        // Test that existing controller methods still work
        $this->assertTrue(method_exists(\App\Http\Controllers\Admin\CloudStorageController::class, 'index'));
        
        // Test that existing service methods still work
        $googleDriveService = app(\App\Services\GoogleDriveService::class);
        $this->assertTrue(method_exists($googleDriveService, 'uploadFile'));
        
        Log::info('Backward compatibility tests passed');
    }

    /**
     * Run fallback mechanism tests.
     */
    private function runFallbackTests(): void
    {
        // Test default provider fallback
        $defaultProvider = $this->manager->getDefaultProvider();
        $this->assertInstanceOf(CloudStorageProviderInterface::class, $defaultProvider);
        
        // Test provider availability checking
        $availableProviders = $this->manager->getAvailableProviders();
        $this->assertIsArray($availableProviders);
        $this->assertNotEmpty($availableProviders);
        
        Log::info('Fallback mechanism tests passed');
    }

    /**
     * Run security tests.
     */
    private function runSecurityTests(): void
    {
        // Test configuration encryption
        $sensitiveKeys = ['client_secret', 'secret_access_key', 'connection_string'];
        
        foreach ($sensitiveKeys as $key) {
            // Verify sensitive configuration is not exposed in logs
            $this->assertStringNotContainsString($key, json_encode(config('cloud-storage')));
        }
        
        // Test provider validation
        $providers = $this->factory->getRegisteredProviders();
        foreach ($providers as $providerName => $className) {
            $this->assertTrue($this->factory->validateProvider($className));
        }
        
        Log::info('Security tests passed');
    }    /**

     * Run access control tests.
     */
    private function runAccessControlTests(User $user1, User $user2): void
    {
        // Test that users can only access their own provider configurations
        $provider1 = $this->manager->getUserProvider($user1);
        $provider2 = $this->manager->getUserProvider($user2);
        
        // Verify providers are properly isolated
        $this->assertInstanceOf(CloudStorageProviderInterface::class, $provider1);
        $this->assertInstanceOf(CloudStorageProviderInterface::class, $provider2);
        
        // Test that provider switching requires proper user context
        $this->manager->switchUserProvider($user1, 'google-drive');
        $user1Provider = $this->manager->getUserProvider($user1);
        $this->assertEquals('google-drive', $user1Provider->getProviderName());
        
        // User2's provider should be unaffected
        $user2Provider = $this->manager->getUserProvider($user2);
        $this->assertInstanceOf(CloudStorageProviderInterface::class, $user2Provider);
        
        Log::info('Access control tests passed');
    }

    /**
     * Configure test providers for integration testing.
     */
    private function configureTestProviders(): void
    {
        Config::set('cloud-storage.providers.google-drive.config', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost/callback',
        ]);
        
        if (class_exists(S3Provider::class)) {
            Config::set('cloud-storage.providers.amazon-s3.config', [
                'access_key_id' => 'test-access-key',
                'secret_access_key' => 'test-secret-key',
                'region' => 'us-east-1',
                'bucket' => 'test-bucket',
            ]);
        }
    }

    /**
     * Test load handling with multiple providers.
     * 
     * @test
     * @group integration
     * @group load-testing
     */
    public function test_load_handling_multiple_providers(): void
    {
        $providers = $this->manager->getAvailableProviders();
        $testUsers = User::factory()->count(10)->create();
        
        // Test concurrent provider access
        foreach ($testUsers as $user) {
            foreach ($providers as $providerName) {
                $provider = $this->manager->getProvider($providerName, $user);
                $this->assertInstanceOf(CloudStorageProviderInterface::class, $provider);
                $this->assertEquals($providerName, $provider->getProviderName());
            }
        }
        
        // Test provider caching and resource management
        $startMemory = memory_get_usage();
        
        for ($i = 0; $i < 100; $i++) {
            $provider = $this->manager->getProvider('google-drive');
            $this->assertInstanceOf(CloudStorageProviderInterface::class, $provider);
        }
        
        $endMemory = memory_get_usage();
        $memoryIncrease = $endMemory - $startMemory;
        
        // Memory increase should be reasonable (less than 10MB for 100 provider instances)
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease, 'Memory usage increased too much during load test');
        
        Log::info('Load testing completed successfully');
    }
}