<?php

namespace App\Http\Controllers;

use App\Models\CloudStorageHealthStatus;
use App\Models\FileUpload;
use App\Services\CloudStorageHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
            $user = $request->user();
            
            // Use the health service to get consolidated status for all providers
            $providersHealth = $this->healthService->getAllProvidersHealth($user);
            
            $statusData = [];
            
            foreach ($providersHealth as $providerHealth) {
                // Get pending uploads count for this provider
                $pendingUploadsCount = FileUpload::where('uploaded_by_user_id', $user->id)
                    ->where('storage_provider', $providerHealth['provider'])
                    ->whereNull('google_drive_file_id')
                    ->whereNull('cloud_storage_error_type')
                    ->count();
                
                // Get failed uploads count for this provider
                $failedUploadsCount = FileUpload::where('uploaded_by_user_id', $user->id)
                    ->where('storage_provider', $providerHealth['provider'])
                    ->whereNotNull('cloud_storage_error_type')
                    ->count();
                
                // Get user-friendly error message
                $userFriendlyMessage = null;
                if ($providerHealth['last_error_type']) {
                    $errorContext = [
                        'provider' => ucfirst(str_replace('-', ' ', $providerHealth['provider'])),
                        'original_message' => $providerHealth['last_error_message']
                    ];
                    
                    $userFriendlyMessage = $this->getUserFriendlyMessage($providerHealth['last_error_type'], $errorContext);
                }
                
                $statusData[] = [
                    'provider' => $providerHealth['provider'],
                    'status' => $providerHealth['status'],
                    'consolidated_status' => $providerHealth['consolidated_status'],
                    'status_message' => $providerHealth['status_message'],
                    'is_healthy' => $providerHealth['is_healthy'],
                    'last_successful_operation_at' => $providerHealth['last_successful_operation'],
                    'consecutive_failures' => $providerHealth['consecutive_failures'],
                    'last_error_type' => $providerHealth['last_error_type'],
                    'last_error_message' => $providerHealth['last_error_message'],
                    'requires_reconnection' => $providerHealth['requires_reconnection'],
                    'pending_uploads_count' => $pendingUploadsCount,
                    'failed_uploads_count' => $failedUploadsCount,
                    'user_friendly_message' => $userFriendlyMessage,
                    'provider_specific_data' => $providerHealth['provider_specific_data'],
                    'token_refresh_working' => $providerHealth['token_refresh_working'],
                    'operational_test_result' => $providerHealth['operational_test_result']
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $statusData
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get cloud storage dashboard status', [
                'user_id' => $request->user()->id,
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
     * Trigger comprehensive health check for a specific provider
     */
    public function checkHealth(Request $request, string $provider): JsonResponse
    {
        $user = $request->user();
        
        try {
            // Perform comprehensive health check using new proactive validation logic
            $healthStatus = $this->healthService->checkConnectionHealth($user, $provider);
            
            // Get consolidated status information
            $consolidatedStatus = $healthStatus->consolidated_status ?? 'connection_issues';
            $isHealthy = $consolidatedStatus === 'healthy';
            
            $message = match ($consolidatedStatus) {
                'healthy' => 'Health check successful - your ' . ucfirst(str_replace('-', ' ', $provider)) . ' integration is working properly',
                'authentication_required' => 'Authentication required - please reconnect your ' . ucfirst(str_replace('-', ' ', $provider)) . ' account',
                'connection_issues' => 'Connection issues detected - ' . ($healthStatus->last_error_message ?? 'unable to connect to ' . ucfirst(str_replace('-', ' ', $provider))),
                'not_connected' => 'Account not connected - please set up your ' . ucfirst(str_replace('-', ' ', $provider)) . ' integration',
                default => 'Health check completed with issues - ' . ($healthStatus->last_error_message ?? 'unknown status')
            };
            
            return response()->json([
                'success' => $isHealthy,
                'data' => [
                    'provider' => $provider,
                    'status' => $healthStatus->status,
                    'consolidated_status' => $consolidatedStatus,
                    'status_message' => $healthStatus->getConsolidatedStatusMessage(),
                    'is_healthy' => $isHealthy,
                    'requires_reconnection' => $healthStatus->requires_reconnection ?? false,
                    'last_successful_operation_at' => $healthStatus->last_successful_operation_at?->toISOString(),
                    'consecutive_failures' => $healthStatus->consecutive_failures,
                    'last_error_type' => $healthStatus->last_error_type,
                    'last_error_message' => $healthStatus->last_error_message,
                    'token_refresh_working' => $healthStatus->isTokenRefreshWorking(),
                    'operational_test_result' => $healthStatus->operational_test_result
                ],
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Health check failed due to an unexpected error. Please try again.',
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