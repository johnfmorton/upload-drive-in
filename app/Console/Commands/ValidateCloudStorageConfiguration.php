<?php

namespace App\Console\Commands;

use App\Services\CloudStorageConfigurationValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ValidateCloudStorageConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cloud-storage:validate-config 
                            {--provider= : Validate specific provider only}
                            {--json : Output results in JSON format}
                            {--log : Log validation results}
                            {--fix : Attempt to fix common configuration issues}';

    /**
     * The console command description.
     */
    protected $description = 'Validate cloud storage provider configurations';

    public function __construct(
        private readonly CloudStorageConfigurationValidationService $validationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $provider = $this->option('provider');
        $jsonOutput = $this->option('json');
        $shouldLog = $this->option('log');
        $shouldFix = $this->option('fix');

        if (!$jsonOutput) {
            $this->info('🔍 Cloud Storage Configuration Validation');
            $this->info('==========================================');
            $this->newLine();
        }

        try {
            if ($provider) {
                $results = $this->validateSingleProvider($provider, $jsonOutput, $shouldFix);
            } else {
                $results = $this->validateAllProviders($jsonOutput, $shouldFix);
            }

            if ($shouldLog) {
                $this->logResults($results);
            }

            if ($jsonOutput) {
                $this->line(json_encode($results, JSON_PRETTY_PRINT));
            }

            // Return appropriate exit code
            if ($provider) {
                return $results['is_valid'] ? self::SUCCESS : self::FAILURE;
            } else {
                return $results['summary']['invalid_count'] === 0 ? self::SUCCESS : self::FAILURE;
            }

        } catch (\Exception $e) {
            if ($jsonOutput) {
                $this->line(json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error('❌ Validation failed: ' . $e->getMessage());
            }

            Log::error('Configuration validation command failed', [
                'error' => $e->getMessage(),
                'provider' => $provider,
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Validate all providers
     */
    private function validateAllProviders(bool $jsonOutput, bool $shouldFix): array
    {
        $results = $this->validationService->validateAllProviderConfigurations();

        if (!$jsonOutput) {
            $this->displayAllProvidersResults($results, $shouldFix);
        }

        return $results;
    }

    /**
     * Validate a single provider
     */
    private function validateSingleProvider(string $provider, bool $jsonOutput, bool $shouldFix): array
    {
        $results = $this->validationService->validateProviderConfiguration($provider);

        if (!$jsonOutput) {
            $this->displaySingleProviderResults($results, $shouldFix);
        }

        return $results;
    }

    /**
     * Display results for all providers
     */
    private function displayAllProvidersResults(array $results, bool $shouldFix): void
    {
        $summary = $results['summary'];

        // Display summary
        $this->info("📊 Validation Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Providers', $summary['total_providers']],
                ['Valid Providers', $summary['valid_count']],
                ['Invalid Providers', $summary['invalid_count']],
                ['Providers with Warnings', $summary['warning_count']],
            ]
        );
        $this->newLine();

        // Display valid providers
        if (!empty($results['valid'])) {
            $this->info('✅ Valid Providers:');
            foreach ($results['valid'] as $provider => $validation) {
                $this->line("  • {$provider}");
                if (!empty($validation['warnings'])) {
                    foreach ($validation['warnings'] as $warning) {
                        $this->warn("    ⚠️  {$warning}");
                    }
                }
            }
            $this->newLine();
        }

        // Display invalid providers
        if (!empty($results['invalid'])) {
            $this->error('❌ Invalid Providers:');
            foreach ($results['invalid'] as $provider => $validation) {
                $this->line("  • {$provider}");
                foreach ($validation['errors'] as $error) {
                    $this->error("    ✗ {$error}");
                }
                if ($shouldFix) {
                    $this->attemptToFixProvider($provider, $validation);
                }
            }
            $this->newLine();
        }

        // Display recommendations
        if ($summary['invalid_count'] > 0) {
            $this->warn('💡 Recommendations:');
            $this->line('  • Review the configuration errors above');
            $this->line('  • Check environment variables and config files');
            $this->line('  • Use --fix option to attempt automatic fixes');
            $this->line('  • Run cloud-storage:migrate-config to migrate legacy settings');
        }
    }

    /**
     * Display results for a single provider
     */
    private function displaySingleProviderResults(array $results, bool $shouldFix): void
    {
        $provider = $results['provider_name'];
        
        $this->info("🔍 Validating Provider: {$provider}");
        $this->newLine();

        // Status
        $status = $results['is_valid'] ? '✅ Valid' : '❌ Invalid';
        $this->line("Status: {$status}");
        $this->newLine();

        // Configuration sources
        if (!empty($results['config_sources'])) {
            $this->info('📋 Configuration Sources:');
            $this->table(
                ['Key', 'Source'],
                collect($results['config_sources'])->map(function ($source, $key) {
                    return [$key, $source];
                })->toArray()
            );
            $this->newLine();
        }

        // Provider class validation
        $this->info('🔧 Provider Class Validation:');
        $this->line('  Class Valid: ' . ($results['provider_class_valid'] ? '✅' : '❌'));
        $this->line('  Interface Compliance: ' . ($results['interface_compliance'] ? '✅' : '❌'));
        $this->newLine();

        // Errors
        if (!empty($results['errors'])) {
            $this->error('❌ Configuration Errors:');
            foreach ($results['errors'] as $error) {
                $this->line("  • {$error}");
            }
            $this->newLine();

            if ($shouldFix) {
                $this->attemptToFixProvider($provider, $results);
            }
        }

        // Warnings
        if (!empty($results['warnings'])) {
            $this->warn('⚠️  Configuration Warnings:');
            foreach ($results['warnings'] as $warning) {
                $this->line("  • {$warning}");
            }
            $this->newLine();
        }

        // Recommendations
        if (!$results['is_valid']) {
            $this->warn('💡 Recommendations:');
            $this->line('  • Check the errors listed above');
            $this->line('  • Verify environment variables are set correctly');
            $this->line('  • Ensure provider class exists and implements the interface');
            if ($shouldFix) {
                $this->line('  • Automatic fixes were attempted (see above)');
            }
        }
    }

    /**
     * Attempt to fix common configuration issues
     */
    private function attemptToFixProvider(string $provider, array $validation): void
    {
        $this->warn("🔧 Attempting to fix issues for {$provider}...");

        $fixed = [];
        $failed = [];

        foreach ($validation['errors'] as $error) {
            try {
                if ($this->attemptToFixError($provider, $error)) {
                    $fixed[] = $error;
                } else {
                    $failed[] = $error;
                }
            } catch (\Exception $e) {
                $failed[] = $error . " (Fix failed: {$e->getMessage()})";
            }
        }

        if (!empty($fixed)) {
            $this->info('  ✅ Fixed issues:');
            foreach ($fixed as $fix) {
                $this->line("    • {$fix}");
            }
        }

        if (!empty($failed)) {
            $this->error('  ❌ Could not fix:');
            foreach ($failed as $fail) {
                $this->line("    • {$fail}");
            }
        }

        $this->newLine();
    }

    /**
     * Attempt to fix a specific error
     */
    private function attemptToFixError(string $provider, string $error): bool
    {
        // For now, we'll just provide guidance rather than making actual changes
        // In the future, this could attempt to migrate configurations, set defaults, etc.
        
        if (str_contains($error, 'client ID is required')) {
            $this->line("    💡 Set the client ID environment variable for {$provider}");
            return false; // We don't actually fix it, just provide guidance
        }

        if (str_contains($error, 'client secret is required')) {
            $this->line("    💡 Set the client secret environment variable for {$provider}");
            return false;
        }

        if (str_contains($error, 'Failed to instantiate provider')) {
            $this->line("    💡 Check that the provider class exists and dependencies are available");
            return false;
        }

        return false;
    }

    /**
     * Log validation results
     */
    private function logResults(array $results): void
    {
        if (isset($results['summary'])) {
            // All providers validation
            Log::info('Cloud storage configuration validation completed', [
                'total_providers' => $results['summary']['total_providers'],
                'valid_count' => $results['summary']['valid_count'],
                'invalid_count' => $results['summary']['invalid_count'],
                'warning_count' => $results['summary']['warning_count'],
                'valid_providers' => array_keys($results['valid'] ?? []),
                'invalid_providers' => array_keys($results['invalid'] ?? []),
            ]);

            // Log specific errors
            foreach ($results['invalid'] ?? [] as $provider => $validation) {
                Log::error("Provider '{$provider}' configuration is invalid", [
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'],
                ]);
            }
        } else {
            // Single provider validation
            Log::info("Cloud storage provider '{$results['provider_name']}' validation completed", [
                'is_valid' => $results['is_valid'],
                'errors' => $results['errors'],
                'warnings' => $results['warnings'],
            ]);
        }
    }
}