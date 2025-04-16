<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_access_rules', function (Blueprint $table) {
            $table->id();
            $table->string('mode')->default('blacklist'); // 'blacklist' or 'whitelist'
            $table->text('rules')->nullable(); // Store rules as JSON
            $table->boolean('allow_public_registration')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_access_rules');
    }
};
