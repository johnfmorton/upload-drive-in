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
            $table->foreignId('client_user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_user_id')->nullable()->after('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('chunk_size')->nullable()->after('file_size');
            $table->integer('total_chunks')->nullable()->after('chunk_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->dropForeign(['client_user_id']);
            $table->dropForeign(['company_user_id']);
            $table->dropColumn(['client_user_id', 'company_user_id', 'chunk_size', 'total_chunks']);
        });
    }
};
