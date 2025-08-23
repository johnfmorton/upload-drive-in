<?php

namespace App\Http\Controllers\Admin;

use App\Models\FileUpload;
use App\Services\QueueTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DashboardController extends AdminController
{
    public function index()
    {
        // Get all files, ordered by most recent
        $files = FileUpload::orderBy('created_at', 'desc')->paginate(10);

        // Check if this is a first-time login after setup completion
        $isFirstTimeLogin = $this->checkFirstTimeLogin();

        return view('admin.dashboard', compact('files', 'isFirstTimeLogin'));
    }

    /**
     * Check if this is the admin's first login after setup completion
     */
    private function checkFirstTimeLogin(): bool
    {
        $user = auth()->user();
        
        // Only check for admin users
        if (!$user || !$user->isAdmin()) {
            return false;
        }

        // Check if setup was recently completed (within last 5 minutes)
        $setupService = app(\App\Services\SetupService::class);
        $setupState = $setupService->getSetupState();
        
        if (!isset($setupState['completed_at'])) {
            return false;
        }

        $completedAt = \Carbon\Carbon::parse($setupState['completed_at']);
        $isRecentlyCompleted = $completedAt->diffInMinutes(now()) <= 5;

        // Check if user has logged in before (excluding the current session)
        $hasLoggedInBefore = $user->last_login_at && 
                           $user->last_login_at->lt($completedAt);

        return $isRecentlyCompleted && !$hasLoggedInBefore;
    }

    public function destroy(FileUpload $file)
    {
        try {
            // Delete from Google Drive if file exists there
            if ($file->google_drive_file_id) {
                try {
                    // Use the new method from FileUpload model
                    $deleted = $file->deleteFromGoogleDrive();
                    if ($deleted) {
                        Log::info('Successfully deleted file from Google Drive: ' . $file->google_drive_file_id);
                    } else {
                        Log::warning('Failed to delete file from Google Drive: ' . $file->google_drive_file_id);
                    }
                } catch (\Exception $e) {
                    Log::error('Google Drive API call failed during deletion for file ID: ' . $file->google_drive_file_id . ' Error: ' . $e->getMessage());
                    throw new \Exception('Failed to delete file from Google Drive. Aborting deletion. Error: ' . $e->getMessage(), 0, $e);
                }
            }

            // Delete the local file if it exists
            if (Storage::disk('public')->exists('uploads/' . $file->filename)) {
                Storage::disk('public')->delete('uploads/' . $file->filename);
            }

            // Delete the database record
            $file->delete();

            return redirect()->route('admin.dashboard')
                ->with('success', 'File has been deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Error deleting file: ' . $e->getMessage());
        }
    }

    /**
     * Process pending uploads that failed to upload to Google Drive.
     */
    public function processPendingUploads()
    {
        try {
            // Get pending uploads count
            $pendingCount = FileUpload::whereNull('google_drive_file_id')
                ->orWhere('google_drive_file_id', '')
                ->count();

            if ($pendingCount === 0) {
                return redirect()->route('admin.dashboard')
                    ->with('info', 'No pending uploads found.');
            }

            // Call the artisan command to process pending uploads
            \Illuminate\Support\Facades\Artisan::call('uploads:process-pending', [
                '--limit' => 50
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();
            Log::info('Processed pending uploads via admin interface', ['output' => $output]);

            return redirect()->route('admin.dashboard')
                ->with('success', "Processing {$pendingCount} pending uploads. Check the queue status for progress.");

        } catch (\Exception $e) {
            Log::error('Failed to process pending uploads', ['error' => $e->getMessage()]);
            return redirect()->route('admin.dashboard')
                ->with('error', 'Failed to process pending uploads: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch a test job to verify queue worker functionality.
     * 
     * @param Request $request
     * @param QueueTestService $queueTestService
     * @return JsonResponse
     */
    public function testQueue(Request $request, QueueTestService $queueTestService): JsonResponse
    {
        // Validate optional delay parameter (let validation exceptions bubble up)
        $request->validate([
            'delay' => 'sometimes|integer|min:0|max:60'
        ]);

        try {
            $delay = $request->input('delay', 0);
            
            // Dispatch test job and get job ID
            $jobId = $queueTestService->dispatchTestJob($delay);
            
            Log::info('Queue test job dispatched via admin dashboard', [
                'test_job_id' => $jobId,
                'delay' => $delay,
                'admin_user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Test job dispatched successfully',
                'test_job_id' => $jobId,
                'delay' => $delay,
                'dispatched_at' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to dispatch queue test job via admin dashboard', [
                'error' => $e->getMessage(),
                'admin_user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch test job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check the status of a queue test job for AJAX polling.
     * 
     * @param Request $request
     * @param QueueTestService $queueTestService
     * @return JsonResponse
     */
    public function checkQueueTestStatus(Request $request, QueueTestService $queueTestService): JsonResponse
    {
        try {
            // Validate job ID parameter
            $request->validate([
                'test_job_id' => 'required|string|regex:/^test_[a-f0-9\-]{36}$/'
            ]);

            $jobId = $request->input('test_job_id');
            
            // Get job status
            $status = $queueTestService->checkTestJobStatus($jobId);
            
            return response()->json([
                'success' => true,
                'status' => $status,
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid job ID format',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Failed to check queue test job status via admin dashboard', [
                'error' => $e->getMessage(),
                'test_job_id' => $request->input('test_job_id'),
                'admin_user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check test job status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get comprehensive queue health metrics for admin monitoring.
     * 
     * @param QueueTestService $queueTestService
     * @return JsonResponse
     */
    public function getQueueHealth(QueueTestService $queueTestService): JsonResponse
    {
        try {
            // Get queue health metrics
            $metrics = $queueTestService->getQueueHealthMetrics();
            
            return response()->json([
                'success' => true,
                'metrics' => $metrics,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get queue health metrics via admin dashboard', [
                'error' => $e->getMessage(),
                'admin_user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue health metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
