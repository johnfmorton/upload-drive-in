<?php

namespace App\Http\Controllers;

use App\Models\CloudStorageHealthStatus;
use App\Models\FileUpload;
use App\Services\CloudStorageHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloudStorageDashboardController extends Controller
{
    public function __construct(
        private CloudStorageHealthService $healthService
    ) {}

    /**
     * Get cloud storage status for dashboard display
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get health status for all providers
        $healthStatuses = CloudStorageHealthStatus::where('user_id', $user->id)->get();
        
        $statusData = [];
        
        foreach ($healthStatuses as $status) {
            // Get pending uploads count for this provider
            $pendingUploadsCount = FileUpload::where('uploaded_by_user_id', $user->id)
                ->where('storage_provider', $status->provider)
                ->whereNotNull('cloud_storage_error_type')
                ->count();
            
            // Get user-friendly error message
            $userFriendlyMessage = null;
            if ($status->last_error_type) {
                $errorContext = [
                    'provider' => ucfirst(str_replace('-', ' ', $status->provider)),
                    'original_message' => $status->last_error_message
                ];
                
                $userFriendlyMessage = $this->getUserFriendlyMessage($status->last_error_type, $errorContext);
            }
            
            $statusData[] = [
                'provider' => $status->provider,
                'status' => $status->status,
                'last_successful_operation_at' => $status->last_successful_operation_at?->toISOString(),
                'consecutive_failures' => $status->consecutive_failures,
                'last_error_type' => $status->last_error_type,
                'last_error_message' => $status->last_error_message,
                'requires_reconnection' => $status->requires_reconnection,
                'pending_uploads_count' => $pendingUploadsCount,
                'user_friendly_message' => $userFriendlyMessage,
                'provider_specific_data' => $status->provider_specific_data
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $statusData
        ]);
    }
    
    /**
     * Get detailed error information for a specific provider
     */
    public function getProviderErrors(Request $request, string $provider): JsonResponse
    {
        $user = $request->user();
        
        // Get failed uploads for this provider
        $failedUploads = FileUpload::where('uploaded_by_user_id', $user->id)
            ->where('storage_provider', $provider)
            ->whereNotNull('cloud_storage_error_type')
            ->with(['uploadedByUser'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);
        
        // Get health status
        $healthStatus = CloudStorageHealthStatus::where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();
        
        return response()->json([
            'success' => true,
            'data' => [
                'health_status' => $healthStatus,
                'failed_uploads' => $failedUploads,
                'summary' => [
                    'total_failed' => $failedUploads->total(),
                    'error_types' => $this->getErrorTypeSummary($user->id, $provider)
                ]
            ]
        ]);
    }
    
    /**
     * Trigger health check for a specific provider
     */
    public function checkHealth(Request $request, string $provider): JsonResponse
    {
        $user = $request->user();
        
        try {
            $healthStatus = $this->healthService->checkConnectionHealth($user, $provider);
            
            return response()->json([
                'success' => true,
                'data' => $healthStatus,
                'message' => 'Health check completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Health check failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function getUserFriendlyMessage(string $errorType, array $context): string
    {
        $provider = $context['provider'] ?? 'cloud storage';
        
        return match ($errorType) {
            'token_expired' => 
                "Your {$provider} connection has expired. Please reconnect to continue uploading files.",
            'insufficient_permissions' => 
                "Insufficient {$provider} permissions. Please reconnect and grant full access.",
            'api_quota_exceeded' => 
                "{$provider} API limit reached. Uploads will resume automatically shortly.",
            'storage_quota_exceeded' => 
                "{$provider} storage quota exceeded. Please free up space or upgrade your plan.",
            'network_error' => 
                'Network connection issue. The upload will be retried automatically.',
            'folder_access_denied' => 
                "Cannot access the configured {$provider} folder. Please check folder permissions.",
            default => "{$provider} upload failed: " . ($context['original_message'] ?? 'Unknown error')
        };
    }
    
    private function getErrorTypeSummary(int $userId, string $provider): array
    {
        $errorCounts = FileUpload::where('uploaded_by_user_id', $userId)
            ->where('storage_provider', $provider)
            ->whereNotNull('cloud_storage_error_type')
            ->selectRaw('cloud_storage_error_type, COUNT(*) as count')
            ->groupBy('cloud_storage_error_type')
            ->pluck('count', 'cloud_storage_error_type')
            ->toArray();
        
        return $errorCounts;
    }
}