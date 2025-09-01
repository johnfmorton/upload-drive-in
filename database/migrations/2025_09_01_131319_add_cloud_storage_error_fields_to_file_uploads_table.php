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
        Schema::table('file_uploads', function (Blueprint $table) {
            // Add cloud storage provider field for multi-provider support
            $table->string('cloud_storage_provider')->nullable()->after('storage_provider');
            
            // Add cloud storage error tracking fields
            $table->string('cloud_storage_error_type')->nullable()->after('last_error');
            $table->json('cloud_storage_error_context')->nullable()->after('cloud_storage_error_type');
            $table->timestamp('connection_health_at_failure')->nullable()->after('cloud_storage_error_context');
            $table->timestamp('retry_recommended_at')->nullable()->after('connection_health_at_failure');
            
            // Add indexes for efficient querying of error states
            $table->index('cloud_storage_error_type', 'idx_file_uploads_error_type');
            $table->index(['cloud_storage_error_type', 'created_at'], 'idx_file_uploads_error_type_created');
            $table->index('connection_health_at_failure', 'idx_file_uploads_connection_health');
            $table->index('retry_recommended_at', 'idx_file_uploads_retry_recommended');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_file_uploads_error_type');
            $table->dropIndex('idx_file_uploads_error_type_created');
            $table->dropIndex('idx_file_uploads_connection_health');
            $table->dropIndex('idx_file_uploads_retry_recommended');
            
            // Drop cloud storage error fields
            $table->dropColumn([
                'cloud_storage_provider',
                'cloud_storage_error_type',
                'cloud_storage_error_context',
                'connection_health_at_failure',
                'retry_recommended_at'
            ]);
        });
    }
};
