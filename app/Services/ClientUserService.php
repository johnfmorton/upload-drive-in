<?php

namespace App\Services;

use App\Models\User;
use App\Models\ClientUserRelationship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Enums\UserRole;
use Exception;

class ClientUserService
{
    /**
     * Find or create a client user and associate them with a company user.
     *
     * @param array $userData The user data (email, name, etc.)
     * @param User $companyUser The admin/employee creating or associating with the client
     * @return User The client user
     */
    public function findOrCreateClientUser(array $userData, User $companyUser): User
    {
        return DB::transaction(function () use ($userData, $companyUser) {
            // Try to find existing user by email
            $clientUser = User::where('email', $userData['email'])->first();
            $isNewUser = false;

            if (!$clientUser) {
                // Create new client user if they don't exist
                $clientUser = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make(Str::random(16)), // Random password as they'll use magic links
                    'role' => UserRole::CLIENT,
                ]);
                $isNewUser = true;
            }

            // Check if this company user is already associated
            $existingRelationship = ClientUserRelationship::where([
                'client_user_id' => $clientUser->id,
                'company_user_id' => $companyUser->id,
            ])->first();

            if (!$existingRelationship) {
                // Create new relationship
                ClientUserRelationship::create([
                    'client_user_id' => $clientUser->id,
                    'company_user_id' => $companyUser->id,
                    'is_primary' => $isNewUser, // Only set as primary if this is a new user
                ]);
            }

            return $clientUser;
        });
    }

    /**
     * Get the appropriate company user's Google Drive token for a client user.
     * This will be the token of their primary company user.
     *
     * @param User $clientUser
     * @return User|null The company user whose token should be used
     */
    public function getTokenOwnerForClient(User $clientUser): ?User
    {
        return $clientUser->primaryCompanyUser();
    }

    /**
     * Associate an existing client user with another company user.
     *
     * @param User $clientUser
     * @param User $companyUser
     * @return void
     */
    public function associateWithCompanyUser(User $clientUser, User $companyUser): void
    {
        // Only create the relationship if it doesn't exist
        ClientUserRelationship::firstOrCreate([
            'client_user_id' => $clientUser->id,
            'company_user_id' => $companyUser->id,
        ], [
            'is_primary' => false, // Never set as primary when adding additional relationships
        ]);
    }
}
