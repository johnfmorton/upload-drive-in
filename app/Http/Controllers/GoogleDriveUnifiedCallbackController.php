<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleDriveUnifiedCallbackController extends Controller
{
    public function __construct(
        private GoogleDriveService $googleDriveService
    ) {}

    /**
     * Handle Google Drive OAuth callback for all user types.
     */
    public function callback(Request $request): RedirectResponse
    {
        $stateData = null;
        
        try {
            // Get the authorization code
            $code = $request->get('code');
            if (!$code) {
                return $this->redirectWithError('Authorization code not provided.');
            }

            // Decode state parameter to get user information
            $state = $request->get('state');
            if (!$state) {
                return $this->redirectWithError('State parameter missing.');
            }

            $stateData = json_decode(base64_decode($state), true);
            if (!$stateData || !isset($stateData['user_id'])) {
                return $this->redirectWithError('Invalid state parameter.');
            }

            // Find the user
            $user = User::find($stateData['user_id']);
            if (!$user) {
                return $this->redirectWithError('User not found.');
            }

            // Log the user in if they're not already authenticated
            if (!Auth::check() || Auth::id() !== $user->id) {
                Auth::login($user);
            }

            // Handle the OAuth callback
            $this->googleDriveService->handleCallback($user, $code);

            Log::info('Google Drive OAuth callback successful', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role->value
            ]);

            // Redirect based on user type
            return $this->redirectBasedOnUserType($user);

        } catch (\Exception $e) {
            Log::error('Google Drive OAuth callback failed', [
                'error' => $e->getMessage(),
                'user_id' => $stateData['user_id'] ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->redirectWithError('Failed to connect to Google Drive: ' . $e->getMessage());
        }
    }

    /**
     * Redirect user to appropriate dashboard based on their role.
     */
    private function redirectBasedOnUserType(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return redirect()
                ->route('admin.cloud-storage.index')
                ->with('success', 'Successfully connected to Google Drive!');
        }

        if ($user->isEmployee()) {
            return redirect()
                ->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('success', 'Successfully connected to Google Drive!');
        }

        // Fallback for other user types
        return redirect()
            ->route('dashboard')
            ->with('success', 'Successfully connected to Google Drive!');
    }

    /**
     * Redirect with error message based on current user or fallback.
     */
    private function redirectWithError(string $message): RedirectResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', $message);
        }

        if ($user->isAdmin()) {
            return redirect()
                ->route('admin.cloud-storage.index')
                ->with('error', $message);
        }

        if ($user->isEmployee()) {
            return redirect()
                ->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('error', $message);
        }

        return redirect()->route('dashboard')->with('error', $message);
    }
}