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
            $table->string('token_type')->default('Bearer')->after('refresh_token');
            $table->json('scopes')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('google_drive_tokens', function (Blueprint $table) {
            $table->dropColumn(['token_type', 'scopes']);
        });
    }
};
