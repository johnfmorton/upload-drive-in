<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    /**
     * Handle request to unsubscribe from upload notifications.
     *
     * @param Request $request The incoming request.
     * @param User $user The user model resolved via route model binding.
     * @return \Illuminate\View\View
     */
    public function unsubscribeUploads(Request $request, User $user)
    {
        // Verification is handled by the 'signed' middleware implicitly

        $user->receive_upload_notifications = false;
        $user->save();

        // Redirect to profile page with a success message
        return redirect()->route('profile.edit')
            ->with('status', __('messages.unsubscribe_success_message'));
    }
}
