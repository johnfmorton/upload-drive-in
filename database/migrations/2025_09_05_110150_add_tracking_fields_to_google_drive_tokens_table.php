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
        Schema::table('google_drive_tokens', function (Blueprint $table) {
            // Token refresh tracking fields
            $table->timestamp('last_refresh_attempt_at')->nullable()->after('scopes');
            $table->unsignedInteger('refresh_failure_count')->default(0)->after('last_refresh_attempt_at');
            $table->timestamp('last_successful_refresh_at')->nullable()->after('refresh_failure_count');
            $table->timestamp('proactive_refresh_scheduled_at')->nullable()->after('last_successful_refresh_at');
            
            // Health monitoring fields
            $table->unsignedInteger('health_check_failures')->default(0)->after('proactive_refresh_scheduled_at');
            $table->boolean('requires_user_intervention')->default(false)->after('health_check_failures');
            
            // Notification tracking fields
            $table->timestamp('last_notification_sent_at')->nullable()->after('requires_user_intervention');
            $table->unsignedInteger('notification_failure_count')->default(0)->after('last_notification_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_drive_tokens', function (Blueprint $table) {
            $table->dropColumn([
                'last_refresh_attempt_at',
                'refresh_failure_count',
                'last_successful_refresh_at',
                'proactive_refresh_scheduled_at',
                'health_check_failures',
                'requires_user_intervention',
                'last_notification_sent_at',
                'notification_failure_count'
            ]);
        });
    }
};
