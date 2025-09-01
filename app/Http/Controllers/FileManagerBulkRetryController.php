<?php

namespace App\Http\Controllers;

use App\Jobs\UploadToGoogleDrive;
use App\Models\CloudStorageHealthStatus;
use App\Models\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileManagerBulkRetryController extends Controller
{
    /**
     * Retry all failed uploads for a specific provider
     */
    public function bulkRetry(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:google-drive,dropbox,onedrive'
        ]);
        
        $user = $request->user();
        $provider = $request->input('provider');
        
        // Get all failed uploads for this provider
        $failedUploads = FileUpload::where('uploaded_by_user_id', $user->id)
            ->where('storage_provider', $provider)
            ->whereNotNull('cloud_storage_error_type')
            ->whereNull('google_drive_file_id') // Not successfully uploaded
            ->get();
        
        if ($failedUploads->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No failed uploads found to retry',
                'count' => 0
            ]);
        }
        
        $retryCount = 0;
        
        foreach ($failedUploads as $upload) {
            // Clear error information to allow retry
            $upload->update([
                'cloud_storage_error_type' => null,
                'cloud_storage_error_context' => null,
                'retry_recommended_at' => now()
            ]);
            
            // Queue the upload job based on provider
            switch ($provider) {
                case 'google-drive':
                    UploadToGoogleDrive::dispatch($upload);
                    break;
                // Add other providers as they are implemented
                default:
                    Log::warning("Bulk retry not implemented for provider: {$provider}");
                    continue 2;
            }
            
            $retryCount++;
        }
        
        // Update health status to indicate retry attempt
        $healthStatus = CloudStorageHealthStatus::where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();
        
        if ($healthStatus) {
            $healthStatus->update([
                'status' => 'healthy',
                'consecutive_failures' => 0,
                'last_error_type' => null,
                'last_error_message' => null,
                'requires_reconnection' => false
            ]);
        }
        
        Log::info("Bulk retry initiated for {$retryCount} failed uploads", [
            'user_id' => $user->id,
            'provider' => $provider,
            'upload_count' => $retryCount
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Bulk retry initiated for {$retryCount} failed uploads",
            'count' => $retryCount
        ]);
    }
    
    /**
     * Retry a specific failed upload
     */
    public function retryUpload(Request $request, FileUpload $upload): JsonResponse
    {
        // Verify user owns this upload
        if ($upload->uploaded_by_user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Check if upload actually failed
        if (!$upload->cloud_storage_error_type) {
            return response()->json([
                'success' => false,
                'message' => 'Upload has not failed and does not need retry'
            ], 400);
        }
        
        // Clear error information
        $upload->update([
            'cloud_storage_error_type' => null,
            'cloud_storage_error_context' => null,
            'retry_recommended_at' => now()
        ]);
        
        // Queue the upload job based on provider
        switch ($upload->storage_provider) {
            case 'google-drive':
                UploadToGoogleDrive::dispatch($upload);
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => "Retry not supported for provider: {$upload->storage_provider}"
                ], 400);
        }
        
        Log::info("Individual upload retry initiated", [
            'user_id' => $request->user()->id,
            'upload_id' => $upload->id,
            'provider' => $upload->storage_provider
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Upload retry initiated successfully'
        ]);
    }
}