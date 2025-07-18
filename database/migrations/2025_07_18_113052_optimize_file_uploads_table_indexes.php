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
            // Add indexes for commonly filtered and sorted columns
            $table->index('created_at', 'idx_file_uploads_created_at');
            $table->index('updated_at', 'idx_file_uploads_updated_at');
            $table->index('mime_type', 'idx_file_uploads_mime_type');
            $table->index('file_size', 'idx_file_uploads_file_size');
            $table->index('google_drive_file_id', 'idx_file_uploads_google_drive_file_id');
            
            // Composite indexes for common query patterns
            $table->index(['email', 'created_at'], 'idx_file_uploads_email_created');
            $table->index(['mime_type', 'created_at'], 'idx_file_uploads_mime_created');
            $table->index(['file_size', 'created_at'], 'idx_file_uploads_size_created');
            
            // Index for pending/completed status queries
            $table->index(['google_drive_file_id', 'created_at'], 'idx_file_uploads_status_created');
            
            // Index for user relationship queries
            $table->index('client_user_id', 'idx_file_uploads_client_user_id');
            $table->index('company_user_id', 'idx_file_uploads_company_user_id');
            $table->index('uploaded_by_user_id', 'idx_file_uploads_uploaded_by_user_id');
            
            // Full-text search index for filename and message
            $table->fullText(['original_filename', 'message'], 'idx_file_uploads_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            // Drop individual indexes
            $table->dropIndex('idx_file_uploads_created_at');
            $table->dropIndex('idx_file_uploads_updated_at');
            $table->dropIndex('idx_file_uploads_mime_type');
            $table->dropIndex('idx_file_uploads_file_size');
            $table->dropIndex('idx_file_uploads_google_drive_file_id');
            
            // Drop composite indexes
            $table->dropIndex('idx_file_uploads_email_created');
            $table->dropIndex('idx_file_uploads_mime_created');
            $table->dropIndex('idx_file_uploads_size_created');
            $table->dropIndex('idx_file_uploads_status_created');
            
            // Drop user relationship indexes
            $table->dropIndex('idx_file_uploads_client_user_id');
            $table->dropIndex('idx_file_uploads_company_user_id');
            $table->dropIndex('idx_file_uploads_uploaded_by_user_id');
            
            // Drop full-text index
            $table->dropFullText('idx_file_uploads_fulltext');
        });
    }
};
