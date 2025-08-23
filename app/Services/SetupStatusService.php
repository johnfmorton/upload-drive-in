<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Service for centralized setup status management with caching.
 * 
 * This service aggregates status information from SetupDetectionService
 * and provides structured responses with caching to prevent excessive
 * database queries and improve performance.
 */
class SetupStatusService
{
    private const CACHE_TTL = 30; // 30 seconds TTL
    private const CACHE_KEY_PREFIX = 'setup_status';
    
    private SetupDetectionService $setupDetectionService;

    public function __construct(SetupDetectionService $setupDetectionService)
    {
        $this->setupDetectionService = $setupDetectionService;
    }

    /**
     * Get detailed status information for all setup steps with structured responses.
     * 
     * @param bool $useCache Whether to use cached results (default: true)
     * @return array<string, array> Structured status data for each setup step
     */
    public function getDetailedStepStatuses(bool $useCache = true): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . '_detailed_statuses';
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $statuses = $this->setupDetectionService->getAllStepStatuses();
            
            // Enhance the status data with additional metadata
            $enhancedStatuses = $this->enhanceStatusData($statuses);
            
            // Cache the results
            Cache::put($cacheKey, $enhancedStatuses, self::CACHE_TTL);
            
            return $enhancedStatuses;
        } catch (Exception $e) {
            Log::error('Failed to get detailed step statuses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return cached data if available, otherwise return error state
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
            
            return $this->getErrorFallbackStatuses($e);
        }
    }

    /**
     * Refresh all status checks and update cache.
     * 
     * @return array<string, array> Fresh status data for all setup steps
     */
    public function refreshAllStatuses(): array
    {
        try {
            // Clear existing cache
            $this->clearStatusCache();
            
            // Get fresh status data (bypassing cache)
            $statuses = $this->getDetailedStepStatuses(false);
            
            Log::info('Setup statuses refreshed successfully', [
                'timestamp' => Carbon::now()->toISOString(),
                'status_count' => count($statuses)
            ]);
            
            return $statuses;
        } catch (Exception $e) {
            Log::error('Failed to refresh setup statuses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get a summary overview of setup completion status.
     * 
     * @param bool $useCache Whether to use cached results (default: true)
     * @return array Summary information about setup completion
     */
    public function getStatusSummary(bool $useCache = true): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . '_summary';
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $statuses = $this->getDetailedStepStatuses($useCache);
            
            $summary = $this->calculateStatusSummary($statuses);
            
            // Cache the summary
            Cache::put($cacheKey, $summary, self::CACHE_TTL);
            
            return $summary;
        } catch (Exception $e) {
            Log::error('Failed to get status summary', [
                'error' => $e->getMessage()
            ]);
            
            // Return cached data if available
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
            
            return [
                'overall_status' => 'error',
                'completion_percentage' => 0,
                'completed_steps' => 0,
                'total_steps' => 0,
                'incomplete_steps' => [],
                'error_steps' => [],
                'last_updated' => Carbon::now()->toISOString(),
                'error_message' => 'Unable to determine setup status'
            ];
        }
    }

    /**
     * Clear all cached status data.
     * 
     * @return void
     */
    public function clearStatusCache(): void
    {
        $cacheKeys = [
            self::CACHE_KEY_PREFIX . '_detailed_statuses',
            self::CACHE_KEY_PREFIX . '_summary'
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        Log::debug('Setup status cache cleared');
    }

    /**
     * Check if setup is complete based on cached or fresh data.
     * 
     * @param bool $useCache Whether to use cached results (default: true)
     * @return bool True if all setup steps are completed
     */
    public function isSetupComplete(bool $useCache = true): bool
    {
        try {
            $summary = $this->getStatusSummary($useCache);
            return $summary['overall_status'] === 'completed';
        } catch (Exception $e) {
            Log::error('Failed to check setup completion status', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get the cache TTL value for status data.
     * 
     * @return int Cache TTL in seconds
     */
    public function getCacheTtl(): int
    {
        return self::CACHE_TTL;
    }

    /**
     * Enhance status data with additional metadata and formatting.
     * 
     * @param array<string, array> $statuses Raw status data from SetupDetectionService
     * @return array<string, array> Enhanced status data
     */
    private function enhanceStatusData(array $statuses): array
    {
        $enhanced = [];
        
        foreach ($statuses as $step => $status) {
            $enhanced[$step] = [
                'status' => $status['status'] ?? 'unknown',
                'message' => $status['message'] ?? 'Status unknown',
                'details' => $status['details'] ?? [],
                'checked_at' => $status['checked_at'] ?? Carbon::now()->toISOString(),
                'step_name' => $this->getStepDisplayName($step),
                'priority' => $this->getStepPriority($step),
                'can_retry' => $this->canRetryStep($step, $status['status'] ?? 'unknown')
            ];
        }
        
        return $enhanced;
    }

    /**
     * Calculate summary statistics from detailed status data.
     * 
     * @param array<string, array> $statuses Detailed status data
     * @return array Summary statistics
     */
    private function calculateStatusSummary(array $statuses): array
    {
        $totalSteps = count($statuses);
        $completedSteps = 0;
        $incompleteSteps = [];
        $errorSteps = [];
        
        foreach ($statuses as $step => $status) {
            $stepStatus = $status['status'] ?? 'unknown';
            
            if ($stepStatus === 'completed') {
                $completedSteps++;
            } elseif ($stepStatus === 'error') {
                $errorSteps[] = $step;
            } else {
                $incompleteSteps[] = $step;
            }
        }
        
        $completionPercentage = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100, 1) : 0;
        
        // Determine overall status
        $overallStatus = 'incomplete';
        if (count($errorSteps) > 0) {
            $overallStatus = 'error';
        } elseif ($completedSteps === $totalSteps) {
            $overallStatus = 'completed';
        }
        
        return [
            'overall_status' => $overallStatus,
            'completion_percentage' => $completionPercentage,
            'completed_steps' => $completedSteps,
            'total_steps' => $totalSteps,
            'incomplete_steps' => $incompleteSteps,
            'error_steps' => $errorSteps,
            'last_updated' => Carbon::now()->toISOString()
        ];
    }

    /**
     * Get user-friendly display name for a setup step.
     * 
     * @param string $step Step identifier
     * @return string Display name
     */
    private function getStepDisplayName(string $step): string
    {
        $displayNames = [
            'database' => 'Database Connection',
            'mail' => 'Mail Configuration',
            'google_drive' => 'Google Drive Integration',
            'migrations' => 'Database Migrations',
            'admin_user' => 'Admin User Account',
            'queue_worker' => 'Queue Worker'
        ];
        
        return $displayNames[$step] ?? ucwords(str_replace('_', ' ', $step));
    }

    /**
     * Get priority order for setup steps (lower number = higher priority).
     * 
     * @param string $step Step identifier
     * @return int Priority value
     */
    private function getStepPriority(string $step): int
    {
        $priorities = [
            'database' => 1,
            'migrations' => 2,
            'admin_user' => 3,
            'mail' => 4,
            'google_drive' => 5,
            'queue_worker' => 6
        ];
        
        return $priorities[$step] ?? 99;
    }

    /**
     * Determine if a step can be retried based on its status.
     * 
     * @param string $step Step identifier
     * @param string $status Current status
     * @return bool True if step can be retried
     */
    private function canRetryStep(string $step, string $status): bool
    {
        // Steps that are completed don't need retry
        if ($status === 'completed') {
            return false;
        }
        
        // Most steps can be retried except for certain conditions
        $nonRetryableConditions = [
            'cannot_verify' => true,
            'checking' => true
        ];
        
        return !isset($nonRetryableConditions[$status]);
    }

    /**
     * Generate fallback status data when service fails.
     * 
     * @param Exception $exception The exception that caused the failure
     * @return array<string, array> Fallback status data
     */
    private function getErrorFallbackStatuses(Exception $exception): array
    {
        $errorMessage = 'Service temporarily unavailable';
        $timestamp = Carbon::now()->toISOString();
        
        $fallbackSteps = [
            'database', 'mail', 'google_drive', 'migrations', 'admin_user', 'queue_worker'
        ];
        
        $fallbackStatuses = [];
        
        foreach ($fallbackSteps as $step) {
            $fallbackStatuses[$step] = [
                'status' => 'cannot_verify',
                'message' => $errorMessage,
                'details' => [
                    'error' => $exception->getMessage(),
                    'fallback' => true
                ],
                'checked_at' => $timestamp,
                'step_name' => $this->getStepDisplayName($step),
                'priority' => $this->getStepPriority($step),
                'can_retry' => true
            ];
        }
        
        return $fallbackStatuses;
    }
}