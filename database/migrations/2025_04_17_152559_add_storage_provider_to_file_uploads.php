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
            // Add storage provider column with google-drive as default for existing records
            $table->string('storage_provider')->default('google-drive')->after('id');

            // Rename google_drive_file_id to provider_file_id for provider agnostic naming
            $table->renameColumn('google_drive_file_id', 'provider_file_id');

            // Add index for faster lookups by provider
            $table->index('storage_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            // Remove the storage provider column
            $table->dropColumn('storage_provider');

            // Rename provider_file_id back to google_drive_file_id
            $table->renameColumn('provider_file_id', 'google_drive_file_id');

            // Remove the index
            $table->dropIndex(['storage_provider']);
        });
    }
};
