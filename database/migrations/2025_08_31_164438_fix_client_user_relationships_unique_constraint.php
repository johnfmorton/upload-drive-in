<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_user_relationships', function (Blueprint $table) {
            // Drop the problematic unique constraint
            $table->dropUnique('unique_primary_relationship');
        });

        // Create a partial unique index that only applies when is_primary = true
        // Note: SQLite doesn't support partial indexes in the same way as PostgreSQL
        // For SQLite, we'll use a different approach
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // For SQLite, we'll create a unique index on a computed column
            // This is a workaround since SQLite has limited partial index support
            DB::statement('CREATE UNIQUE INDEX unique_primary_relationship ON client_user_relationships (client_user_id) WHERE is_primary = 1');
        } elseif ($driver === 'mysql') {
            // MySQL doesn't support partial indexes, so we'll use a different approach
            // We'll create a unique index on client_user_id, is_primary but handle the logic in the application
            DB::statement('CREATE UNIQUE INDEX unique_primary_relationship ON client_user_relationships (client_user_id, is_primary)');
        } elseif ($driver === 'pgsql') {
            // PostgreSQL supports partial indexes
            DB::statement('CREATE UNIQUE INDEX unique_primary_relationship ON client_user_relationships (client_user_id) WHERE is_primary = true');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the partial unique index
        DB::statement('DROP INDEX IF EXISTS unique_primary_relationship');
        
        // Recreate the original constraint
        Schema::table('client_user_relationships', function (Blueprint $table) {
            $table->unique(['client_user_id', 'is_primary'], 'unique_primary_relationship');
        });
    }
};