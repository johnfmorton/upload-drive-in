<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FileUpload;
use App\Services\GoogleDriveService;
use App\Services\GoogleDriveProvider;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageHealthService;
use App\Jobs\UploadToGoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GoogleDriveUnifiedCallbackController extends Controller
{
    public function __construct(
        private GoogleDriveService $googleDriveService,
        private GoogleDriveProvider $googleDriveProvider,
        private CloudStorageHealthService $healthService,
        private CloudStorageManager $storageManager
    ) {}

    /**
     * Handle Google Drive OAuth callback for all user types with enhanced reconnection flow.
     */
    public function callback(Request $request): RedirectResponse
    {
        $stateData = null;
        $user = null;
        
        try {
            // Get the authorization code
            $code = $request->get('code');
            if (!$code) {
                return $this->redirectWithError(__('messages.oauth_authorization_code_missing'));
            }

            // Handle error responses from OAuth provider
            if ($request->has('error')) {
                $error = $request->get('error');
                $errorDescription = $request->get('error_description', 'OAuth authorization failed');
                
                Log::warning('OAuth callback received error', [
                    'error' => $error,
                    'error_description' => $errorDescription
                ]);
                
                return $this->redirectWithError("Authorization failed: {$errorDescription}");
            }

            // Decode state parameter to get user information
            $state = $request->get('state');
            if (!$state) {
                return $this->redirectWithError(__('messages.oauth_state_parameter_missing'));
            }

            $stateData = json_decode(base64_decode($state), true);
            if (!$stateData || !isset($stateData['user_id'])) {
                return $this->redirectWithError(__('messages.oauth_state_parameter_invalid'));
            }

            // Find the user
            $user = User::find($stateData['user_id']);
            if (!$user) {
                return $this->redirectWithError(__('messages.oauth_user_not_found'));
            }

            // Log the user in if they're not already authenticated
            if (!Auth::check() || Auth::id() !== $user->id) {
                Auth::login($user);
            }

            Log::info('Starting enhanced Google Drive OAuth callback processing', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role->value,
                'is_reconnection' => $stateData['is_reconnection'] ?? false
            ]);

            // Handle the OAuth callback
            $this->googleDriveService->handleCallback($user, $code);

            // Validate the connection after OAuth completion
            $connectionValid = $this->validateConnection($user);
            if (!$connectionValid) {
                Log::error('Connection validation failed after OAuth callback', [
                    'user_id' => $user->id
                ]);
                return $this->redirectWithError(__('messages.oauth_connection_validation_failed'));
            }

            // If this was a reconnection, attempt to retry pending uploads
            $retriedUploads = 0;
            if ($stateData['is_reconnection'] ?? false) {
                $retriedUploads = $this->retryPendingUploads($user);
            }

            Log::info('Google Drive OAuth callback completed successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role->value,
                'connection_validated' => true,
                'retried_uploads' => $retriedUploads
            ]);

            // Redirect based on user type with success message
            return $this->redirectBasedOnUserType($user, $retriedUploads);

        } catch (\Exception $e) {
            Log::error('Google Drive OAuth callback failed', [
                'error' => $e->getMessage(),
                'user_id' => $stateData['user_id'] ?? null,
                'user_email' => $user?->email,
                'trace' => $e->getTraceAsString()
            ]);

            // Handle specific error types with appropriate fallback
            return $this->handleCallbackFailure($user, $e, $stateData);
        }
    }

    /**
     * Validate the connection after OAuth completion.
     */
    private function validateConnection(User $user): bool
    {
        try {
            Log::debug('Validating Google Drive connection after OAuth', [
                'user_id' => $user->id
            ]);

            // Use CloudStorageManager to get the provider and check connection health
            $provider = $this->storageManager->getProvider('google-drive');
            $healthStatus = $provider->getConnectionHealth($user);
            
            $isValid = $healthStatus->isHealthy() || $healthStatus->isDegraded();
            
            Log::info('Connection validation completed', [
                'user_id' => $user->id,
                'is_valid' => $isValid,
                'health_status' => $healthStatus->status,
                'requires_reconnection' => $healthStatus->requiresReconnection
            ]);

            return $isValid;

        } catch (\Exception $e) {
            Log::error('Connection validation failed with exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retry pending uploads for the user after successful reconnection.
     */
    private function retryPendingUploads(User $user): int
    {
        try {
            Log::info('Starting automatic retry of pending uploads after reconnection', [
                'user_id' => $user->id
            ]);

            // Find pending uploads for this user
            $pendingUploads = FileUpload::where(function($query) use ($user) {
                $query->where('company_user_id', $user->id)
                      ->orWhere('uploaded_by_user_id', $user->id);
            })
            ->whereNull('google_drive_file_id')
            ->where(function($query) {
                // Only retry uploads that failed due to connection issues
                $query->whereIn('cloud_storage_error_type', [
                    'token_expired',
                    'insufficient_permissions',
                    'invalid_credentials'
                ])->orWhereNull('cloud_storage_error_type');
            })
            ->limit(50) // Limit to prevent overwhelming the queue
            ->get();

            if ($pendingUploads->isEmpty()) {
                Log::info('No pending uploads found for retry after reconnection', [
                    'user_id' => $user->id
                ]);
                return 0;
            }

            $retriedCount = 0;
            foreach ($pendingUploads as $upload) {
                try {
                    // Clear previous error information
                    $upload->update([
                        'cloud_storage_error_type' => null,
                        'cloud_storage_error_context' => null,
                        'connection_health_at_failure' => null,
                        'last_error' => null,
                        'error_details' => null,
                        'retry_recommended_at' => now()
                    ]);

                    // Dispatch the upload job
                    UploadToGoogleDrive::dispatch($upload);
                    $retriedCount++;

                    Log::debug('Queued upload for retry after reconnection', [
                        'upload_id' => $upload->id,
                        'filename' => $upload->original_filename,
                        'user_id' => $user->id
                    ]);

                } catch (\Exception $e) {
                    Log::warning('Failed to queue upload for retry after reconnection', [
                        'upload_id' => $upload->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Completed automatic retry of pending uploads after reconnection', [
                'user_id' => $user->id,
                'total_pending' => $pendingUploads->count(),
                'successfully_queued' => $retriedCount
            ]);

            return $retriedCount;

        } catch (\Exception $e) {
            Log::error('Failed to retry pending uploads after reconnection', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Handle callback failure with appropriate fallback handling.
     */
    private function handleCallbackFailure(User $user = null, \Exception $exception, array $stateData = null): RedirectResponse
    {
        $errorMessage = 'Failed to connect to Google Drive';
        
        // Classify the error for better user feedback
        if (str_contains($exception->getMessage(), 'invalid_grant')) {
            $errorMessage = 'The authorization code has expired. Please try connecting again.';
        } elseif (str_contains($exception->getMessage(), 'access_denied')) {
            $errorMessage = 'Access was denied. Please grant the required permissions to connect Google Drive.';
        } elseif (str_contains($exception->getMessage(), 'invalid_client')) {
            $errorMessage = 'Invalid Google Drive configuration. Please contact your administrator.';
        } else {
            $errorMessage .= ': ' . $exception->getMessage();
        }

        // If we have user context, mark the connection as requiring attention
        if ($user) {
            try {
                $this->healthService->markConnectionAsUnhealthy(
                    $user,
                    'google-drive',
                    $exception->getMessage(),
                    \App\Enums\CloudStorageErrorType::INVALID_CREDENTIALS
                );
            } catch (\Exception $e) {
                Log::warning('Failed to update health status after callback failure', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->redirectWithError($errorMessage);
    }

    /**
     * Redirect user to appropriate dashboard based on their role with enhanced messaging.
     */
    private function redirectBasedOnUserType(User $user, int $retriedUploads = 0): RedirectResponse
    {
        $baseMessage = 'Successfully connected to Google Drive!';
        
        if ($retriedUploads > 0) {
            $baseMessage .= " {$retriedUploads} pending uploads have been queued for retry.";
        }

        if ($user->isAdmin()) {
            return redirect()
                ->route('admin.cloud-storage.index')
                ->with('success', $baseMessage);
        }

        if ($user->isEmployee()) {
            return redirect()
                ->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('success', $baseMessage);
        }

        // Fallback for other user types
        return redirect()
            ->route('dashboard')
            ->with('success', $baseMessage);
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