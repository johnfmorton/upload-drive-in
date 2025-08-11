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
        Schema::create('cloud_storage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // google-drive, microsoft-teams, dropbox
            $table->string('key'); // client_id, client_secret, root_folder_id, etc.
            $table->text('value')->nullable();
            $table->boolean('encrypted')->default(false);
            $table->timestamps();
            
            $table->unique(['provider', 'key']);
            $table->index(['provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloud_storage_settings');
    }
};
