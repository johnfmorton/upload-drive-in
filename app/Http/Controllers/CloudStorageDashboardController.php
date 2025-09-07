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
     * Get cloud storage status for dashboard display with enhanced token information
     */
    public function getStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tokenStatusService = app(\App\Services\TokenStatusService::class);
            
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
                
                // Get comprehensive token status
                $tokenStatus = $tokenStatusService->getTokenStatus($user, $providerHealth['provider']);
                
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
                    'operational_test_result' => $providerHealth['operational_test_result'],
                    'token_status' => $tokenStatus, // Enhanced token information
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $statusData,
                'providers' => $statusData, // Alias for backward compatibility
                'retrieved_at' => now()->toISOString(),
                'status_type' => 'enhanced_with_token_details'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get cloud storage dashboard status', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = __('messages.cloud_storage_status_retrieval_failed');
            
            return response()->json([
                'success' => false,
                'error' => $errorMessage,
                'message' => 'Failed to get status',
                'status_type' => 'enhanced_with_token_details'
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
     * Trigger comprehensive health check for a specific provider using real-time validation
     */
    public function checkHealth(Request $request, string $provider): JsonResponse
    {
        $user = $request->user();
        
        try {
            // Use RealTimeHealthValidator for live validation
            $realTimeValidator = app(\App\Services\RealTimeHealthValidator::class);
            $tokenStatusService = app(\App\Services\TokenStatusService::class);
            
            Log::info('Starting real-time health check', [
                'user_id' => $user->id,
                'provider' => $provider,
                'check_type' => 'dashboard_health_check'
            ]);
            
            // Perform live health validation
            $healthStatus = $realTimeValidator->validateConnectionHealth($user, $provider);
            
            // Get comprehensive token status
            $tokenStatus = $tokenStatusService->getTokenStatus($user, $provider);
            
            $isHealthy = $healthStatus->isHealthy();
            $status = $healthStatus->getStatus();
            
            // Use centralized messaging instead of inline generation
            $errorMessageService = app(\App\Services\CloudStorageErrorMessageService::class);
            $errorContext = [
                'provider' => $provider,
                'error_type' => $healthStatus->getErrorType(),
                'error_message' => $healthStatus->getErrorMessage(),
                'consecutive_failures' => $healthStatus->getConsecutiveFailures() ?? 0,
                'user' => $user
            ];
            
            $message = $errorMessageService->getStatusDisplayMessage($status, $errorContext);
            
            Log::info('Real-time health check completed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'is_healthy' => $isHealthy,
                'status' => $status,
                'validation_time_ms' => $healthStatus->getValidationDetails()['validation_time_ms'] ?? null,
            ]);
            
            return response()->json([
                'success' => $isHealthy,
                'data' => [
                    'provider' => $provider,
                    'status' => $status,
                    'status_localized' => $healthStatus->getLocalizedStatus(),
                    'is_healthy' => $isHealthy,
                    'error_message' => $healthStatus->getErrorMessage(),
                    'error_type' => $healthStatus->getErrorType(),
                    'error_type_localized' => $healthStatus->getLocalizedErrorType(),
                    'validation_details' => $healthStatus->getValidationDetails(),
                    'validated_at' => $healthStatus->getValidatedAt()?->toISOString(),
                    'token_status' => $tokenStatus,
                    'check_type' => 'real_time_validation',
                    'cache_ttl_seconds' => $healthStatus->getCacheTtlSeconds(),
                ],
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Health check failed with real-time validation', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = __('messages.cloud_storage_health_check_failed');
            
            return response()->json([
                'success' => false,
                'error' => $errorMessage,
                'message' => 'Health check failed: ' . $e->getMessage(),
                'check_type' => 'real_time_validation',
                'error_details' => config('app.debug') ? $e->getMessage() : null
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