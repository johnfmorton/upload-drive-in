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
        // Add performance indexes for user email lookups
        Schema::table('users', function (Blueprint $table) {
            // Email is already unique, but we want to ensure it's optimized for lookups
            // Add a covering index that includes commonly accessed fields
            $table->index(['email', 'role', 'created_at'], 'idx_users_email_role_created');
        });

        // Add performance indexes for domain access rules
        Schema::table('domain_access_rules', function (Blueprint $table) {
            // Index for quick lookup of domain rules (there's typically only one record)
            $table->index(['allow_public_registration', 'mode'], 'idx_domain_rules_config');
        });

        // Add performance indexes for email validations
        Schema::table('email_validations', function (Blueprint $table) {
            // Optimize verification code lookups with expiration check
            $table->index(['verification_code', 'expires_at'], 'idx_email_validations_code_expires');
            // Optimize email-based lookups
            $table->index(['email', 'expires_at'], 'idx_email_validations_email_expires');
        });

        // Log the optimization (database-agnostic approach)
        try {
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("INSERT INTO `migrations` (`migration`, `batch`) VALUES ('performance_optimization_user_email_lookup', 1) ON DUPLICATE KEY UPDATE `batch` = 1");
            } else {
                // For SQLite and other databases, use INSERT OR IGNORE
                DB::statement("INSERT OR IGNORE INTO `migrations` (`migration`, `batch`) VALUES ('performance_optimization_user_email_lookup', 1)");
            }
        } catch (\Exception $e) {
            // Silently handle logging failures - the indexes are more important
            Log::info('Performance optimization migration completed', [
                'indexes_added' => true,
                'logging_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email_role_created');
        });

        Schema::table('domain_access_rules', function (Blueprint $table) {
            $table->dropIndex('idx_domain_rules_config');
        });

        Schema::table('email_validations', function (Blueprint $table) {
            $table->dropIndex('idx_email_validations_code_expires');
            $table->dropIndex('idx_email_validations_email_expires');
        });
    }
};