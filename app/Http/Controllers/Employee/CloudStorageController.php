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
            return response()->json([
                'success' => false,
                'error' => 'Unable to retrieve cloud storage status. Please try again.',
                'message' => 'Failed to get status'
            ], 500);
        }
    }

    /**
     * Reconnect a cloud storage provider.
     */
    public function reconnectProvider(Request $request)
    {
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
     * Test connection to a cloud storage provider.
     */
    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:google-drive,microsoft-teams'
        ]);

        try {
            $user = Auth::user();
            $provider = $validated['provider'];
            $healthService = app(\App\Services\CloudStorageHealthService::class);
            
            // Perform comprehensive health check using new proactive validation logic
            $healthStatus = $healthService->checkConnectionHealth($user, $provider);
            
            // Use consolidated status for consistent messaging
            $consolidatedStatus = $healthStatus->consolidated_status ?? 'connection_issues';
            $isHealthy = $consolidatedStatus === 'healthy';
            
            $message = match ($consolidatedStatus) {
                'healthy' => 'Connection test successful - your ' . ucfirst(str_replace('-', ' ', $provider)) . ' integration is working properly',
                'authentication_required' => 'Authentication required - please reconnect your ' . ucfirst(str_replace('-', ' ', $provider)) . ' account',
                'connection_issues' => 'Connection issues detected - ' . ($healthStatus->last_error_message ?? 'unable to connect to ' . ucfirst(str_replace('-', ' ', $provider))),
                'not_connected' => 'Account not connected - please set up your ' . ucfirst(str_replace('-', ' ', $provider)) . ' integration',
                default => 'Connection test failed - ' . ($healthStatus->last_error_message ?? 'unknown error')
            };
            
            return response()->json([
                'success' => $isHealthy,
                'message' => $message,
                'status' => $healthStatus->status,
                'consolidated_status' => $consolidatedStatus,
                'status_message' => $healthStatus->getConsolidatedStatusMessage(),
                'requires_reconnection' => $healthStatus->requires_reconnection ?? false,
                'last_successful_operation' => $healthStatus->last_successful_operation_at?->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to test connection', [
                'user_id' => Auth::id(),
                'provider' => $validated['provider'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Connection test failed due to an unexpected error. Please try again.',
                'message' => 'Connection test failed'
            ], 500);
        }
    }
}