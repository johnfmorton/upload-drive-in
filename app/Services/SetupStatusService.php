<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Throwable;

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
    private const FALLBACK_CACHE_TTL = 300; // 5 minutes for fallback data
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY_MS = 1000; // 1 second
    
    private SetupDetectionService $setupDetectionService;

    public function __construct(SetupDetectionService $setupDetectionService)
    {
        $this->setupDetectionService = $setupDetectionService;
    }

    /**
     * Get detailed status information for all setup steps with structured responses.
     * 
     * @param bool $useCache Whether to use cached results (default: true)
     * @param int $retryAttempt Current retry attempt (internal use)
     * @return array<string, array> Structured status data for each setup step
     */
    public function getDetailedStepStatuses(bool $useCache = true, int $retryAttempt = 0): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . '_detailed_statuses';
        $fallbackCacheKey = $cacheKey . '_fallback';
        
        if ($useCache && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            Log::debug('Returning cached setup statuses', [
                'cache_key' => $cacheKey,
                'status_count' => count($cached)
            ]);
            return $cached;
        }

        try {
            $statuses = $this->executeWithRetry(function () {
                return $this->setupDetectionService->getAllStepStatuses();
            }, $retryAttempt);
            
            // Enhance the status data with additional metadata
            $enhancedStatuses = $this->enhanceStatusData($statuses);
            
            // Cache both regular and fallback results
            Cache::put($cacheKey, $enhancedStatuses, self::CACHE_TTL);
            Cache::put($fallbackCacheKey, $enhancedStatuses, self::FALLBACK_CACHE_TTL);
            
            Log::debug('Successfully retrieved and cached setup statuses', [
                'status_count' => count($enhancedStatuses),
                'retry_attempt' => $retryAttempt
            ]);
            
            return $enhancedStatuses;
            
        } catch (Exception $e) {
            Log::error('Failed to get detailed step statuses', [
                'error' => $e->getMessage(),
                'retry_attempt' => $retryAttempt,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Try fallback cache first
            if (Cache::has($fallbackCacheKey)) {
                $fallbackData = Cache::get($fallbackCacheKey);
                Log::info('Using fallback cached data for setup statuses', [
                    'fallback_cache_key' => $fallbackCacheKey,
                    'status_count' => count($fallbackData)
                ]);
                
                // Mark as fallback data
                foreach ($fallbackData as $step => &$status) {
                    $status['fallback'] = true;
                    $status['fallback_reason'] = 'Service temporarily unavailable';
                    $status['last_error'] = $e->getMessage();
                }
                
                return $fallbackData;
            }
            
            // Return regular cache if available
            if (Cache::has($cacheKey)) {
                $regularCache = Cache::get($cacheKey);
                Log::info('Using regular cached data as fallback', [
                    'cache_key' => $cacheKey,
                    'status_count' => count($regularCache)
                ]);
                return $regularCache;
            }
            
            // Last resort: return error state
            return $this->getErrorFallbackStatuses($e);
            
        } catch (Throwable $e) {
            Log::critical('Critical error getting setup statuses', [
                'error' => $e->getMessage(),
                'retry_attempt' => $retryAttempt,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getErrorFallbackStatuses($e);
        }
    }

    /**
     * Refresh all status checks and update cache.
     * 
     * @param bool $forceFresh Force fresh data even if service fails
     * @return array<string, array> Fresh status data for all setup steps
     * @throws Exception If refresh fails and no fallback is available
     */
    public function refreshAllStatuses(bool $forceFresh = false): array
    {
        $startTime = microtime(true);
        
        try {
            // Clear existing cache
            $this->clearStatusCache();
            
            // Also clear configuration cache if we're in setup context to ensure fresh env() reads
            $this->clearConfigurationCacheIfNeeded();
            
            // Get fresh status data (bypassing cache) with retry logic
            $statuses = $this->getDetailedStepStatuses(false);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Setup statuses refreshed successfully', [
                'timestamp' => Carbon::now()->toISOString(),
                'status_count' => count($statuses),
                'duration_ms' => $duration,
                'force_fresh' => $forceFresh
            ]);
            
            return $statuses;
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Failed to refresh setup statuses', [
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'force_fresh' => $forceFresh,
                'trace' => $e->getTraceAsString()
            ]);
            
            if (!$forceFresh) {
                // Try to return cached data as fallback
                $fallbackCacheKey = self::CACHE_KEY_PREFIX . '_detailed_statuses_fallback';
                if (Cache::has($fallbackCacheKey)) {
                    $fallbackData = Cache::get($fallbackCacheKey);
                    Log::info('Returning fallback data after refresh failure', [
                        'fallback_cache_key' => $fallbackCacheKey,
                        'status_count' => count($fallbackData)
                    ]);
                    return $fallbackData;
                }
            }
            
            throw $e;
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::critical('Critical error during status refresh', [
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'force_fresh' => $forceFresh,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception('Critical error during status refresh: ' . $e->getMessage(), 0, $e);
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
        $fallbackCacheKey = $cacheKey . '_fallback';
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $statuses = $this->getDetailedStepStatuses($useCache);
            
            $summary = $this->calculateStatusSummary($statuses);
            
            // Cache both regular and fallback summaries
            Cache::put($cacheKey, $summary, self::CACHE_TTL);
            Cache::put($fallbackCacheKey, $summary, self::FALLBACK_CACHE_TTL);
            
            return $summary;
            
        } catch (Exception $e) {
            Log::error('Failed to get status summary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Try fallback cache first
            if (Cache::has($fallbackCacheKey)) {
                $fallbackSummary = Cache::get($fallbackCacheKey);
                Log::info('Using fallback cached summary', [
                    'fallback_cache_key' => $fallbackCacheKey
                ]);
                
                // Mark as fallback data
                $fallbackSummary['fallback'] = true;
                $fallbackSummary['fallback_reason'] = 'Service temporarily unavailable';
                $fallbackSummary['last_error'] = $e->getMessage();
                
                return $fallbackSummary;
            }
            
            // Return regular cached data if available
            if (Cache::has($cacheKey)) {
                $regularCache = Cache::get($cacheKey);
                Log::info('Using regular cached summary as fallback', [
                    'cache_key' => $cacheKey
                ]);
                return $regularCache;
            }
            
            // Last resort: return error summary
            return $this->getErrorFallbackSummary($e);
            
        } catch (Throwable $e) {
            Log::critical('Critical error getting status summary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getErrorFallbackSummary($e);
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

    /**
     * Execute a function with retry logic.
     * 
     * @param callable $callback The function to execute
     * @param int $currentAttempt Current attempt number
     * @return mixed The result of the callback
     * @throws Exception If all retry attempts fail
     */
    private function executeWithRetry(callable $callback, int $currentAttempt = 0)
    {
        $maxAttempts = self::MAX_RETRY_ATTEMPTS;
        $lastException = null;
        
        for ($attempt = $currentAttempt; $attempt < $maxAttempts; $attempt++) {
            try {
                if ($attempt > 0) {
                    Log::debug('Retrying operation', [
                        'attempt' => $attempt + 1,
                        'max_attempts' => $maxAttempts,
                        'delay_ms' => self::RETRY_DELAY_MS
                    ]);
                    
                    // Add delay between retries
                    usleep(self::RETRY_DELAY_MS * 1000);
                }
                
                return $callback();
                
            } catch (Exception $e) {
                $lastException = $e;
                
                Log::warning('Operation failed, will retry if attempts remain', [
                    'attempt' => $attempt + 1,
                    'max_attempts' => $maxAttempts,
                    'error' => $e->getMessage(),
                    'remaining_attempts' => $maxAttempts - $attempt - 1
                ]);
                
                // Don't retry on certain types of errors
                if ($this->shouldNotRetry($e)) {
                    Log::info('Error type should not be retried', [
                        'error_type' => get_class($e),
                        'error_message' => $e->getMessage()
                    ]);
                    break;
                }
            }
        }
        
        // All attempts failed
        Log::error('All retry attempts exhausted', [
            'max_attempts' => $maxAttempts,
            'final_error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);
        
        throw $lastException ?? new Exception('Operation failed after all retry attempts');
    }

    /**
     * Determine if an exception should not be retried.
     * 
     * @param Exception $exception The exception to check
     * @return bool True if the exception should not be retried
     */
    private function shouldNotRetry(Exception $exception): bool
    {
        // Don't retry configuration errors or permanent failures
        $nonRetryableErrors = [
            'InvalidArgumentException',
            'BadMethodCallException',
            'LogicException'
        ];
        
        $exceptionClass = get_class($exception);
        
        foreach ($nonRetryableErrors as $errorClass) {
            if (strpos($exceptionClass, $errorClass) !== false) {
                return true;
            }
        }
        
        // Don't retry if error message indicates a permanent issue
        $message = strtolower($exception->getMessage());
        $permanentErrorIndicators = [
            'access denied',
            'permission denied',
            'authentication failed',
            'invalid credentials',
            'not found',
            'does not exist'
        ];
        
        foreach ($permanentErrorIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get error fallback summary when all else fails.
     * 
     * @param Throwable $exception The exception that caused the failure
     * @return array Fallback summary data
     */
    private function getErrorFallbackSummary(Throwable $exception): array
    {
        Log::warning('Returning error fallback summary', [
            'error' => $exception->getMessage(),
            'error_type' => get_class($exception)
        ]);
        
        return [
            'overall_status' => 'error',
            'completion_percentage' => 0,
            'completed_steps' => 0,
            'total_steps' => 6, // Known number of setup steps
            'incomplete_steps' => ['database', 'mail', 'google_drive', 'migrations', 'admin_user', 'queue_worker'],
            'error_steps' => ['database', 'mail', 'google_drive', 'migrations', 'admin_user', 'queue_worker'],
            'last_updated' => Carbon::now()->toISOString(),
            'error_message' => 'Unable to determine setup status - service temporarily unavailable',
            'technical_error' => $exception->getMessage(),
            'fallback' => true,
            'fallback_reason' => 'All status checks failed'
        ];
    }

    /**
     * Clear all cached status data including fallback caches.
     * 
     * @return void
     */
    public function clearAllCaches(): void
    {
        $cacheKeys = [
            self::CACHE_KEY_PREFIX . '_detailed_statuses',
            self::CACHE_KEY_PREFIX . '_detailed_statuses_fallback',
            self::CACHE_KEY_PREFIX . '_summary',
            self::CACHE_KEY_PREFIX . '_summary_fallback'
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        Log::debug('All setup status caches cleared', [
            'cleared_keys' => $cacheKeys
        ]);
    }

    /**
     * Get cache statistics for debugging.
     * 
     * @return array Cache statistics
     */
    public function getCacheStatistics(): array
    {
        $cacheKeys = [
            'detailed_statuses' => self::CACHE_KEY_PREFIX . '_detailed_statuses',
            'detailed_statuses_fallback' => self::CACHE_KEY_PREFIX . '_detailed_statuses_fallback',
            'summary' => self::CACHE_KEY_PREFIX . '_summary',
            'summary_fallback' => self::CACHE_KEY_PREFIX . '_summary_fallback'
        ];
        
        $stats = [
            'cache_ttl' => self::CACHE_TTL,
            'fallback_cache_ttl' => self::FALLBACK_CACHE_TTL,
            'keys' => []
        ];
        
        foreach ($cacheKeys as $name => $key) {
            $stats['keys'][$name] = [
                'key' => $key,
                'exists' => Cache::has($key),
                'size_bytes' => Cache::has($key) ? strlen(serialize(Cache::get($key))) : 0
            ];
        }
        
        return $stats;
    }

    /**
     * Clear configuration cache if we're in a setup context.
     * This ensures fresh environment variable reads during setup.
     * 
     * @return void
     */
    private function clearConfigurationCacheIfNeeded(): void
    {
        $request = request();
        
        // Check if we're in a setup-related context
        if ($request) {
            $path = $request->path();
            $route = $request->route();
            
            $isSetupContext = str_contains($path, 'setup') || 
                             str_contains($path, 'instructions') ||
                             ($route && $route->getName() && str_contains($route->getName(), 'setup'));
            
            if ($isSetupContext) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('config:clear');
                    Log::debug('Configuration cache cleared during setup status refresh');
                } catch (Exception $e) {
                    Log::warning('Failed to clear configuration cache during setup status refresh', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}