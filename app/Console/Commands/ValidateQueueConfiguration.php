<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\Jobs\Job;
use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use Exception;

class ValidateQueueConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'validate:queue 
                            {--connection= : Specific queue connection to test (sync, database, redis)}
                            {--all : Test all available queue connections}
                            {--dispatch-test : Dispatch a test job to validate job processing}';

    /**
     * The console command description.
     */
    protected $description = 'Validate queue configuration and test queue connectivity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Queue Configuration Validation');
        $this->newLine();

        $connection = $this->option('connection');
        $testAll = $this->option('all');
        $dispatchTest = $this->option('dispatch-test');

        if ($testAll) {
            return $this->testAllConnections($dispatchTest);
        }

        if ($connection) {
            return $this->testSpecificConnection($connection, $dispatchTest);
        }

        // Test current default connection
        $currentConnection = config('queue.default');
        $this->info("Testing current queue connection: <fg=yellow>{$currentConnection}</>");
        $this->newLine();

        return $this->testQueueConnection($currentConnection, $dispatchTest);
    }

    /**
     * Test all available queue connections
     */
    private function testAllConnections(bool $dispatchTest = false): int
    {
        $connections = ['sync', 'database', 'redis'];
        $results = [];

        foreach ($connections as $connection) {
            $this->info("Testing <fg=yellow>{$connection}</> queue connection...");
            $result = $this->testQueueConnection($connection, false, false);
            $results[$connection] = $result === Command::SUCCESS;
            $this->newLine();
        }

        // Summary table
        $this->info('📊 Queue Connection Test Results:');
        $tableData = [];
        foreach ($results as $connection => $success) {
            $status = $success ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>';
            $tableData[] = [ucfirst($connection), $status];
        }

        $this->table(['Connection', 'Status'], $tableData);

        // Optional job dispatch test on working connections
        if ($dispatchTest) {
            $workingConnections = array_keys(array_filter($results));
            if (!empty($workingConnections)) {
                $this->newLine();
                $this->info('🚀 Testing job dispatch on working connections...');
                foreach ($workingConnections as $connection) {
                    $this->testJobDispatch($connection);
                }
            }
        }

        return in_array(false, $results) ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Test a specific queue connection
     */
    private function testSpecificConnection(string $connection, bool $dispatchTest = false): int
    {
        $this->info("Testing <fg=yellow>{$connection}</> queue connection...");
        $this->newLine();

        $result = $this->testQueueConnection($connection, $dispatchTest);

        if ($result === Command::SUCCESS && $dispatchTest) {
            $this->newLine();
            $this->testJobDispatch($connection);
        }

        return $result;
    }

    /**
     * Test queue connection functionality
     */
    private function testQueueConnection(string $connection, bool $dispatchTest = false, bool $showDetails = true): int
    {
        try {
            // Check if connection is configured
            if (!$this->isConnectionConfigured($connection)) {
                if ($showDetails) {
                    $this->error("❌ {$connection} queue connection is not properly configured");
                    $this->showConnectionConfiguration($connection);
                }
                return Command::FAILURE;
            }

            // Test basic queue operations
            $originalConnection = config('queue.default');
            Config::set('queue.default', $connection);

            // Test queue size (if supported)
            try {
                $queueSize = Queue::size();
                if ($showDetails) {
                    $this->info("✓ Queue size check successful (current size: {$queueSize})");
                }
            } catch (Exception $e) {
                if ($showDetails) {
                    $this->info("⚠ Queue size check not supported for {$connection} driver");
                }
            }

            // Test connection-specific functionality
            $this->testConnectionSpecificFeatures($connection, $showDetails);

            // Restore original connection
            Config::set('queue.default', $originalConnection);

            if ($showDetails) {
                $this->info("🎉 <fg=green>{$connection} queue connection is working correctly!</>");
            }

            return Command::SUCCESS;

        } catch (Exception $e) {
            // Restore original connection on error
            if (isset($originalConnection)) {
                Config::set('queue.default', $originalConnection);
            }

            if ($showDetails) {
                $this->error("❌ {$connection} queue connection test failed: " . $e->getMessage());
                $this->showTroubleshootingGuide($connection, $e);
            }

            return Command::FAILURE;
        }
    }

    /**
     * Test connection-specific features
     */
    private function testConnectionSpecificFeatures(string $connection, bool $showDetails): void
    {
        switch ($connection) {
            case 'sync':
                if ($showDetails) {
                    $this->info("✓ Sync driver ready (jobs execute immediately)");
                }
                break;

            case 'database':
                $table = config('queue.connections.database.table', 'jobs');
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    throw new Exception("Jobs table '{$table}' does not exist");
                }
                if ($showDetails) {
                    $this->info("✓ Jobs table '{$table}' exists and is accessible");
                }

                // Check failed jobs table
                $failedTable = config('queue.failed.table', 'failed_jobs');
                if (DB::getSchemaBuilder()->hasTable($failedTable)) {
                    if ($showDetails) {
                        $this->info("✓ Failed jobs table '{$failedTable}' exists");
                    }
                } else {
                    if ($showDetails) {
                        $this->warn("⚠ Failed jobs table '{$failedTable}' does not exist");
                    }
                }
                break;

            case 'redis':
                Redis::connection()->ping();
                if ($showDetails) {
                    $this->info("✓ Redis connection successful");
                }
                break;
        }
    }

    /**
     * Test job dispatch and processing
     */
    private function testJobDispatch(string $connection): void
    {
        $this->info("🚀 Testing job dispatch on <fg=yellow>{$connection}</> connection...");

        try {
            // Create a test file upload record (we'll clean it up)
            $testUpload = new FileUpload([
                'filename' => 'test_validation_file.txt',
                'original_filename' => 'test_validation_file.txt',
                'mime_type' => 'text/plain',
                'file_size' => 100,
                'email' => 'test@validation.com',
                'storage_provider' => 'local',
                'validation_method' => 'email',
                'message' => 'Queue validation test'
            ]);

            // Don't save to database for sync connection (immediate execution)
            if ($connection !== 'sync') {
                $testUpload->save();
            }

            // Temporarily switch to test connection
            $originalConnection = config('queue.default');
            Config::set('queue.default', $connection);

            if ($connection === 'sync') {
                $this->info("⚠ Sync connection executes jobs immediately - skipping dispatch test");
                $this->info("✓ Sync connection is suitable for development/testing");
            } else {
                // Dispatch the job
                UploadToGoogleDrive::dispatch($testUpload);
                $this->info("✓ Job dispatched successfully to {$connection} queue");

                // Check if job was queued (for database driver)
                if ($connection === 'database') {
                    $table = config('queue.connections.database.table', 'jobs');
                    $jobCount = DB::table($table)->count();
                    $this->info("✓ Job added to queue (total jobs in queue: {$jobCount})");
                }

                $this->warn("⚠ Job was queued but not processed (run 'php artisan queue:work' to process)");
                
                // Clean up test record
                if ($testUpload->exists) {
                    $testUpload->delete();
                    $this->info("✓ Test record cleaned up");
                }
            }

            // Restore original connection
            Config::set('queue.default', $originalConnection);

        } catch (Exception $e) {
            // Restore original connection on error
            if (isset($originalConnection)) {
                Config::set('queue.default', $originalConnection);
            }

            // Clean up test record on error
            if (isset($testUpload) && $testUpload->exists) {
                $testUpload->delete();
            }

            $this->error("❌ Job dispatch test failed: " . $e->getMessage());
        }
    }

    /**
     * Check if a queue connection is properly configured
     */
    private function isConnectionConfigured(string $connection): bool
    {
        $connections = config('queue.connections');
        
        if (!isset($connections[$connection])) {
            return false;
        }

        switch ($connection) {
            case 'sync':
                return true; // Sync driver is always available

            case 'database':
                try {
                    $table = $connections['database']['table'] ?? 'jobs';
                    return DB::getSchemaBuilder()->hasTable($table);
                } catch (Exception $e) {
                    return false;
                }

            case 'redis':
                try {
                    Redis::connection()->ping();
                    return true;
                } catch (Exception $e) {
                    return false;
                }

            default:
                return true;
        }
    }

    /**
     * Show connection configuration details
     */
    private function showConnectionConfiguration(string $connection): void
    {
        $this->newLine();
        $this->info("📋 Configuration for <fg=yellow>{$connection}</> connection:");

        switch ($connection) {
            case 'sync':
                $this->info("  Driver: sync (immediate execution)");
                $this->info("  Best for: Development and testing");
                break;

            case 'database':
                $table = config('queue.connections.database.table', 'jobs');
                $hasTable = DB::getSchemaBuilder()->hasTable($table);
                $this->info("  Driver: database");
                $this->info("  Table: {$table}");
                $this->info("  Table exists: " . ($hasTable ? 'Yes' : 'No'));
                break;

            case 'redis':
                $host = config('database.redis.default.host', env('REDIS_HOST', '127.0.0.1'));
                $port = config('database.redis.default.port', env('REDIS_PORT', 6379));
                $this->info("  Driver: redis");
                $this->info("  Host: {$host}");
                $this->info("  Port: {$port}");
                break;
        }
    }

    /**
     * Show troubleshooting guide for failed connections
     */
    private function showTroubleshootingGuide(string $connection, Exception $e): void
    {
        $this->newLine();
        $this->error("🔧 Troubleshooting Guide for {$connection}:");

        switch ($connection) {
            case 'sync':
                $this->line("  • Sync driver should always work");
                $this->line("  • Check if there are any application-level errors");
                break;

            case 'database':
                $this->line("  • Ensure jobs table exists: php artisan queue:table && php artisan migrate");
                $this->line("  • Check database connection configuration");
                $this->line("  • Verify DB_CONNECTION, DB_HOST, DB_DATABASE settings in .env");
                $this->line("  • Create failed jobs table: php artisan queue:failed-table && php artisan migrate");
                break;

            case 'redis':
                $this->line("  • Ensure Redis server is running");
                $this->line("  • For DDEV: Add Redis service with docker-compose.redis.yaml");
                $this->line("  • Check REDIS_HOST, REDIS_PORT, REDIS_PASSWORD in .env");
                $this->line("  • Test connection: redis-cli ping");
                $this->line("  • Verify REDIS_QUEUE_CONNECTION setting");
                break;
        }

        $this->newLine();
        $this->line("Error details: " . $e->getMessage());
        
        $this->newLine();
        $this->info("💡 Queue Processing Commands:");
        $this->line("  • Process jobs: php artisan queue:work");
        $this->line("  • View failed jobs: php artisan queue:failed");
        $this->line("  • Retry failed jobs: php artisan queue:retry all");
        $this->line("  • Clear all jobs: php artisan queue:clear");
    }
}