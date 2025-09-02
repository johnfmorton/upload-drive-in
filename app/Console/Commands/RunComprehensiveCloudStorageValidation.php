<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Command to run comprehensive validation tests for the cloud storage provider system.
 */
class RunComprehensiveCloudStorageValidation extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cloud-storage:validate-comprehensive 
                            {--filter= : Filter tests by name pattern}
                            {--group= : Run specific test group}
                            {--report : Generate detailed report}';

    /**
     * The console command description.
     */
    protected $description = 'Run comprehensive validation tests for cloud storage provider system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting comprehensive cloud storage validation...');
        
        $startTime = microtime(true);
        
        // Prepare test command
        $testCommand = ['test'];
        
        // Add filter if specified
        if ($filter = $this->option('filter')) {
            $testCommand[] = '--filter=' . $filter;
        }
        
        // Add group if specified
        if ($group = $this->option('group')) {
            $testCommand[] = '--group=' . $group;
        } else {
            // Default to comprehensive validation groups
            $testCommand[] = '--group=final-validation,comprehensive-validation,integration';
        }
        
        // Add coverage if report requested
        if ($this->option('report')) {
            $testCommand[] = '--coverage-text';
            $testCommand[] = '--coverage-html=storage/app/test-coverage';
        }
        
        // Run the tests
        $this->info('Running validation tests...');
        $exitCode = Artisan::call('test', [
            '--filter' => $this->option('filter'),
            '--group' => $this->option('group') ?: 'final-validation,comprehensive-validation',
            '--stop-on-failure' => false,
        ]);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        if ($exitCode === 0) {
            $this->info("✅ Comprehensive validation completed successfully in {$duration}s");
            $this->runAdditionalValidations();
        } else {
            $this->error("❌ Comprehensive validation failed in {$duration}s");
            $this->displayFailureGuidance();
        }
        
        if ($this->option('report')) {
            $this->generateDetailedReport();
        }
        
        return $exitCode;
    }

    /**
     * Run additional validations beyond PHPUnit tests.
     */
    private function runAdditionalValidations(): void
    {
        $this->info('Running additional system validations...');
        
        // Validate provider registration
        $this->validateProviderRegistration();
        
        // Validate configuration
        $this->validateConfiguration();
        
        // Validate backward compatibility
        $this->validateBackwardCompatibility();
        
        // Validate security
        $this->validateSecurity();
        
        $this->info('✅ Additional validations completed');
    }

    /**
     * Validate provider registration.
     */
    private function validateProviderRegistration(): void
    {
        try {
            $factory = app(\App\Services\CloudStorageFactory::class);
            $providers = $factory->getRegisteredProviders();
            
            $this->line("Registered providers: " . implode(', ', array_keys($providers)));
            
            foreach ($providers as $name => $class) {
                if (!class_exists($class)) {
                    $this->error("❌ Provider class {$class} does not exist");
                    continue;
                }
                
                if (!$factory->validateProvider($class)) {
                    $this->error("❌ Provider {$name} failed validation");
                    continue;
                }
                
                $this->line("✅ Provider {$name} validated");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Provider registration validation failed: " . $e->getMessage());
        }
    }

    /**
     * Validate configuration.
     */
    private function validateConfiguration(): void
    {
        try {
            $manager = app(\App\Services\CloudStorageManager::class);
            $validationResults = $manager->validateAllProviders();
            
            foreach ($validationResults as $provider => $result) {
                if ($result['valid']) {
                    $this->line("✅ Configuration for {$provider} is valid");
                } else {
                    $this->error("❌ Configuration for {$provider} is invalid: " . implode(', ', $result['errors']));
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Configuration validation failed: " . $e->getMessage());
        }
    }

    /**
     * Validate backward compatibility.
     */
    private function validateBackwardCompatibility(): void
    {
        try {
            // Test that old service still exists
            $googleDriveService = app(\App\Services\GoogleDriveService::class);
            $this->line("✅ GoogleDriveService backward compatibility maintained");
            
            // Test that old job still exists
            if (class_exists(\App\Jobs\UploadToGoogleDrive::class)) {
                $this->line("✅ UploadToGoogleDrive job backward compatibility maintained");
            }
            
            // Test that old controllers still work
            if (class_exists(\App\Http\Controllers\Admin\CloudStorageController::class)) {
                $this->line("✅ Admin CloudStorageController backward compatibility maintained");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Backward compatibility validation failed: " . $e->getMessage());
        }
    }

    /**
     * Validate security.
     */
    private function validateSecurity(): void
    {
        try {
            // Check configuration security
            $config = config('cloud-storage');
            $configString = json_encode($config);
            
            $sensitivePatterns = ['password', 'secret', 'key', 'token'];
            $exposedSecrets = [];
            
            foreach ($sensitivePatterns as $pattern) {
                if (preg_match("/\"{$pattern}\":\s*\"[^\"]+\"/i", $configString)) {
                    $exposedSecrets[] = $pattern;
                }
            }
            
            if (empty($exposedSecrets)) {
                $this->line("✅ No sensitive data exposed in configuration");
            } else {
                $this->error("❌ Potentially sensitive data exposed: " . implode(', ', $exposedSecrets));
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Security validation failed: " . $e->getMessage());
        }
    }    /**

     * Display failure guidance.
     */
    private function displayFailureGuidance(): void
    {
        $this->error('Comprehensive validation failed. Please check the following:');
        $this->line('');
        $this->line('1. Ensure all required services are properly registered');
        $this->line('2. Verify configuration is complete and valid');
        $this->line('3. Check that all provider classes implement the correct interfaces');
        $this->line('4. Ensure backward compatibility is maintained');
        $this->line('5. Verify security requirements are met');
        $this->line('');
        $this->line('For detailed troubleshooting, see:');
        $this->line('- docs/troubleshooting/cloud-storage-provider-troubleshooting.md');
        $this->line('- docs/testing/cloud-storage-provider-testing-guide.md');
    }

    /**
     * Generate detailed report.
     */
    private function generateDetailedReport(): void
    {
        $this->info('Generating detailed validation report...');
        
        $report = [
            'timestamp' => now()->toISOString(),
            'validation_type' => 'comprehensive',
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
            ],
            'providers' => [],
            'configuration' => [],
            'security' => [],
            'performance' => [],
        ];
        
        // Gather provider information
        try {
            $factory = app(\App\Services\CloudStorageFactory::class);
            $providers = $factory->getRegisteredProviders();
            
            foreach ($providers as $name => $class) {
                $report['providers'][$name] = [
                    'class' => $class,
                    'exists' => class_exists($class),
                    'valid' => $factory->validateProvider($class),
                ];
            }
        } catch (\Exception $e) {
            $report['providers']['error'] = $e->getMessage();
        }
        
        // Gather configuration information
        try {
            $manager = app(\App\Services\CloudStorageManager::class);
            $report['configuration'] = $manager->validateAllProviders();
        } catch (\Exception $e) {
            $report['configuration']['error'] = $e->getMessage();
        }
        
        // Save report
        $reportPath = storage_path('logs/comprehensive-validation-' . date('Y-m-d-H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("Detailed report saved to: {$reportPath}");
        
        // Display summary
        $this->displayReportSummary($report);
    }

    /**
     * Display report summary.
     */
    private function displayReportSummary(array $report): void
    {
        $this->line('');
        $this->line('=== VALIDATION REPORT SUMMARY ===');
        
        // Provider summary
        if (isset($report['providers']) && is_array($report['providers'])) {
            $totalProviders = count($report['providers']);
            $validProviders = 0;
            
            foreach ($report['providers'] as $provider) {
                if (isset($provider['valid']) && $provider['valid']) {
                    $validProviders++;
                }
            }
            
            $this->line("Providers: {$validProviders}/{$totalProviders} valid");
        }
        
        // Configuration summary
        if (isset($report['configuration']) && is_array($report['configuration'])) {
            $totalConfigs = count($report['configuration']);
            $validConfigs = 0;
            
            foreach ($report['configuration'] as $config) {
                if (isset($config['valid']) && $config['valid']) {
                    $validConfigs++;
                }
            }
            
            $this->line("Configurations: {$validConfigs}/{$totalConfigs} valid");
        }
        
        $this->line('================================');
    }
}