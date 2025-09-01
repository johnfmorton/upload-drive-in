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
            // Add recovery tracking fields
            $table->integer('retry_count')->default(0)->after('google_drive_file_id');
            $table->text('last_error')->nullable()->after('retry_count');
            $table->json('error_details')->nullable()->after('last_error');
            $table->timestamp('last_processed_at')->nullable()->after('error_details');
            $table->integer('recovery_attempts')->default(0)->after('last_processed_at');
            
            // Add indexes for efficient querying
            $table->index(['retry_count', 'last_processed_at'], 'idx_file_uploads_retry_processing');
            $table->index(['recovery_attempts', 'updated_at'], 'idx_file_uploads_recovery_status');
            $table->index('last_processed_at', 'idx_file_uploads_last_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_file_uploads_retry_processing');
            $table->dropIndex('idx_file_uploads_recovery_status');
            $table->dropIndex('idx_file_uploads_last_processed');
            
            // Drop recovery fields
            $table->dropColumn([
                'retry_count',
                'last_error',
                'error_details',
                'last_processed_at',
                'recovery_attempts'
            ]);
        });
    }
};
