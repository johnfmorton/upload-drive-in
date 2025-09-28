<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AdminUserSearchOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TestAdminUserSearchPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:test-search-performance 
                            {--create-test-data : Create test data for performance testing}
                            {--test-count=1000 : Number of test users to create}
                            {--search-terms=* : Specific search terms to test}
                            {--cleanup : Remove test data after testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test admin user search performance with various dataset sizes and search patterns';

    protected AdminUserSearchOptimizationService $searchService;

    public function __construct(AdminUserSearchOptimizationService $searchService)
    {
        parent::__construct();
        $this->searchService = $searchService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Admin User Search Performance Testing');
        $this->newLine();

        if ($this->option('create-test-data')) {
            $this->createTestData();
        }

        $this->runPerformanceTests();

        if ($this->option('cleanup')) {
            $this->cleanupTestData();
        }

        $this->generatePerformanceReport();
    }

    /**
     * Create test data for performance testing.
     */
    private function createTestData(): void
    {
        $testCount = (int) $this->option('test-count');
        
        $this->info("Creating {$testCount} test users for performance testing...");
        
        $progressBar = $this->output->createProgressBar($testCount);
        $progressBar->start();

        $batchSize = 100;
        $batches = ceil($testCount / $batchSize);

        for ($batch = 0; $batch < $batches; $batch++) {
            $users = [];
            $currentBatchSize = min($batchSize, $testCount - ($batch * $batchSize));

            for ($i = 0; $i < $currentBatchSize; $i++) {
                $users[] = [
                    'name' => 'Test User ' . Str::random(10),
                    'email' => 'test' . Str::random(8) . '@example.com',
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                    'role' => 'client',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $progressBar->advance();
            }

            User::insert($users);
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Created {$testCount} test users");
    }

    /**
     * Run performance tests with various search patterns.
     */
    private function runPerformanceTests(): void
    {
        $this->info('Running performance tests...');
        $this->newLine();

        $totalUsers = User::where('role', 'client')->count();
        $this->info("Total client users in database: {$totalUsers}");
        $this->newLine();

        // Default search terms if none provided
        $searchTerms = $this->option('search-terms') ?: [
            'test',           // Common prefix
            'user',           // Common word
            '@example.com',   // Email domain
            'Test User',      // Full name pattern
            'test123',        // Alphanumeric
            'nonexistent',    // No results
        ];

        $results = [];

        foreach ($searchTerms as $searchTerm) {
            $this->info("Testing search term: '{$searchTerm}'");
            
            $testResults = $this->testSearchTerm($searchTerm);
            $results[$searchTerm] = $testResults;
            
            $this->table(
                ['Query Type', 'Execution Time (ms)', 'Result Count'],
                array_map(function($type, $data) {
                    return [$type, $data['execution_time_ms'], $data['result_count']];
                }, array_keys($testResults), $testResults)
            );
            
            $this->newLine();
        }

        // Test pagination performance
        $this->testPaginationPerformance();
    }

    /**
     * Test a specific search term with different query patterns.
     */
    private function testSearchTerm(string $searchTerm): array
    {
        $results = [];

        // Test name-only search
        $startTime = microtime(true);
        $nameCount = User::where('role', 'client')
            ->where('name', 'LIKE', "%{$searchTerm}%")
            ->count();
        $nameTime = (microtime(true) - $startTime) * 1000;

        $results['name_only'] = [
            'execution_time_ms' => round($nameTime, 2),
            'result_count' => $nameCount
        ];

        // Test email-only search
        $startTime = microtime(true);
        $emailCount = User::where('role', 'client')
            ->where('email', 'LIKE', "%{$searchTerm}%")
            ->count();
        $emailTime = (microtime(true) - $startTime) * 1000;

        $results['email_only'] = [
            'execution_time_ms' => round($emailTime, 2),
            'result_count' => $emailCount
        ];

        // Test combined OR search (current implementation)
        $startTime = microtime(true);
        $combinedCount = User::where('role', 'client')
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            })
            ->count();
        $combinedTime = (microtime(true) - $startTime) * 1000;

        $results['combined_or'] = [
            'execution_time_ms' => round($combinedTime, 2),
            'result_count' => $combinedCount
        ];

        // Log performance metrics
        Log::info('Search performance test results', [
            'search_term' => $searchTerm,
            'results' => $results,
            'total_users' => User::where('role', 'client')->count()
        ]);

        return $results;
    }

    /**
     * Test pagination performance with search.
     */
    private function testPaginationPerformance(): void
    {
        $this->info('Testing pagination performance...');

        $searchTerm = 'test';
        $perPage = 15;
        $pages = [1, 5, 10, 20];

        $paginationResults = [];

        foreach ($pages as $page) {
            $startTime = microtime(true);
            
            $results = User::where('role', 'client')
                ->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                })
                ->paginate($perPage, ['*'], 'page', $page);
                
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $paginationResults[] = [
                "Page {$page}",
                round($executionTime, 2),
                $results->count(),
                $results->total()
            ];
        }

        $this->table(
            ['Page', 'Execution Time (ms)', 'Items on Page', 'Total Results'],
            $paginationResults
        );

        $this->newLine();
    }

    /**
     * Clean up test data.
     */
    private function cleanupTestData(): void
    {
        $this->info('Cleaning up test data...');
        
        $deletedCount = User::where('role', 'client')
            ->where('email', 'LIKE', 'test%@example.com')
            ->delete();
            
        $this->info("✅ Deleted {$deletedCount} test users");
    }

    /**
     * Generate comprehensive performance report.
     */
    private function generatePerformanceReport(): void
    {
        $this->info('Generating performance report...');
        $this->newLine();

        $report = $this->searchService->generatePerformanceReport();

        $this->info('📊 Performance Report');
        $this->info('==================');
        $this->info("Database Driver: {$report['database_driver']}");
        $this->info("Generated: {$report['timestamp']}");
        $this->newLine();

        if (!empty($report['indexes']) && is_array($report['indexes'])) {
            $this->info('📈 Search Indexes:');
            foreach ($report['indexes'] as $index) {
                if (is_array($index) && isset($index['INDEX_NAME'], $index['CARDINALITY'])) {
                    $this->line("  - {$index['INDEX_NAME']} (Cardinality: {$index['CARDINALITY']})");
                }
            }
            $this->newLine();
        }

        $this->info('💡 Recommendations:');
        foreach ($report['recommendations'] as $recommendation) {
            $this->line("  • {$recommendation}");
        }
        $this->newLine();

        // Check for slow queries in recent logs
        $this->checkRecentSlowQueries();
    }

    /**
     * Check for recent slow queries in logs.
     */
    private function checkRecentSlowQueries(): void
    {
        $this->info('🐌 Recent Slow Query Analysis:');
        
        // This is a simplified check - in production you might want to check actual log files
        $this->line('  Check Laravel logs for entries with "Slow admin user search query detected"');
        $this->line('  Monitor queries taking over 100ms for optimization opportunities');
        $this->newLine();
    }
}