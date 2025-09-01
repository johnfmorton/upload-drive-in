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
        Schema::create('cloud_storage_health_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // google-drive, dropbox, onedrive, etc.
            $table->enum('status', ['healthy', 'degraded', 'unhealthy', 'disconnected'])->default('disconnected');
            $table->timestamp('last_successful_operation_at')->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->string('last_error_type')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('requires_reconnection')->default(false);
            $table->json('provider_specific_data')->nullable();
            $table->timestamps();

            // Ensure one health status per user per provider
            $table->unique(['user_id', 'provider']);
            
            // Index for efficient queries
            $table->index(['user_id', 'status']);
            $table->index(['provider', 'status']);
            $table->index('last_successful_operation_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloud_storage_health_statuses');
    }
};
