<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Outgoing file shares: a sender uploads files into a share and sends a
     * link to download them. See docs/plans/outgoing-file-shares.md.
     */
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Links are looked up by the token's SHA-256; the token itself is only
            // stored encrypted (APP_KEY), so the sender can copy the link again
            $table->string('token_hash', 64)->unique();
            $table->text('token_encrypted');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('password')->nullable();
            $table->json('recipient_emails')->nullable();
            $table->string('provider');
            // The account whose cloud storage holds the files (Drive token owner)
            $table->foreignId('cloud_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cloud_folder_id')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('expires_at');
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamp('first_downloaded_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('share_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('local_path')->nullable();
            $table->string('provider_file_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('last_error')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();

            $table->index(['share_id', 'status']);
        });

        Schema::create('share_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_id')->constrained()->cascadeOnDelete();
            $table->foreignId('share_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['share_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('receive_share_download_notifications')->default(true)->after('receive_upload_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('receive_share_download_notifications');
        });

        Schema::dropIfExists('share_access_logs');
        Schema::dropIfExists('share_files');
        Schema::dropIfExists('shares');
    }
};
