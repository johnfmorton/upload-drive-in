<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AdminUserSearchOptimizationService
{
    protected SearchPerformanceMonitoringService $monitoringService;

    public function __construct(SearchPerformanceMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }
    /**
     * Build optimized search query for admin user management.
     *
     * @param Request $request
     * @return Builder
     */
    public function buildOptimizedSearchQuery(Request $request): Builder
    {
        $startTime = microtime(true);
        
        // Start with base query using role index
        $query = User::where('role', 'client');
        
        // Handle primary contact filtering with optimized join
        if ($request->has('filter') && $request->get('filter') === 'primary_contact') {
            $currentUser = Auth::user();
            $query->whereHas('companyUsers', function ($q) use ($currentUser) {
                $q->where('company_user_id', $currentUser->id)
                  ->where('is_primary', true);
            });
        }
        
        // Handle search functionality with optimized LIKE queries
        if ($request->has('search') && !empty($request->get('search'))) {
            $searchTerm = trim($request->get('search'));
            
            // Use optimized search with proper indexing
            $query->where(function ($q) use ($searchTerm) {
                // These queries will use the idx_users_role_name_search and idx_users_role_email_search indexes
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
            
            // Record performance metrics for monitoring
            $executionTime = (microtime(true) - $startTime) * 1000;
            $resultCount = $query->count();
            
            $this->monitoringService->recordSearchMetrics(
                $searchTerm,
                $executionTime,
                $resultCount,
                Auth::id()
            );
        }
        
        return $query;
    }

    /**
     * Get search performance metrics.
     *
     * @param string $searchTerm
     * @param float $startTime
     * @return void
     */
    private function logSearchMetrics(string $searchTerm, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        // Log performance metrics
        Log::info('Admin user search performance', [
            'search_term' => $searchTerm,
            'execution_time_ms' => round($executionTime, 2),
            'admin_id' => Auth::id(),
            'timestamp' => now()->toISOString()
        ]);
        
        // Track slow queries (over 100ms)
        if ($executionTime > 100) {
            Log::warning('Slow admin user search query detected', [
                'search_term' => $searchTerm,
                'execution_time_ms' => round($executionTime, 2),
                'admin_id' => Auth::id(),
                'threshold_ms' => 100
            ]);
        }
    }

    /**
     * Analyze query performance and suggest optimizations.
     *
     * @param string $searchTerm
     * @return array
     */
    public function analyzeQueryPerformance(string $searchTerm): array
    {
        $analysis = [];
        
        // Test different query patterns to find the most efficient
        $queries = [
            'name_only' => function() use ($searchTerm) {
                return User::where('role', 'client')
                    ->where('name', 'LIKE', "%{$searchTerm}%");
            },
            'email_only' => function() use ($searchTerm) {
                return User::where('role', 'client')
                    ->where('email', 'LIKE', "%{$searchTerm}%");
            },
            'combined_or' => function() use ($searchTerm) {
                return User::where('role', 'client')
                    ->where(function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                    });
            }
        ];
        
        foreach ($queries as $queryType => $queryBuilder) {
            $startTime = microtime(true);
            $count = $queryBuilder()->count();
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $analysis[$queryType] = [
                'execution_time_ms' => round($executionTime, 2),
                'result_count' => $count
            ];
        }
        
        return $analysis;
    }

    /**
     * Get database index usage statistics.
     *
     * @return array
     */
    public function getIndexUsageStats(): array
    {
        try {
            // This works for MySQL - adapt for other databases as needed
            if (DB::connection()->getDriverName() === 'mysql') {
                $indexStats = DB::select("
                    SELECT 
                        INDEX_NAME,
                        CARDINALITY,
                        SUB_PART,
                        PACKED,
                        NULLABLE,
                        INDEX_TYPE
                    FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'users'
                    AND INDEX_NAME LIKE 'idx_users_%search%'
                    ORDER BY INDEX_NAME, SEQ_IN_INDEX
                ");
                
                return array_map(function($stat) {
                    return [
                        'INDEX_NAME' => $stat->INDEX_NAME,
                        'CARDINALITY' => $stat->CARDINALITY,
                        'SUB_PART' => $stat->SUB_PART,
                        'PACKED' => $stat->PACKED,
                        'NULLABLE' => $stat->NULLABLE,
                        'INDEX_TYPE' => $stat->INDEX_TYPE
                    ];
                }, $indexStats);
            }
            
            return ['message' => 'Index statistics only available for MySQL'];
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve index usage statistics', [
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Failed to retrieve index statistics'];
        }
    }

    /**
     * Optimize search query based on search pattern analysis.
     *
     * @param string $searchTerm
     * @return string
     */
    public function getOptimizedSearchStrategy(string $searchTerm): string
    {
        // Analyze search term characteristics
        $isEmail = filter_var($searchTerm, FILTER_VALIDATE_EMAIL) !== false;
        $containsAt = strpos($searchTerm, '@') !== false;
        $isNumeric = is_numeric($searchTerm);
        $length = strlen($searchTerm);
        
        if ($isEmail || $containsAt) {
            return 'email_focused';
        }
        
        if ($isNumeric && $length < 5) {
            return 'id_focused';
        }
        
        if ($length < 3) {
            return 'prefix_search';
        }
        
        return 'full_text_search';
    }

    /**
     * Cache frequently searched terms for performance.
     *
     * @param string $searchTerm
     * @param mixed $results
     * @return void
     */
    public function cacheSearchResults(string $searchTerm, $results): void
    {
        $cacheKey = 'admin_user_search:' . md5($searchTerm . Auth::id());
        
        // Cache for 5 minutes
        Cache::put($cacheKey, $results, 300);
        
        Log::debug('Cached admin user search results', [
            'search_term' => $searchTerm,
            'cache_key' => $cacheKey,
            'admin_id' => Auth::id()
        ]);
    }

    /**
     * Get cached search results if available.
     *
     * @param string $searchTerm
     * @return mixed|null
     */
    public function getCachedSearchResults(string $searchTerm)
    {
        $cacheKey = 'admin_user_search:' . md5($searchTerm . Auth::id());
        
        return Cache::get($cacheKey);
    }

    /**
     * Generate performance report for search operations.
     *
     * @return array
     */
    public function generatePerformanceReport(): array
    {
        $report = [
            'timestamp' => now()->toISOString(),
            'database_driver' => DB::connection()->getDriverName(),
            'indexes' => $this->getIndexUsageStats(),
            'recommendations' => []
        ];
        
        // Add performance recommendations
        $report['recommendations'] = [
            'Use specific search patterns when possible (email vs name)',
            'Consider implementing search result caching for frequent queries',
            'Monitor slow query log for searches taking over 100ms',
            'Consider full-text search indexes for very large datasets'
        ];
        
        return $report;
    }
}