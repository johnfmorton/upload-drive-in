<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\ClientUserRelationship;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the admin and client users
        $admin = User::where('role', UserRole::ADMIN)->first();
        $client = User::where('role', UserRole::CLIENT)->first();

        if ($admin && $client) {
            // Create the relationship if it doesn't exist
            ClientUserRelationship::firstOrCreate([
                'client_user_id' => $client->id,
                'company_user_id' => $admin->id,
            ], [
                'is_primary' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the admin and client users
        $admin = User::where('role', UserRole::ADMIN)->first();
        $client = User::where('role', UserRole::CLIENT)->first();

        if ($admin && $client) {
            // Delete the relationship
            ClientUserRelationship::where([
                'client_user_id' => $client->id,
                'company_user_id' => $admin->id,
            ])->delete();
        }
    }
};
