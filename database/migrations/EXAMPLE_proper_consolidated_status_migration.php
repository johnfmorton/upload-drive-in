<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Services\CloudStorageHealthService;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This is how the original migration SHOULD have been written
     * to prevent the stale data issue.
     */
    public function up(): void
    {
        // Step 1: Add the schema changes
        Schema::table('cloud_storage_health_statuses', function (Blueprint $table) {
            // Add consolidated status field WITHOUT a default value initially
            $table->enum('consolidated_status', [
                'healthy', 
                'authentication_required', 
                'connection_issues', 
                'not_connected'
            ])->nullable()->after('status');
            
            $table->timestamp('last_token_refresh_attempt_at')->nullable()->after('token_expires_at');
            $table->integer('token_refresh_failures')->default(0)->after('last_token_refresh_attempt_at');
            $table->json('operational_test_result')->nullable()->after('token_refresh_failures');
        });

        // Step 2: Calculate correct consolidated_status for existing records
        $this->calculateConsolidatedStatusForExistingRecords();

        // Step 3: Make the field non-nullable with a default for new records
        Schema::table('cloud_storage_health_statuses', function (Blueprint $table) {
            $table->enum('consolidated_status', [
                'healthy', 
                'authentication_required', 
                'connection_issues', 
                'not_connected'
            ])->default('not_connected')->change();
            
            // Add indexes
            $table->index('consolidated_status', 'csh_consolidated_status_idx');
            $table->index('last_token_refresh_attempt_at', 'csh_last_token_refresh_idx');
        });
    }

    /**
     * Calculate the correct consolidated_status for existing records.
     */
    private function calculateConsolidatedStatusForExistingRecords(): void
    {
        $healthService = app(CloudStorageHealthService::class);
        $records = CloudStorageHealthStatus::whereNull('consolidated_status')->get();
        
        foreach ($records as $record) {
            try {
                $user = User::find($record->user_id);
                if ($user) {
                    $consolidatedStatus = $healthService->determineConsolidatedStatus($user, $record->provider);
                    $record->update(['consolidated_status' => $consolidatedStatus]);
                }
            } catch (\Exception $e) {
                // Fallback to 'not_connected' if calculation fails
                $record->update(['consolidated_status' => 'not_connected']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloud_storage_health_statuses', function (Blueprint $table) {
            $table->dropIndex('csh_consolidated_status_idx');
            $table->dropIndex('csh_last_token_refresh_idx');
            $table->dropColumn([
                'consolidated_status',
                'last_token_refresh_attempt_at', 
                'token_refresh_failures',
                'operational_test_result'
            ]);
        });
    }
};