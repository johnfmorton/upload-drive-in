<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CloudStorageController extends Controller
{
    protected GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Display the employee's cloud storage settings.
     */
    public function index()
    {
        // Check if the current provider requires user authentication
        $defaultProvider = config('cloud-storage.default');
        $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
        $requiresUserAuth = ($providerConfig['auth_type'] ?? 'oauth') === 'oauth';
        
        // If provider doesn't require user auth (like S3), redirect to dashboard
        if (!$requiresUserAuth) {
            return redirect()
                ->route('employee.dashboard', ['username' => Auth::user()->username])
                ->with('info', 'Cloud storage is managed by the administrator for the current provider.');
        }
        
        $user = Auth::user();
        
        // Get current folder name if set
        $currentFolderName = null;
        $currentFolderId = $user->google_drive_root_folder_id;
        
        if ($user->hasGoogleDriveConnected()) {
            try {
                if (empty($currentFolderId) || $currentFolderId === 'root') {
                    $currentFolderName = 'Google Drive Root';
                } else {
                    $service = $this->driveService->getDriveService($user);
                    $folder = $service->files->get($currentFolderId, ['fields' => 'name']);
                    $currentFolderName = $folder->getName();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get folder name for employee', [
                    'user_id' => $user->id,
                    'folder_id' => $currentFolderId,
                    'error' => $e->getMessage()
                ]);
                $currentFolderName = 'Unknown Folder';
            }
        } else {
            // Default messaging when not connected
            $currentFolderName = empty($currentFolderId) ? 'Google Drive Root (default)' : 'Selected Folder';
        }

        return view('employee.cloud-storage.index', compact('user', 'currentFolderName', 'currentFolderId'));
    }

    /**
     * Connect to Google Drive (redirect to OAuth).
     */
    public function connect()
    {
        $user = Auth::user();
        $authUrl = $this->driveService->getAuthUrl($user);
        
        return redirect($authUrl);
    }

    /**
     * Disconnect from Google Drive.
     */
    public function disconnect()
    {
        $user = Auth::user();
        
        try {
            $this->driveService->disconnect($user);
            
            return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('success', 'Successfully disconnected from Google Drive');
        } catch (\Exception $e) {
            Log::error('Failed to disconnect Google Drive for employee', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('error', 'Failed to disconnect from Google Drive');
        }
    }

    /**
     * Get cloud storage status for the dashboard widget.
     */
    public function getStatus()
    {
        // Check if the current provider requires user authentication
        $defaultProvider = config('cloud-storage.default');
        $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
        $requiresUserAuth = ($providerConfig['auth_type'] ?? 'oauth') === 'oauth';
        
        // If provider doesn't require user auth (like S3), return appropriate response
        if (!$requiresUserAuth) {
            return response()->json([
                'success' => false,
                'error' => 'Cloud storage status is not available for the current provider.',
                'message' => 'Provider does not require user authentication'
            ], 403);
        }
        
        try {
            $user = Auth::user();
            $healthService = app(\App\Services\CloudStorageHealthService::class);
            
            // Get health status for all providers using consolidated status
            $providersHealth = $healthService->getAllProvidersHealth($user);
            
            // Get pending uploads count for each provider
            $pendingUploads = \App\Models\FileUpload::where(function($query) use ($user) {
                $query->where('company_user_id', $user->id)
                      ->orWhere('uploaded_by_user_id', $user->id);
            })
            ->whereNull('google_drive_file_id')
            ->whereNull('cloud_storage_error_type')
            ->get()
            ->groupBy('cloud_storage_provider')
            ->map(fn($uploads) => $uploads->count())
            ->toArray();
            
            // Get failed uploads count for each provider
            $failedUploads = \App\Models\FileUpload::where(function($query) use ($user) {
                $query->where('company_user_id', $user->id)
                      ->orWhere('uploaded_by_user_id', $user->id);
            })
            ->whereNotNull('cloud_storage_error_type')
            ->get()
            ->groupBy('cloud_storage_provider')
            ->map(fn($uploads) => $uploads->count())
            ->toArray();
            
            return response()->json([
                'success' => true,
                'providers' => $providersHealth,
                'pending_uploads' => $pendingUploads,
                'failed_uploads' => $failedUploads,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get cloud storage status', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = __('messages.cloud_storage_status_retrieval_failed');
            
            return response()->json([
                'success' => false,
                'error' => $errorMessage,
                'message' => 'Failed to get status'
            ], 500);
        }
    }

    /**
     * Reconnect a cloud storage provider.
     */
    public function reconnectProvider(Request $request)
    {
        // Check if the current provider requires user authentication
        $defaultProvider = config('cloud-storage.default');
        $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
        $requiresUserAuth = ($providerConfig['auth_type'] ?? 'oauth') === 'oauth';
        
        // If provider doesn't require user auth (like S3), return error
        if (!$requiresUserAuth) {
            return response()->json([
                'error' => 'Reconnection is not available for the current provider.'
            ], 403);
        }
        
        $validated = $request->validate([
            'provider' => 'required|string|in:google-drive,amazon-s3,microsoft-teams'
        ]);

        try {
            $user = Auth::user();
            $provider = $validated['provider'];
            
            switch ($provider) {
                case 'google-drive':
                    $authUrl = $this->driveService->getAuthUrl($user);
                    return response()->json(['redirect_url' => $authUrl]);
                    
                default:
                    return response()->json(['error' => 'Provider not supported yet'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to reconnect provider', [
                'provider' => $validated['provider'],
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to initiate reconnection'], 500);
        }
    }

    /**
     * Test connection to a cloud storage provider using real-time health validation.
     */
    public function testConnection(Request $request)
    {
        // Check if the current provider requires user authentication
        $defaultProvider = config('cloud-storage.default');
        $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
        $requiresUserAuth = ($providerConfig['auth_type'] ?? 'oauth') === 'oauth';
        
        // If provider doesn't require user auth (like S3), return error
        if (!$requiresUserAuth) {
            return response()->json([
                'success' => false,
                'error' => 'Connection testing is not available for the current provider.',
                'message' => 'Provider does not require user authentication'
            ], 403);
        }
        
        $validated = $request->validate([
            'provider' => 'required|string|in:google-drive,microsoft-teams'
        ]);

        try {
            $user = Auth::user();
            $provider = $validated['provider'];
            
            // Use RealTimeHealthValidator for live validation
            $realTimeValidator = app(\App\Services\RealTimeHealthValidator::class);
            $tokenStatusService = app(\App\Services\TokenStatusService::class);
            $errorMessageService = app(\App\Services\CloudStorageErrorMessageService::class);
            
            Log::info('Starting real-time connection test (employee)', [
                'user_id' => $user->id,
                'provider' => $provider,
                'test_type' => 'employee_dashboard_test_connection'
            ]);
            
            // Perform live health validation
            $healthStatus = $realTimeValidator->validateConnectionHealth($user, $provider);
            
            // Get comprehensive token status
            $tokenStatus = $tokenStatusService->getTokenStatus($user, $provider);
            
            $isHealthy = $healthStatus->isHealthy();
            $status = $healthStatus->getStatus();
            
            // Use centralized messaging instead of inline generation
            $errorContext = [
                'provider' => $provider,
                'error_type' => $healthStatus->getErrorType(),
                'error_message' => $healthStatus->getErrorMessage(),
                'consecutive_failures' => $healthStatus->getConsecutiveFailures() ?? 0,
                'user' => $user
            ];
            
            $message = $errorMessageService->getStatusDisplayMessage($status, $errorContext);
            
            Log::info('Real-time connection test completed (employee)', [
                'user_id' => $user->id,
                'provider' => $provider,
                'is_healthy' => $isHealthy,
                'status' => $status,
                'validation_time_ms' => $healthStatus->getValidationDetails()['validation_time_ms'] ?? null,
            ]);
            
            return response()->json([
                'success' => $isHealthy,
                'message' => $message,
                'status' => $status,
                'status_localized' => $healthStatus->getLocalizedStatus(),
                'error_message' => $healthStatus->getErrorMessage(),
                'error_type' => $healthStatus->getErrorType(),
                'error_type_localized' => $healthStatus->getLocalizedErrorType(),
                'validation_details' => $healthStatus->getValidationDetails(),
                'validated_at' => $healthStatus->getValidatedAt()?->toISOString(),
                'token_status' => $tokenStatus,
                'test_type' => 'real_time_validation',
                'cache_ttl_seconds' => $healthStatus->getCacheTtlSeconds(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to test connection with real-time validation (employee)', [
                'user_id' => Auth::id(),
                'provider' => $validated['provider'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Connection test failed due to an unexpected error. Please try again.',
                'message' => 'Connection test failed',
                'test_type' => 'real_time_validation',
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}