<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cloud_storage_health_statuses', function (Blueprint $table) {
            // Add consolidated status field with enum values
            $table->enum('consolidated_status', [
                'healthy', 
                'authentication_required', 
                'connection_issues', 
                'not_connected'
            ])->default('not_connected')->after('status');
            
            // Add timestamp for last token refresh attempt
            $table->timestamp('last_token_refresh_attempt_at')->nullable()->after('token_expires_at');
            
            // Add counter for consecutive token refresh failures
            $table->integer('token_refresh_failures')->default(0)->after('last_token_refresh_attempt_at');
            
            // Add field to store operational test result
            $table->json('operational_test_result')->nullable()->after('token_refresh_failures');
            
            // Add indexes for efficient queries
            $table->index('consolidated_status', 'csh_consolidated_status_idx');
            $table->index('last_token_refresh_attempt_at', 'csh_last_token_refresh_idx');
        });
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
