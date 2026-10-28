<?php

namespace App\Policies;

use App\Models\Share;
use App\Models\User;

/**
 * Admins can see and manage every share; employees only their own.
 */
class SharePolicy
{
    public function view(User $user, Share $share): bool
    {
        return $user->isAdmin() || $share->user_id === $user->id;
    }

    public function update(User $user, Share $share): bool
    {
        return $this->view($user, $share);
    }
}
