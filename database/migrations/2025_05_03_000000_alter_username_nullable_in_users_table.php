<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the username column to allow NULL values
        DB::statement('ALTER TABLE `users` MODIFY `username` VARCHAR(255) NULL;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the username column to NOT NULL
        DB::statement('ALTER TABLE `users` MODIFY `username` VARCHAR(255) NOT NULL;');
    }
};
