<?php

namespace App\Console\Commands;

use App\Services\CloudStorageConfigMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrateCloudStorageConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-storage:migrate-config 
                            {--backup : Create a backup of current configuration}
                            {--validate : Only validate configuration without migrating}
                            {--force : Force migration even if already migrated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate cloud storage configuration from legacy format to enhanced provider system';

    /**
     * The migration service instance.
     */
    private CloudStorageConfigMigrationService $migrationService;

    /**
     * Create a new command instance.
     */
    public function __construct(CloudStorageConfigMigrationService $migrationService)
    {
        parent::__construct();
        $this->migrationService = $migrationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cloud Storage Configuration Migration Tool');
        $this->line('==========================================');

        try {
            // Create backup if requested
            if ($this->option('backup')) {
                $this->info('Creating configuration backup...');
                $backupPath = $this->migrationService->createConfigurationBackup();
                $this->info("Backup created: {$backupPath}");
                $this->line('');
            }

            // Validate configuration
            if ($this->option('validate')) {
                return $this->validateConfiguration();
            }

            // Perform migration
            return $this->performMigration();

        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            Log::error('Cloud storage configuration migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Validate the current configuration.
     */
    private function validateConfiguration(): int
    {
        $this->info('Validating provider configurations...');
        
        $results = $this->migrationService->validateProviderConfigurations();
        
        // Display valid providers
        if (!empty($results['valid'])) {
            $this->info('✅ Valid Providers:');
            foreach ($results['valid'] as $result) {
                $this->line("  • {$result['provider']}: {$result['message']}");
            }
            $this->line('');
        }

        // Display warnings
        if (!empty($results['warnings'])) {
            $this->warn('⚠️  Warnings:');
            foreach ($results['warnings'] as $result) {
                $this->line("  • {$result['provider']}: {$result['message']}");
            }
            $this->line('');
        }

        // Display invalid providers
        if (!empty($results['invalid'])) {
            $this->error('❌ Invalid Providers:');
            foreach ($results['invalid'] as $result) {
                $this->line("  • {$result['provider']}:");
                foreach ($result['errors'] as $error) {
                    $this->line("    - {$error}");
                }
            }
            $this->line('');
            return Command::FAILURE;
        }

        $this->info('All provider configurations are valid! ✨');
        return Command::SUCCESS;
    }

    /**
     * Perform the migration process.
     */
    private function performMigration(): int
    {
        $hasErrors = false;

        // Migrate legacy Google Drive settings
        $this->info('Migrating legacy Google Drive settings...');
        $googleDriveResults = $this->migrationService->migrateLegacyGoogleDriveSettings();
        $this->displayMigrationResults('Google Drive Settings', $googleDriveResults);
        
        if (!empty($googleDriveResults['errors'])) {
            $hasErrors = true;
        }

        $this->line('');

        // Migrate configuration format
        $this->info('Migrating configuration format...');
        $configResults = $this->migrationService->migrateConfigurationFormat();
        $this->displayMigrationResults('Configuration Format', $configResults);
        
        if (!empty($configResults['errors'])) {
            $hasErrors = true;
        }

        $this->line('');

        // Validate final configuration
        $this->info('Validating migrated configuration...');
        $validationResults = $this->migrationService->validateProviderConfigurations();
        
        if (!empty($validationResults['invalid'])) {
            $this->error('❌ Configuration validation failed after migration:');
            foreach ($validationResults['invalid'] as $result) {
                $this->line("  • {$result['provider']}:");
                foreach ($result['errors'] as $error) {
                    $this->line("    - {$error}");
                }
            }
            $hasErrors = true;
        } else {
            $this->info('✅ Configuration validation passed!');
        }

        if ($hasErrors) {
            $this->error('Migration completed with errors. Please review the output above.');
            return Command::FAILURE;
        }

        $this->info('Migration completed successfully! ✨');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Review your .env file and update any provider-specific settings');
        $this->line('2. Test your cloud storage providers using: php artisan cloud-storage:test');
        $this->line('3. Update any custom code that directly uses the old configuration format');

        return Command::SUCCESS;
    }

    /**
     * Display migration results in a formatted way.
     */
    private function displayMigrationResults(string $section, array $results): void
    {
        // Display migrated items
        if (!empty($results['migrated'])) {
            $this->info("✅ {$section} - Migrated:");
            foreach ($results['migrated'] as $result) {
                if (isset($result['env_key'])) {
                    $this->line("  • {$result['env_key']} → {$result['config_path']}");
                } elseif (isset($result['provider'])) {
                    $this->line("  • {$result['provider']}: {$result['action']}");
                }
            }
        }

        // Display skipped items
        if (!empty($results['skipped'])) {
            $this->warn("⏭️  {$section} - Skipped:");
            foreach ($results['skipped'] as $result) {
                if (isset($result['env_key'])) {
                    $this->line("  • {$result['env_key']}: {$result['reason']}");
                } else {
                    $this->line("  • {$result['reason']}");
                }
            }
        }

        // Display errors
        if (!empty($results['errors'])) {
            $this->error("❌ {$section} - Errors:");
            foreach ($results['errors'] as $result) {
                if (isset($result['env_key'])) {
                    $this->line("  • {$result['env_key']}: {$result['error']}");
                } elseif (isset($result['provider'])) {
                    $this->line("  • {$result['provider']}: {$result['error']}");
                } else {
                    $this->line("  • {$result['general']}");
                }
            }
        }

        // Show summary
        $total = count($results['migrated']) + count($results['skipped']) + count($results['errors']);
        if ($total > 0) {
            $migrated = count($results['migrated']);
            $skipped = count($results['skipped']);
            $errors = count($results['errors']);
            $this->line("  Summary: {$migrated} migrated, {$skipped} skipped, {$errors} errors");
        }
    }
}