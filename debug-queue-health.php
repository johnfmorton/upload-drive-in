<?php

// Debug script to check queue health metrics in production
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $service = app(\App\Services\QueueTestService::class);
    $health = $service->getQueueHealthMetrics();
    
    echo "=== QUEUE HEALTH DEBUG ===\n";
    echo "Overall Status: " . ($health['overall_status'] ?? 'not set') . "\n";
    echo "Health Message: " . ($health['health_message'] ?? 'not set') . "\n";
    echo "Queue Tables Exist: " . ($health['queue_tables_exist'] ? 'Yes' : 'No') . "\n";
    echo "\n";
    
    echo "=== JOB STATISTICS ===\n";
    $jobStats = $health['job_statistics'] ?? [];
    echo "Pending Jobs: " . ($jobStats['pending_jobs'] ?? 0) . "\n";
    echo "Failed Jobs (Total): " . ($jobStats['failed_jobs_total'] ?? 0) . "\n";
    echo "Failed Jobs (24h): " . ($jobStats['failed_jobs_24h'] ?? 0) . "\n";
    echo "Failed Jobs (1h): " . ($jobStats['failed_jobs_1h'] ?? 0) . "\n";
    echo "Stalled Jobs: " . ($health['stalled_jobs'] ?? 0) . "\n";
    echo "\n";
    
    echo "=== TEST JOB STATISTICS ===\n";
    $testStats = $health['test_job_statistics'] ?? [];
    echo "Total Test Jobs: " . ($testStats['total_test_jobs'] ?? 0) . "\n";
    echo "Test Jobs (1h): " . ($testStats['test_jobs_1h'] ?? 0) . "\n";
    echo "Test Jobs (24h): " . ($testStats['test_jobs_24h'] ?? 0) . "\n";
    echo "\n";
    
    echo "=== RECENT FAILED JOBS ===\n";
    $recentFailed = $health['recent_failed_jobs'] ?? [];
    if (empty($recentFailed)) {
        echo "No recent failed jobs\n";
    } else {
        foreach ($recentFailed as $job) {
            echo "Job ID: " . ($job['id'] ?? 'unknown') . "\n";
            echo "Job Class: " . ($job['job_class'] ?? 'unknown') . "\n";
            echo "Queue: " . ($job['queue'] ?? 'default') . "\n";
            echo "Failed At: " . ($job['failed_at'] ?? 'unknown') . "\n";
            echo "Error: " . ($job['error_summary'] ?? 'no error details') . "\n";
            echo "---\n";
        }
    }
    
    echo "\n=== RECOMMENDATIONS ===\n";
    $recommendations = $health['recommendations'] ?? [];
    if (empty($recommendations)) {
        echo "No recommendations\n";
    } else {
        foreach ($recommendations as $recommendation) {
            echo "- " . $recommendation . "\n";
        }
    }
    
    echo "\n=== RAW METRICS ===\n";
    echo json_encode($health, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}