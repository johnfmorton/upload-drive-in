<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            // Add nullable username column
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->after('email');
            });

            // Populate username for existing users using the part before '@'
            DB::table('users')
                ->whereNull('username')
                ->update([
                    'username' => DB::raw("SUBSTRING_INDEX(email, '@', 1)")
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }
};
