<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Enums\UserRole;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix any existing client users with string role
        User::where('role', 'client')->update(['role' => UserRole::CLIENT]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to string role
        User::where('role', UserRole::CLIENT)->update(['role' => 'client']);
    }
};
