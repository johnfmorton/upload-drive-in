<?php

namespace App\Http\Controllers;

use App\Services\SetupDetectionService;
use App\Services\SetupStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Exception;

class SetupInstructionsController extends Controller
{
    public function __construct(
        private SetupDetectionService $setupDetectionService,
        private SetupStatusService $setupStatusService
    ) {}

    /**
     * Display the setup instructions page or redirect if setup is complete.
     */
    public function show(): View|RedirectResponse
    {
        // If setup is complete, redirect to appropriate dashboard
        if ($this->setupDetectionService->isSetupComplete()) {
            // If user is authenticated, redirect to their appropriate dashboard
            if (auth()->check()) {
                $user = auth()->user();
                
                if ($user->isAdmin()) {
                    return redirect()->route('admin.dashboard');
                } elseif ($user->isEmployee()) {
                    return redirect()->route('employee.dashboard', ['username' => $user->username]);
                } elseif ($user->isClient()) {
                    return redirect()->route('client.dashboard');
                }
            }
            
            // If not authenticated, redirect to home page
            return redirect()->route('home');
        }

        return view('setup.instructions');
    }

    /**
     * Refresh and return JSON status data for all setup steps.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshStatus(Request $request): JsonResponse
    {
        try {
            // Validate CSRF token (handled automatically by VerifyCsrfToken middleware)
            
            // Get fresh status data for all steps
            $statuses = $this->setupStatusService->refreshAllStatuses();
            $summary = $this->setupStatusService->getStatusSummary(false);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'statuses' => $statuses,
                    'summary' => $summary,
                    'refreshed_at' => now()->toISOString()
                ],
                'message' => 'Status refreshed successfully'
            ]);
            
        } catch (Exception $e) {
            \Log::error('Failed to refresh setup status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Failed to refresh status. Please try again.',
                    'code' => 'REFRESH_FAILED',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * Refresh and return JSON status data for a single setup step.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshSingleStep(Request $request): JsonResponse
    {
        try {
            // Validate the step parameter
            $validated = $request->validate([
                'step' => 'required|string|in:database,mail,google_drive,migrations,admin_user,queue_worker'
            ]);
            
            $stepName = $validated['step'];
            
            // Clear cache and get fresh status for all steps (since steps may be interdependent)
            $this->setupStatusService->clearStatusCache();
            $allStatuses = $this->setupStatusService->getDetailedStepStatuses(false);
            
            // Check if the requested step exists in the results
            if (!isset($allStatuses[$stepName])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid step name provided.',
                        'code' => 'INVALID_STEP',
                        'valid_steps' => array_keys($allStatuses)
                    ]
                ], 400);
            }
            
            $stepStatus = $allStatuses[$stepName];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'step' => $stepName,
                    'status' => $stepStatus,
                    'refreshed_at' => now()->toISOString()
                ],
                'message' => "Status for '{$stepStatus['step_name']}' refreshed successfully"
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Invalid request parameters.',
                    'code' => 'VALIDATION_ERROR',
                    'details' => $e->errors()
                ]
            ], 422);
            
        } catch (Exception $e) {
            \Log::error('Failed to refresh single step status', [
                'step' => $request->input('step'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Failed to refresh step status. Please try again.',
                    'code' => 'STEP_REFRESH_FAILED',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }
}