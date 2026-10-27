<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * cloud_storage_provider was missing from FileUpload::$fillable, so the
     * upload job's update() silently dropped it and uploaded files were left
     * with NULL. Fill it in using the same rule downloads already fall back
     * to: S3 keys always contain "/" (email folder/filename), Google Drive
     * file IDs never do.
     */
    public function up(): void
    {
        DB::table('file_uploads')
            ->whereNull('cloud_storage_provider')
            ->whereNotNull('google_drive_file_id')
            ->where('google_drive_file_id', '!=', '')
            ->where('google_drive_file_id', 'like', '%/%')
            ->update(['cloud_storage_provider' => 'amazon-s3']);

        DB::table('file_uploads')
            ->whereNull('cloud_storage_provider')
            ->whereNotNull('google_drive_file_id')
            ->where('google_drive_file_id', '!=', '')
            ->where('google_drive_file_id', 'not like', '%/%')
            ->update(['cloud_storage_provider' => 'google-drive']);
    }

    /**
     * Reverse the migrations.
     *
     * Not reversible: rows that were already set correctly can't be told
     * apart from backfilled ones, and the values are correct either way.
     */
    public function down(): void
    {
        //
    }
};
