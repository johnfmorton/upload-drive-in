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
            // Add missing field for comprehensive error tracking
            // Note: last_error_type and last_error_message already exist
            $table->json('last_error_context')->nullable()->after('last_error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloud_storage_health_statuses', function (Blueprint $table) {
            $table->dropColumn('last_error_context');
        });
    }
};