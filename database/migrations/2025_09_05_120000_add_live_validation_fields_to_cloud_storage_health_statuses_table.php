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
            // Add fields for real-time validation tracking
            $table->timestamp('last_live_validation_at')->nullable()->after('operational_test_result');
            $table->json('live_validation_result')->nullable()->after('last_live_validation_at');
            $table->timestamp('api_connectivity_last_tested_at')->nullable()->after('live_validation_result');
            $table->json('api_connectivity_result')->nullable()->after('api_connectivity_last_tested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloud_storage_health_statuses', function (Blueprint $table) {
            $table->dropColumn([
                'last_live_validation_at',
                'live_validation_result',
                'api_connectivity_last_tested_at',
                'api_connectivity_result',
            ]);
        });
    }
};