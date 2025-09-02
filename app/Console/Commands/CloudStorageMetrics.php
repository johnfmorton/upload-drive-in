<?php

namespace App\Console\Commands;

use App\Services\CloudStorageLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CloudStorageMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-storage:metrics 
                            {provider=google-drive : The cloud storage provider}
                            {--hours=24 : Number of hours to look back for metrics}
                            {--format=table : Output format (table, json)}
                            {--export= : Export metrics to file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display cloud storage metrics and monitoring information';

    public function __construct(
        private readonly CloudStorageLogService $logService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $provider = $this->argument('provider');
        $hours = (int) $this->option('hours');
        $format = $this->option('format');
        $exportFile = $this->option('export');

        $this->info("Cloud Storage Metrics Report");
        $this->info("Provider: {$provider}");
        $this->info("Time Range: Last {$hours} hours");
        $this->newLine();

        try {
            $metrics = $this->logService->getMetricsSummary($provider, $hours);
            
            if ($format === 'json') {
                $this->displayJsonMetrics($metrics);
            } else {
                $this->displayTableMetrics($metrics, $provider);
            }

            if ($exportFile) {
                $this->exportMetrics($metrics, $exportFile, $format);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to retrieve metrics: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Display metrics in table format.
     */
    private function displayTableMetrics(array $metrics, string $provider): void
    {
        // Token Refresh Metrics
        $this->info('🔄 Token Refresh Metrics');
        $tokenMetrics = $metrics['token_refresh'];
        $this->table(
            ['Metric', 'Value'],
            [
                ['Attempts', $tokenMetrics['attempts']],
                ['Successes', $tokenMetrics['successes']],
                ['Failures', $tokenMetrics['failures']],
                ['Success Rate', number_format($tokenMetrics['success_rate'] * 100, 2) . '%'],
            ]
        );
        $this->newLine();

        // API Connectivity Metrics
        $this->info('🌐 API Connectivity Metrics');
        $apiMetrics = $metrics['api_connectivity'];
        $this->table(
            ['Metric', 'Value'],
            [
                ['Successful Tests', $apiMetrics['successes']],
                ['Failed Tests', $apiMetrics['failures']],
                ['Total Tests', $apiMetrics['successes'] + $apiMetrics['failures']],
                ['Success Rate', $this->calculateSuccessRate($apiMetrics['successes'], $apiMetrics['failures'])],
            ]
        );
        $this->newLine();

        // Status Distribution
        $this->info('📊 Status Distribution');
        $statusData = [];
        foreach ($metrics['status_distribution'] as $status => $count) {
            $statusData[] = [ucfirst(str_replace('_', ' ', $status)), $count];
        }
        $this->table(['Status', 'Frequency'], $statusData);
        $this->newLine();

        // Cache Performance
        $this->info('💾 Cache Performance');
        $cacheMetrics = $metrics['cache_performance'];
        $totalCacheOps = $cacheMetrics['hits'] + $cacheMetrics['misses'];
        $hitRate = $totalCacheOps > 0 ? ($cacheMetrics['hits'] / $totalCacheOps) * 100 : 0;
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Hits', $cacheMetrics['hits']],
                ['Cache Misses', $cacheMetrics['misses']],
                ['Total Operations', $totalCacheOps],
                ['Hit Rate', number_format($hitRate, 2) . '%'],
            ]
        );
        $this->newLine();

        // Health Summary
        $this->displayHealthSummary($metrics, $provider);
    }

    /**
     * Display metrics in JSON format.
     */
    private function displayJsonMetrics(array $metrics): void
    {
        $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
    }

    /**
     * Display health summary with recommendations.
     */
    private function displayHealthSummary(array $metrics, string $provider): void
    {
        $this->info('🏥 Health Summary & Recommendations');
        
        $tokenMetrics = $metrics['token_refresh'];
        $apiMetrics = $metrics['api_connectivity'];
        $statusMetrics = $metrics['status_distribution'];
        
        $recommendations = [];
        
        // Token refresh health
        if ($tokenMetrics['success_rate'] < 0.9 && $tokenMetrics['attempts'] > 0) {
            $recommendations[] = "⚠️  Token refresh success rate is low ({$this->formatPercentage($tokenMetrics['success_rate'])}). Check Google Drive API credentials.";
        } elseif ($tokenMetrics['success_rate'] >= 0.95) {
            $recommendations[] = "✅ Token refresh is working well ({$this->formatPercentage($tokenMetrics['success_rate'])} success rate).";
        }
        
        // API connectivity health
        $apiTotal = $apiMetrics['successes'] + $apiMetrics['failures'];
        if ($apiTotal > 0) {
            $apiSuccessRate = $apiMetrics['successes'] / $apiTotal;
            if ($apiSuccessRate < 0.9) {
                $recommendations[] = "⚠️  API connectivity issues detected ({$this->formatPercentage($apiSuccessRate)} success rate). Check network connectivity.";
            } elseif ($apiSuccessRate >= 0.95) {
                $recommendations[] = "✅ API connectivity is stable ({$this->formatPercentage($apiSuccessRate)} success rate).";
            }
        }
        
        // Status distribution analysis
        $totalStatusChecks = array_sum($statusMetrics);
        if ($totalStatusChecks > 0) {
            $healthyPercentage = ($statusMetrics['healthy'] ?? 0) / $totalStatusChecks;
            if ($healthyPercentage < 0.8) {
                $recommendations[] = "⚠️  System is frequently unhealthy ({$this->formatPercentage($healthyPercentage)} healthy status). Review error logs.";
            } elseif ($healthyPercentage >= 0.9) {
                $recommendations[] = "✅ System maintains good health ({$this->formatPercentage($healthyPercentage)} healthy status).";
            }
            
            if (($statusMetrics['authentication_required'] ?? 0) > 0) {
                $authIssues = $statusMetrics['authentication_required'] / $totalStatusChecks;
                $recommendations[] = "🔐 Authentication issues detected ({$this->formatPercentage($authIssues)} of checks). Users may need to reconnect.";
            }
        }
        
        // Cache performance
        $cacheMetrics = $metrics['cache_performance'];
        $totalCacheOps = $cacheMetrics['hits'] + $cacheMetrics['misses'];
        if ($totalCacheOps > 0) {
            $hitRate = $cacheMetrics['hits'] / $totalCacheOps;
            if ($hitRate < 0.7) {
                $recommendations[] = "💾 Cache hit rate is low ({$this->formatPercentage($hitRate)}). Consider adjusting cache TTL.";
            } elseif ($hitRate >= 0.8) {
                $recommendations[] = "✅ Cache is performing well ({$this->formatPercentage($hitRate)} hit rate).";
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = "ℹ️  No specific recommendations at this time. Monitor regularly.";
        }
        
        foreach ($recommendations as $recommendation) {
            $this->line($recommendation);
        }
    }

    /**
     * Export metrics to file.
     */
    private function exportMetrics(array $metrics, string $filename, string $format): void
    {
        try {
            $data = $format === 'json' 
                ? json_encode($metrics, JSON_PRETTY_PRINT)
                : $this->formatMetricsAsCsv($metrics);
                
            file_put_contents($filename, $data);
            $this->info("Metrics exported to: {$filename}");
        } catch (\Exception $e) {
            $this->error("Failed to export metrics: {$e->getMessage()}");
        }
    }

    /**
     * Format metrics as CSV.
     */
    private function formatMetricsAsCsv(array $metrics): string
    {
        $csv = "Metric Category,Metric Name,Value\n";
        
        // Token refresh metrics
        foreach ($metrics['token_refresh'] as $key => $value) {
            $csv .= "Token Refresh," . ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
        }
        
        // API connectivity metrics
        foreach ($metrics['api_connectivity'] as $key => $value) {
            $csv .= "API Connectivity," . ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
        }
        
        // Status distribution
        foreach ($metrics['status_distribution'] as $status => $count) {
            $csv .= "Status Distribution," . ucfirst(str_replace('_', ' ', $status)) . "," . $count . "\n";
        }
        
        // Cache performance
        foreach ($metrics['cache_performance'] as $key => $value) {
            $csv .= "Cache Performance," . ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
        }
        
        return $csv;
    }

    /**
     * Calculate success rate as formatted percentage.
     */
    private function calculateSuccessRate(int $successes, int $failures): string
    {
        $total = $successes + $failures;
        if ($total === 0) {
            return 'N/A';
        }
        
        return number_format(($successes / $total) * 100, 2) . '%';
    }

    /**
     * Format decimal as percentage.
     */
    private function formatPercentage(float $decimal): string
    {
        return number_format($decimal * 100, 1) . '%';
    }
}