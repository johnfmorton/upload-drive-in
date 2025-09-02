<?php

namespace App\Console\Commands;

use App\Services\CloudStorageManager;
use App\Services\CloudConfigurationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCloudStorageProviders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-storage:test 
                            {provider? : Specific provider to test}
                            {--all : Test all configured providers}
                            {--enabled-only : Only test enabled providers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test cloud storage provider configurations and connectivity';

    /**
     * The cloud storage manager instance.
     */
    private CloudStorageManager $storageManager;

    /**
     * The configuration service instance.
     */
    private CloudConfigurationService $configService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        CloudStorageManager $storageManager,
        CloudConfigurationService $configService
    ) {
        parent::__construct();
        $this->storageManager = $storageManager;
        $this->configService = $configService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cloud Storage Provider Testing Tool');
        $this->line('====================================');

        try {
            $provider = $this->argument('provider');
            $testAll = $this->option('all');
            $enabledOnly = $this->option('enabled-only');

            if ($provider) {
                return $this->testSpecificProvider($provider);
            } elseif ($testAll) {
                return $this->testAllProviders($enabledOnly);
            } else {
                return $this->testDefaultProvider();
            }

        } catch (\Exception $e) {
            $this->error('Testing failed: ' . $e->getMessage());
            Log::error('Cloud storage provider testing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Test a specific provider.
     */
    private function testSpecificProvider(string $providerName): int
    {
        $this->info("Testing provider: {$providerName}");
        $this->line('');

        $result = $this->testProvider($providerName);
        
        if ($result['success']) {
            $this->info("✅ Provider '{$providerName}' test passed!");
            return Command::SUCCESS;
        } else {
            $this->error("❌ Provider '{$providerName}' test failed!");
            return Command::FAILURE;
        }
    }

    /**
     * Test all providers.
     */
    private function testAllProviders(bool $enabledOnly = false): int
    {
        $this->info('Testing all providers...');
        $this->line('');

        $providers = config('cloud-storage.providers', []);
        $results = [];
        $hasFailures = false;

        foreach ($providers as $providerName => $providerConfig) {
            if ($enabledOnly && !($providerConfig['enabled'] ?? true)) {
                $this->warn("⏭️  Skipping disabled provider: {$providerName}");
                continue;
            }

            $this->info("Testing {$providerName}...");
            $result = $this->testProvider($providerName);
            $results[$providerName] = $result;

            if ($result['success']) {
                $this->info("  ✅ Passed");
            } else {
                $this->error("  ❌ Failed");
                $hasFailures = true;
            }
            $this->line('');
        }

        // Display summary
        $this->displayTestSummary($results);

        return $hasFailures ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Test the default provider.
     */
    private function testDefaultProvider(): int
    {
        $defaultProvider = config('cloud-storage.default');
        $this->info("Testing default provider: {$defaultProvider}");
        $this->line('');

        return $this->testSpecificProvider($defaultProvider);
    }

    /**
     * Test a single provider.
     */
    private function testProvider(string $providerName): array
    {
        $result = [
            'success' => false,
            'tests' => [],
            'errors' => [],
        ];

        try {
            // Test 1: Configuration validation
            $this->line("  🔍 Testing configuration...");
            $configTest = $this->testProviderConfiguration($providerName);
            $result['tests']['configuration'] = $configTest;
            
            if (!$configTest['success']) {
                $result['errors'] = array_merge($result['errors'], $configTest['errors']);
                return $result;
            }
            $this->line("    ✅ Configuration valid");

            // Test 2: Provider instantiation
            $this->line("  🔍 Testing provider instantiation...");
            $instantiationTest = $this->testProviderInstantiation($providerName);
            $result['tests']['instantiation'] = $instantiationTest;
            
            if (!$instantiationTest['success']) {
                $result['errors'] = array_merge($result['errors'], $instantiationTest['errors']);
                return $result;
            }
            $this->line("    ✅ Provider instantiated successfully");

            // Test 3: Capability detection
            $this->line("  🔍 Testing capability detection...");
            $capabilityTest = $this->testProviderCapabilities($providerName);
            $result['tests']['capabilities'] = $capabilityTest;
            
            if ($capabilityTest['success']) {
                $this->line("    ✅ Capabilities detected");
                $this->displayCapabilities($capabilityTest['capabilities']);
            } else {
                $this->warn("    ⚠️  Capability detection failed (non-critical)");
            }

            // Test 4: Feature support
            $this->line("  🔍 Testing feature support...");
            $featureTest = $this->testProviderFeatures($providerName);
            $result['tests']['features'] = $featureTest;
            
            if ($featureTest['success']) {
                $this->line("    ✅ Feature support verified");
                $this->displayFeatures($featureTest['features']);
            } else {
                $this->warn("    ⚠️  Feature support test failed (non-critical)");
            }

            $result['success'] = true;

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            $this->error("    ❌ Exception: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Test provider configuration.
     */
    private function testProviderConfiguration(string $providerName): array
    {
        try {
            $config = $this->configService->getProviderConfig($providerName);
            $validation = $this->configService->validateProviderConfig($providerName, $config);

            return [
                'success' => empty($validation),
                'errors' => $validation,
                'config' => $config,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Test provider instantiation.
     */
    private function testProviderInstantiation(string $providerName): array
    {
        try {
            $provider = $this->storageManager->getProvider($providerName);
            
            return [
                'success' => true,
                'provider' => $provider,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Test provider capabilities.
     */
    private function testProviderCapabilities(string $providerName): array
    {
        try {
            $provider = $this->storageManager->getProvider($providerName);
            $capabilities = $provider->getCapabilities();

            return [
                'success' => is_array($capabilities),
                'capabilities' => $capabilities,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Test provider features.
     */
    private function testProviderFeatures(string $providerName): array
    {
        try {
            $provider = $this->storageManager->getProvider($providerName);
            $config = config("cloud-storage.providers.{$providerName}.features", []);
            
            $features = [];
            foreach ($config as $feature => $supported) {
                $features[$feature] = [
                    'configured' => $supported,
                    'detected' => $provider->supportsFeature($feature),
                ];
            }

            return [
                'success' => true,
                'features' => $features,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Display provider capabilities.
     */
    private function displayCapabilities(array $capabilities): void
    {
        if (empty($capabilities)) {
            $this->line("      No capabilities reported");
            return;
        }

        foreach ($capabilities as $capability => $value) {
            if (is_bool($value)) {
                $status = $value ? '✅' : '❌';
                $this->line("      {$status} {$capability}");
            } else {
                $this->line("      • {$capability}: {$value}");
            }
        }
    }

    /**
     * Display provider features.
     */
    private function displayFeatures(array $features): void
    {
        if (empty($features)) {
            $this->line("      No features configured");
            return;
        }

        foreach ($features as $feature => $status) {
            $configured = $status['configured'] ? '✅' : '❌';
            $detected = $status['detected'] ? '✅' : '❌';
            $match = $status['configured'] === $status['detected'] ? '✅' : '⚠️';
            
            $this->line("      {$match} {$feature}: Config({$configured}) Detected({$detected})");
        }
    }

    /**
     * Display test summary.
     */
    private function displayTestSummary(array $results): void
    {
        $this->line('');
        $this->info('Test Summary');
        $this->line('============');

        $passed = 0;
        $failed = 0;

        foreach ($results as $providerName => $result) {
            if ($result['success']) {
                $this->info("✅ {$providerName}: PASSED");
                $passed++;
            } else {
                $this->error("❌ {$providerName}: FAILED");
                foreach ($result['errors'] as $error) {
                    $this->line("   - {$error}");
                }
                $failed++;
            }
        }

        $this->line('');
        $total = $passed + $failed;
        $this->info("Total: {$total}, Passed: {$passed}, Failed: {$failed}");

        if ($failed === 0) {
            $this->info('All tests passed! 🎉');
        } else {
            $this->warn("Some tests failed. Please review the configuration for failed providers.");
        }
    }
}