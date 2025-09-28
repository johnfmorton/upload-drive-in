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
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add index for name field to optimize LIKE searches
            // This will help with queries like: WHERE name LIKE '%search%'
            $table->index(['name'], 'idx_users_name_search');
            
            // Add composite index for search queries that filter by role and search name/email
            // This covers the common pattern: WHERE role = 'client' AND (name LIKE '%term%' OR email LIKE '%term%')
            $table->index(['role', 'name'], 'idx_users_role_name_search');
            $table->index(['role', 'email'], 'idx_users_role_email_search');
            
            // Add composite index for pagination with search
            // This helps with ORDER BY and LIMIT clauses in paginated search results
            $table->index(['role', 'created_at', 'id'], 'idx_users_role_created_pagination');
        });

        // Log the performance optimization
        Log::info('Search performance indexes added to users table', [
            'indexes_added' => [
                'idx_users_name_search',
                'idx_users_role_name_search', 
                'idx_users_role_email_search',
                'idx_users_role_created_pagination'
            ],
            'migration' => 'add_search_performance_indexes_to_users_table'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name_search');
            $table->dropIndex('idx_users_role_name_search');
            $table->dropIndex('idx_users_role_email_search');
            $table->dropIndex('idx_users_role_created_pagination');
        });

        Log::info('Search performance indexes removed from users table');
    }
};