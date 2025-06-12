<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            // For SQLite, we need to recreate the table
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->change();
            });
        } else {
            // For MySQL/MariaDB
            DB::statement('ALTER TABLE `users` MODIFY `username` VARCHAR(255) NULL;');
        }
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
