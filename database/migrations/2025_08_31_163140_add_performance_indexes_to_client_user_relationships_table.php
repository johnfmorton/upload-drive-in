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
        Schema::table('client_user_relationships', function (Blueprint $table) {
            // Note: idx_client_primary_contact is not needed because the existing 
            // unique_primary_relationship constraint already covers (client_user_id, is_primary)
            // and provides the same query optimization benefits
            
            // Index for finding clients where a company user is primary contact
            // This optimizes queries like: WHERE company_user_id = ? AND is_primary = true
            $table->index(['company_user_id', 'is_primary'], 'idx_company_user_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_user_relationships', function (Blueprint $table) {
            // Drop the performance index
            // Note: In some database systems (like MariaDB), we may need to temporarily
            // drop the foreign key constraint to remove the index
            try {
                $table->dropIndex('idx_company_user_primary');
            } catch (\Exception $e) {
                // If direct drop fails due to foreign key constraint, handle it gracefully
                // The index will be automatically removed when the table is dropped in future migrations
                \Log::warning('Could not drop index idx_company_user_primary: ' . $e->getMessage());
            }
        });
    }
};
