<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminUserSearchOptimizationService;
use App\Services\SearchPerformanceMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminUserSearchOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUserSearchOptimizationService $searchService;
    protected SearchPerformanceMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->searchService = app(AdminUserSearchOptimizationService::class);
        $this->monitoringService = app(SearchPerformanceMonitoringService::class);
    }

    /** @test */
    public function it_builds_optimized_search_query_for_name_search()
    {
        // Create test users
        $admin = User::factory()->create(['role' => 'admin']);
        $client1 = User::factory()->create(['role' => 'client', 'name' => 'John Doe']);
        $client2 = User::factory()->create(['role' => 'client', 'name' => 'Jane Smith']);
        $client3 = User::factory()->create(['role' => 'client', 'name' => 'Bob Johnson']);

        Auth::login($admin);

        $request = new Request(['search' => 'John']);
        
        $query = $this->searchService->buildOptimizedSearchQuery($request);
        $results = $query->get();

        $this->assertCount(2, $results); // John Doe and Bob Johnson
        $this->assertTrue($results->contains('name', 'John Doe'));
        $this->assertTrue($results->contains('name', 'Bob Johnson'));
    }

    /** @test */
    public function it_builds_optimized_search_query_for_email_search()
    {
        // Create test users
        $admin = User::factory()->create(['role' => 'admin']);
        $client1 = User::factory()->create(['role' => 'client', 'email' => 'john@example.com']);
        $client2 = User::factory()->create(['role' => 'client', 'email' => 'jane@test.com']);
        $client3 = User::factory()->create(['role' => 'client', 'email' => 'bob@example.com']);

        Auth::login($admin);

        $request = new Request(['search' => 'example.com']);
        
        $query = $this->searchService->buildOptimizedSearchQuery($request);
        $results = $query->get();

        $this->assertCount(2, $results); // john@example.com and bob@example.com
        $this->assertTrue($results->contains('email', 'john@example.com'));
        $this->assertTrue($results->contains('email', 'bob@example.com'));
    }

    /** @test */
    public function it_handles_combined_name_and_email_search()
    {
        // Create test users
        $admin = User::factory()->create(['role' => 'admin']);
        $client1 = User::factory()->create(['role' => 'client', 'name' => 'Test User', 'email' => 'user@example.com']);
        $client2 = User::factory()->create(['role' => 'client', 'name' => 'John Doe', 'email' => 'test@domain.com']);
        $client3 = User::factory()->create(['role' => 'client', 'name' => 'Jane Smith', 'email' => 'jane@example.com']);

        Auth::login($admin);

        $request = new Request(['search' => 'test']);
        
        $query = $this->searchService->buildOptimizedSearchQuery($request);
        $results = $query->get();

        $this->assertCount(2, $results); // Test User (name) and test@domain.com (email)
        $this->assertTrue($results->contains('name', 'Test User'));
        $this->assertTrue($results->contains('email', 'test@domain.com'));
    }

    /** @test */
    public function it_filters_by_role_correctly()
    {
        // Create test users with different roles
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client', 'name' => 'Test Client']);
        $employee = User::factory()->create(['role' => 'employee', 'name' => 'Test Employee']);

        Auth::login($admin);

        $request = new Request(['search' => 'Test']);
        
        $query = $this->searchService->buildOptimizedSearchQuery($request);
        $results = $query->get();

        $this->assertCount(1, $results); // Only the client should be returned
        $this->assertEquals('client', $results->first()->role->value);
        $this->assertEquals('Test Client', $results->first()->name);
    }

    /** @test */
    public function it_records_performance_metrics()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client', 'name' => 'Test User']);

        Auth::login($admin);

        // Clear any existing metrics
        $this->monitoringService->clearMetrics();

        $request = new Request(['search' => 'Test']);
        
        // Build query which should record metrics
        $query = $this->searchService->buildOptimizedSearchQuery($request);
        $results = $query->get();

        // Check that metrics were recorded
        $stats = $this->monitoringService->getPerformanceStats();
        
        $this->assertGreaterThan(0, $stats['total_searches']);
    }

    /** @test */
    public function it_provides_optimization_recommendations()
    {
        $recommendations = $this->monitoringService->getOptimizationRecommendations();
        
        $this->assertIsArray($recommendations);
        // With no search data, there should be no specific recommendations
        $this->assertEmpty($recommendations);
    }

    /** @test */
    public function it_analyzes_query_performance()
    {
        // Create test data
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(10)->create(['role' => 'client']);

        Auth::login($admin);

        $analysis = $this->searchService->analyzeQueryPerformance('test');
        
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('name_only', $analysis);
        $this->assertArrayHasKey('email_only', $analysis);
        $this->assertArrayHasKey('combined_or', $analysis);
        
        foreach ($analysis as $queryType => $metrics) {
            $this->assertArrayHasKey('execution_time_ms', $metrics);
            $this->assertArrayHasKey('result_count', $metrics);
            $this->assertIsNumeric($metrics['execution_time_ms']);
            $this->assertIsInt($metrics['result_count']);
        }
    }

    /** @test */
    public function it_determines_optimal_search_strategy()
    {
        $emailStrategy = $this->searchService->getOptimizedSearchStrategy('user@example.com');
        $this->assertEquals('email_focused', $emailStrategy);

        $partialEmailStrategy = $this->searchService->getOptimizedSearchStrategy('user@');
        $this->assertEquals('email_focused', $partialEmailStrategy);

        $numericStrategy = $this->searchService->getOptimizedSearchStrategy('123');
        $this->assertEquals('id_focused', $numericStrategy);

        $shortStrategy = $this->searchService->getOptimizedSearchStrategy('ab');
        $this->assertEquals('prefix_search', $shortStrategy);

        $fullTextStrategy = $this->searchService->getOptimizedSearchStrategy('John Doe');
        $this->assertEquals('full_text_search', $fullTextStrategy);
    }

    /** @test */
    public function it_handles_empty_search_terms()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(5)->create(['role' => 'client']);

        Auth::login($admin);

        $request = new Request(['search' => '']);
        
        $query = $this->searchService->buildOptimizedSearchQuery($request);
        $results = $query->get();

        // Should return all clients when search is empty
        $this->assertCount(5, $results);
    }

    /** @test */
    public function it_handles_whitespace_only_search_terms()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create(['role' => 'client']);

        Auth::login($admin);

        $request = new Request(['search' => '   ']);
        
        $query = $this->searchService->buildOptimizedSearchQuery($request);
        $results = $query->get();

        // Should return all clients when search is only whitespace
        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_generates_performance_report()
    {
        $report = $this->searchService->generatePerformanceReport();
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('timestamp', $report);
        $this->assertArrayHasKey('database_driver', $report);
        $this->assertArrayHasKey('indexes', $report);
        $this->assertArrayHasKey('recommendations', $report);
        
        $this->assertIsArray($report['recommendations']);
        $this->assertNotEmpty($report['recommendations']);
    }

    /** @test */
    public function it_gets_database_insights()
    {
        $insights = $this->monitoringService->getDatabaseInsights();
        
        $this->assertIsArray($insights);
        // The exact structure depends on the database driver
        // For testing, we just ensure it returns an array without errors
    }
}