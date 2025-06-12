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
        Schema::create('client_user_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // The company user who created the client
            $table->timestamps();

            // Each client-company user pair should be unique
            $table->unique(['client_user_id', 'company_user_id']);

            // Only one company user can be primary for a client
            $table->unique(['client_user_id', 'is_primary'], 'unique_primary_relationship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_user_relationships');
    }
};
