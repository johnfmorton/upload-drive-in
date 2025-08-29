<?php

namespace App\Http\Middleware;

use App\Services\QueueWorkerPerformanceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to add performance monitoring headers for queue worker requests.
 * 
 * This middleware adds performance metrics and cache statistics to responses
 * for queue worker related endpoints to help with performance monitoring.
 */
class QueueWorkerPerformanceHeaders
{
    private QueueWorkerPerformanceService $performanceService;

    public function __construct(QueueWorkerPerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        // Calculate request processing time
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Only add headers for queue worker related requests
        if ($this->isQueueWorkerRequest($request)) {
            $this->addPerformanceHeaders($response, $processingTime, $request);
        }
        
        // Log slow requests for monitoring
        if ($processingTime > 2000) { // 2 seconds
            Log::warning('Slow queue worker request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'processing_time_ms' => $processingTime,
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);
        }
        
        return $response;
    }

    /**
     * Check if the request is related to queue worker functionality.
     */
    private function isQueueWorkerRequest(Request $request): bool
    {
        $queueWorkerPaths = [
            '/setup/queue',
            '/setup/queue-worker',
            '/admin/queue',
            '/employee/queue'
        ];

        $path = $request->path();
        
        foreach ($queueWorkerPaths as $queuePath) {
            if (str_starts_with($path, trim($queuePath, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add performance monitoring headers to the response.
     */
    private function addPerformanceHeaders(Response $response, float $processingTime, Request $request): void
    {
        try {
            // Add basic performance headers
            $response->headers->set('X-Queue-Worker-Processing-Time', $processingTime . 'ms');
            $response->headers->set('X-Queue-Worker-Timestamp', now()->toISOString());
            
            // Add cache performance statistics for debugging
            if (config('app.debug') || $request->hasHeader('X-Debug-Performance')) {
                $cacheStats = $this->performanceService->getCachePerformanceStats();
                
                $response->headers->set('X-Queue-Worker-Cache-Driver', $cacheStats['cache_driver'] ?? 'unknown');
                $response->headers->set('X-Queue-Worker-Index-Size', (string)($cacheStats['job_index_size'] ?? 0));
                $response->headers->set('X-Queue-Worker-Cache-Usage', (string)($cacheStats['estimated_cache_usage'] ?? 0));
                
                // Add cleanup recommendations if any
                if (!empty($cacheStats['cleanup_recommendations'])) {
                    $response->headers->set('X-Queue-Worker-Cleanup-Needed', 'true');
                    $response->headers->set('X-Queue-Worker-Recommendations', implode('; ', $cacheStats['cleanup_recommendations']));
                }
            }
            
            // Add rate limiting information if applicable
            if ($request->hasHeader('X-RateLimit-Remaining')) {
                $response->headers->set('X-Queue-Worker-Rate-Limit', $request->header('X-RateLimit-Remaining'));
            }
            
        } catch (\Exception $e) {
            // Don't let header addition failures break the response
            Log::warning('Failed to add queue worker performance headers', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl()
            ]);
        }
    }
}