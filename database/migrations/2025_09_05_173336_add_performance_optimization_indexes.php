<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance optimization indexes for token expiration lookups and health status queries.
     */
    public function up(): void
    {
        // Add indexes for google_drive_tokens table
        $this->addGoogleDriveTokenIndexes();
        
        // Add indexes for cloud_storage_health_statuses table
        $this->addCloudStorageHealthStatusIndexes();
        
        Log::info('Performance optimization indexes added successfully');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop google_drive_tokens indexes
        $this->dropGoogleDriveTokenIndexes();
        
        // Drop cloud_storage_health_statuses indexes
        $this->dropCloudStorageHealthStatusIndexes();
        
        Log::info('Performance optimization indexes removed successfully');
    }

    /**
     * Add performance indexes for google_drive_tokens table.
     */
    private function addGoogleDriveTokenIndexes(): void
    {
        try {
            // Index for expiring tokens query optimization
            // Covers: expires_at, refresh_failure_count, requires_user_intervention
            if (!$this->indexExists('google_drive_tokens', 'idx_expires_at_refresh_failures')) {
                DB::statement('
                    CREATE INDEX idx_expires_at_refresh_failures 
                    ON google_drive_tokens (expires_at, refresh_failure_count, requires_user_intervention)
                ');
                Log::info('Created index: idx_expires_at_refresh_failures');
            }

            // Index for user-specific token lookups
            // Covers: user_id, expires_at
            if (!$this->indexExists('google_drive_tokens', 'idx_user_id_expires_at')) {
                DB::statement('
                    CREATE INDEX idx_user_id_expires_at 
                    ON google_drive_tokens (user_id, expires_at)
                ');
                Log::info('Created index: idx_user_id_expires_at');
            }

            // Index for proactive refresh scheduling
            // Covers: proactive_refresh_scheduled_at
            if (!$this->indexExists('google_drive_tokens', 'idx_proactive_refresh_scheduled')) {
                DB::statement('
                    CREATE INDEX idx_proactive_refresh_scheduled 
                    ON google_drive_tokens (proactive_refresh_scheduled_at)
                ');
                Log::info('Created index: idx_proactive_refresh_scheduled');
            }

            // Index for refresh failure tracking
            // Covers: refresh_failure_count, last_refresh_attempt_at
            if (!$this->indexExists('google_drive_tokens', 'idx_refresh_failures_attempt')) {
                DB::statement('
                    CREATE INDEX idx_refresh_failures_attempt 
                    ON google_drive_tokens (refresh_failure_count, last_refresh_attempt_at)
                ');
                Log::info('Created index: idx_refresh_failures_attempt');
            }

            // Index for user intervention requirements
            // Covers: requires_user_intervention, refresh_failure_count
            if (!$this->indexExists('google_drive_tokens', 'idx_user_intervention_failures')) {
                DB::statement('
                    CREATE INDEX idx_user_intervention_failures 
                    ON google_drive_tokens (requires_user_intervention, refresh_failure_count)
                ');
                Log::info('Created index: idx_user_intervention_failures');
            }

        } catch (\Exception $e) {
            Log::error('Failed to create google_drive_tokens indexes', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add performance indexes for cloud_storage_health_statuses table.
     */
    private function addCloudStorageHealthStatusIndexes(): void
    {
        try {
            // Index for health validation queries
            // Covers: provider, last_live_validation_at, consecutive_failures
            if (!$this->indexExists('cloud_storage_health_statuses', 'idx_provider_validation_failures')) {
                DB::statement('
                    CREATE INDEX idx_provider_validation_failures 
                    ON cloud_storage_health_statuses (provider, last_live_validation_at, consecutive_failures)
                ');
                Log::info('Created index: idx_provider_validation_failures');
            }

            // Index for user-provider status lookups
            // Covers: user_id, provider, consolidated_status
            if (!$this->indexExists('cloud_storage_health_statuses', 'idx_user_provider_status')) {
                DB::statement('
                    CREATE INDEX idx_user_provider_status 
                    ON cloud_storage_health_statuses (user_id, provider, consolidated_status)
                ');
                Log::info('Created index: idx_user_provider_status');
            }

            // Index for successful operation tracking
            // Covers: last_successful_operation_at
            if (!$this->indexExists('cloud_storage_health_statuses', 'idx_last_successful_operation')) {
                DB::statement('
                    CREATE INDEX idx_last_successful_operation 
                    ON cloud_storage_health_statuses (last_successful_operation_at)
                ');
                Log::info('Created index: idx_last_successful_operation');
            }

            // Index for API connectivity testing
            // Covers: api_connectivity_last_tested_at, provider
            if (!$this->indexExists('cloud_storage_health_statuses', 'idx_api_connectivity_tested')) {
                DB::statement('
                    CREATE INDEX idx_api_connectivity_tested 
                    ON cloud_storage_health_statuses (api_connectivity_last_tested_at, provider)
                ');
                Log::info('Created index: idx_api_connectivity_tested');
            }

            // Index for token refresh tracking
            // Covers: last_token_refresh_attempt_at, token_refresh_failures
            if (!$this->indexExists('cloud_storage_health_statuses', 'idx_token_refresh_tracking')) {
                DB::statement('
                    CREATE INDEX idx_token_refresh_tracking 
                    ON cloud_storage_health_statuses (last_token_refresh_attempt_at, token_refresh_failures)
                ');
                Log::info('Created index: idx_token_refresh_tracking');
            }

            // Index for consolidated status queries
            // Covers: consolidated_status, provider, last_live_validation_at
            if (!$this->indexExists('cloud_storage_health_statuses', 'idx_consolidated_status_validation')) {
                DB::statement('
                    CREATE INDEX idx_consolidated_status_validation 
                    ON cloud_storage_health_statuses (consolidated_status, provider, last_live_validation_at)
                ');
                Log::info('Created index: idx_consolidated_status_validation');
            }

        } catch (\Exception $e) {
            Log::error('Failed to create cloud_storage_health_statuses indexes', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Drop google_drive_tokens indexes.
     */
    private function dropGoogleDriveTokenIndexes(): void
    {
        $indexes = [
            'idx_expires_at_refresh_failures',
            'idx_user_id_expires_at',
            'idx_proactive_refresh_scheduled',
            'idx_refresh_failures_attempt',
            'idx_user_intervention_failures',
        ];

        foreach ($indexes as $indexName) {
            try {
                if ($this->indexExists('google_drive_tokens', $indexName)) {
                    DB::statement("DROP INDEX {$indexName} ON google_drive_tokens");
                    Log::info("Dropped index: {$indexName}");
                }
            } catch (\Exception $e) {
                Log::warning("Failed to drop index {$indexName}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Drop cloud_storage_health_statuses indexes.
     */
    private function dropCloudStorageHealthStatusIndexes(): void
    {
        $indexes = [
            'idx_provider_validation_failures',
            'idx_user_provider_status',
            'idx_last_successful_operation',
            'idx_api_connectivity_tested',
            'idx_token_refresh_tracking',
            'idx_consolidated_status_validation',
        ];

        foreach ($indexes as $indexName) {
            try {
                if ($this->indexExists('cloud_storage_health_statuses', $indexName)) {
                    DB::statement("DROP INDEX {$indexName} ON cloud_storage_health_statuses");
                    Log::info("Dropped index: {$indexName}");
                }
            } catch (\Exception $e) {
                Log::warning("Failed to drop index {$indexName}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Exception $e) {
            Log::warning("Failed to check if index exists", [
                'table' => $table,
                'index' => $indexName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
};