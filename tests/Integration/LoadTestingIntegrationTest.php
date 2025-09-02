<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\Traits\CloudStorageTestHelpers;

/**
 * Load testing for cloud storage provider system.
 * Tests performance and resource usage under load.
 */
class LoadTestingIntegrationTest extends TestCase
{
    use RefreshDatabase, CloudStorageTestHelpers;

    private CloudStorageManager $manager;
    private CloudStorageFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->manager = app(CloudStorageManager::class);
        $this->factory = app(CloudStorageFactory::class);
        
        $this->configureTestEnvironment();
    }

    /**
     * Test concurrent provider access with multiple users.
     * 
     * @test
     * @group load-testing
     * @group performance
     */
    public function test_concurrent_provider_access_multiple_users(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Create multiple users
        $users = User::factory()->count(50)->create();
        $providers = $this->manager->getAvailableProviders();
        
        $operationCount = 0;
        
        // Test concurrent access
        foreach ($users as $user) {
            foreach ($providers as $providerName) {
                $provider = $this->manager->getProvider($providerName, $user);
                $this->assertInstanceOf(\App\Contracts\CloudStorageProviderInterface::class, $provider);
                
                // Test basic operations
                $capabilities = $provider->getCapabilities();
                $this->assertIsArray($capabilities);
                
                $this->assertTrue($provider->supportsFeature('file_upload'));
                
                $operationCount++;
            }
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;
        
        // Performance assertions
        $this->assertLessThan(30.0, $executionTime, 'Concurrent access took too long');
        $this->assertLessThan(100 * 1024 * 1024, $memoryUsage, 'Memory usage too high');
        
        Log::info("Load test completed: {$operationCount} operations in {$executionTime}s, " . 
                 number_format($memoryUsage / 1024 / 1024, 2) . "MB");
    }

    /**
     * Test provider switching under load.
     * 
     * @test
     * @group load-testing
     * @group provider-switching
     */
    public function test_provider_switching_under_load(): void
    {
        $users = User::factory()->count(20)->create();
        $providers = $this->manager->getAvailableProviders();
        
        if (count($providers) < 2) {
            $this->markTestSkipped('Need at least 2 providers for switching test');
        }
        
        $startTime = microtime(true);
        
        foreach ($users as $user) {
            // Switch between providers multiple times
            foreach ($providers as $providerName) {
                $this->manager->switchUserProvider($user, $providerName);
                $provider = $this->manager->getUserProvider($user);
                $this->assertEquals($providerName, $provider->getProviderName());
            }
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(10.0, $executionTime, 'Provider switching under load took too long');
        
        Log::info("Provider switching load test completed in {$executionTime}s");
    }  
  /**
     * Test memory usage and resource cleanup.
     * 
     * @test
     * @group load-testing
     * @group memory
     */
    public function test_memory_usage_and_resource_cleanup(): void
    {
        $initialMemory = memory_get_usage();
        
        // Create many provider instances
        $providers = [];
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();
            $provider = $this->manager->getUserProvider($user);
            $providers[] = $provider;
        }
        
        $peakMemory = memory_get_usage();
        $memoryIncrease = $peakMemory - $initialMemory;
        
        // Clear references
        unset($providers);
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $finalMemory = memory_get_usage();
        $memoryAfterCleanup = $finalMemory - $initialMemory;
        
        // Memory should be reasonable
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory increase too high');
        $this->assertLessThan($memoryIncrease * 0.8, $memoryAfterCleanup, 'Memory not properly cleaned up');
        
        Log::info("Memory test: Peak increase " . number_format($memoryIncrease / 1024 / 1024, 2) . 
                 "MB, After cleanup " . number_format($memoryAfterCleanup / 1024 / 1024, 2) . "MB");
    }

    /**
     * Test configuration validation under load.
     * 
     * @test
     * @group load-testing
     * @group configuration
     */
    public function test_configuration_validation_under_load(): void
    {
        $startTime = microtime(true);
        
        // Run validation many times
        for ($i = 0; $i < 50; $i++) {
            $validationResults = $this->manager->validateAllProviders();
            $this->assertIsArray($validationResults);
            
            foreach ($validationResults as $provider => $result) {
                $this->assertArrayHasKey('valid', $result);
                $this->assertIsBool($result['valid']);
            }
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(5.0, $executionTime, 'Configuration validation under load took too long');
        
        Log::info("Configuration validation load test completed in {$executionTime}s");
    }

    /**
     * Test provider capability detection under load.
     * 
     * @test
     * @group load-testing
     * @group capabilities
     */
    public function test_capability_detection_under_load(): void
    {
        $providers = $this->manager->getAvailableProviders();
        $startTime = microtime(true);
        
        // Test capability detection many times
        for ($i = 0; $i < 100; $i++) {
            foreach ($providers as $providerName) {
                $capabilities = $this->manager->getProviderCapabilities($providerName);
                $this->assertIsArray($capabilities);
                
                $provider = $this->manager->getProvider($providerName);
                $this->assertTrue($provider->supportsFeature('file_upload'));
            }
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(10.0, $executionTime, 'Capability detection under load took too long');
        
        Log::info("Capability detection load test completed in {$executionTime}s");
    }

    /**
     * Configure test environment for load testing.
     */
    private function configureTestEnvironment(): void
    {
        Config::set('cloud-storage.default', 'google-drive');
        
        Config::set('cloud-storage.providers.google-drive.config', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost/callback',
        ]);
        
        // Increase memory limit for load testing
        ini_set('memory_limit', '512M');
    }

    /**
     * Test error handling under load.
     * 
     * @test
     * @group load-testing
     * @group error-handling
     */
    public function test_error_handling_under_load(): void
    {
        $users = User::factory()->count(10)->create();
        $errorCount = 0;
        $successCount = 0;
        
        foreach ($users as $user) {
            for ($i = 0; $i < 10; $i++) {
                try {
                    // Try to get a non-existent provider
                    $provider = $this->manager->getProvider('non-existent-provider', $user);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    // This is expected
                }
                
                try {
                    // Get valid provider
                    $provider = $this->manager->getUserProvider($user);
                    $this->assertInstanceOf(\App\Contracts\CloudStorageProviderInterface::class, $provider);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->fail('Valid provider access should not fail: ' . $e->getMessage());
                }
            }
        }
        
        // Should handle errors gracefully
        $this->assertGreaterThan(0, $errorCount, 'Should have some expected errors');
        $this->assertGreaterThan(0, $successCount, 'Should have some successes');
        
        Log::info("Error handling load test: {$successCount} successes, {$errorCount} expected errors");
    }
}