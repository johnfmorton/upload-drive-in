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
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('google_drive_file_id')->nullable();
            $table->text('message')->nullable();
            $table->string('validation_method'); // 'email' or 'token'
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->timestamps();

            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
