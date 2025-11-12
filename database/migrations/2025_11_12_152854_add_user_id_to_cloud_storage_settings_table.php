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
        Schema::table('cloud_storage_settings', function (Blueprint $table) {
            // Drop the old unique constraint
            $table->dropUnique(['provider', 'key']);
            
            // Add user_id column (nullable for system-level settings)
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            
            // Add new unique constraint including user_id
            $table->unique(['user_id', 'provider', 'key']);
            
            // Add index for system-level settings queries
            $table->index(['user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloud_storage_settings', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique(['user_id', 'provider', 'key']);
            
            // Drop the new index
            $table->dropIndex(['user_id', 'provider']);
            
            // Drop the user_id column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
            // Restore the old unique constraint
            $table->unique(['provider', 'key']);
        });
    }
};
