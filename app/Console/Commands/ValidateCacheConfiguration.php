<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Exception;

class ValidateCacheConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'validate:cache 
                            {--backend= : Specific cache backend to test (file, database, redis, memcached)}
                            {--all : Test all available cache backends}';

    /**
     * The console command description.
     */
    protected $description = 'Validate cache configuration and test cache backend connectivity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Cache Configuration Validation');
        $this->newLine();

        $backend = $this->option('backend');
        $testAll = $this->option('all');

        if ($testAll) {
            return $this->testAllBackends();
        }

        if ($backend) {
            return $this->testSpecificBackend($backend);
        }

        // Test current default backend
        $currentBackend = config('cache.default');
        $this->info("Testing current cache backend: <fg=yellow>{$currentBackend}</>");
        $this->newLine();

        return $this->testCacheBackend($currentBackend);
    }

    /**
     * Test all available cache backends
     */
    private function testAllBackends(): int
    {
        $backends = ['file', 'database', 'redis', 'memcached'];
        $results = [];

        foreach ($backends as $backend) {
            $this->info("Testing <fg=yellow>{$backend}</> cache backend...");
            $result = $this->testCacheBackend($backend, false);
            $results[$backend] = $result === Command::SUCCESS;
            $this->newLine();
        }

        // Summary table
        $this->info('📊 Cache Backend Test Results:');
        $tableData = [];
        foreach ($results as $backend => $success) {
            $status = $success ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>';
            $tableData[] = [ucfirst($backend), $status];
        }

        $this->table(['Backend', 'Status'], $tableData);

        return in_array(false, $results) ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Test a specific cache backend
     */
    private function testSpecificBackend(string $backend): int
    {
        $this->info("Testing <fg=yellow>{$backend}</> cache backend...");
        $this->newLine();

        return $this->testCacheBackend($backend);
    }

    /**
     * Test cache backend functionality
     */
    private function testCacheBackend(string $backend, bool $showDetails = true): int
    {
        try {
            // Check if backend is configured
            if (!$this->isBackendConfigured($backend)) {
                if ($showDetails) {
                    $this->error("❌ {$backend} cache backend is not properly configured");
                    $this->showBackendConfiguration($backend);
                }
                return Command::FAILURE;
            }

            // Test cache operations
            $testKey = "cache_validation_test_{$backend}_" . time();
            $testValue = "test_value_" . uniqid();

            // Temporarily switch to the backend being tested
            $originalBackend = config('cache.default');
            Config::set('cache.default', $backend);

            // Test put operation
            Cache::put($testKey, $testValue, 60);
            if ($showDetails) {
                $this->info("✓ PUT operation successful");
            }

            // Test get operation
            $retrievedValue = Cache::get($testKey);
            if ($retrievedValue !== $testValue) {
                throw new Exception("Retrieved value does not match stored value");
            }
            if ($showDetails) {
                $this->info("✓ GET operation successful");
            }

            // Test forget operation
            Cache::forget($testKey);
            $forgottenValue = Cache::get($testKey);
            if ($forgottenValue !== null) {
                throw new Exception("Value was not properly forgotten");
            }
            if ($showDetails) {
                $this->info("✓ FORGET operation successful");
            }

            // Restore original backend
            Config::set('cache.default', $originalBackend);

            if ($showDetails) {
                $this->info("🎉 <fg=green>{$backend} cache backend is working correctly!</>");
            }

            return Command::SUCCESS;

        } catch (Exception $e) {
            // Restore original backend on error
            if (isset($originalBackend)) {
                Config::set('cache.default', $originalBackend);
            }

            if ($showDetails) {
                $this->error("❌ {$backend} cache backend test failed: " . $e->getMessage());
                $this->showTroubleshootingGuide($backend, $e);
            }

            return Command::FAILURE;
        }
    }

    /**
     * Check if a cache backend is properly configured
     */
    private function isBackendConfigured(string $backend): bool
    {
        $stores = config('cache.stores');
        
        if (!isset($stores[$backend])) {
            return false;
        }

        switch ($backend) {
            case 'file':
                $path = $stores['file']['path'] ?? null;
                return $path && is_dir($path) && is_writable($path);

            case 'database':
                try {
                    $table = $stores['database']['table'] ?? 'cache';
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

            case 'memcached':
                if (!extension_loaded('memcached')) {
                    return false;
                }
                try {
                    $memcached = new \Memcached();
                    $servers = $stores['memcached']['servers'] ?? [];
                    if (empty($servers)) {
                        return false;
                    }
                    $server = $servers[0];
                    $memcached->addServer($server['host'], $server['port']);
                    $memcached->set('test', 'test', 1);
                    return true;
                } catch (Exception $e) {
                    return false;
                }

            default:
                return true;
        }
    }

    /**
     * Show backend configuration details
     */
    private function showBackendConfiguration(string $backend): void
    {
        $this->newLine();
        $this->info("📋 Configuration for <fg=yellow>{$backend}</> backend:");

        switch ($backend) {
            case 'file':
                $path = config('cache.stores.file.path');
                $this->info("  Path: {$path}");
                $this->info("  Writable: " . (is_writable($path) ? 'Yes' : 'No'));
                break;

            case 'database':
                $table = config('cache.stores.database.table', 'cache');
                $hasTable = DB::getSchemaBuilder()->hasTable($table);
                $this->info("  Table: {$table}");
                $this->info("  Table exists: " . ($hasTable ? 'Yes' : 'No'));
                break;

            case 'redis':
                $host = config('database.redis.default.host', env('REDIS_HOST', '127.0.0.1'));
                $port = config('database.redis.default.port', env('REDIS_PORT', 6379));
                $this->info("  Host: {$host}");
                $this->info("  Port: {$port}");
                break;

            case 'memcached':
                $servers = config('cache.stores.memcached.servers', []);
                $extensionLoaded = extension_loaded('memcached');
                $this->info("  Extension loaded: " . ($extensionLoaded ? 'Yes' : 'No'));
                if (!empty($servers)) {
                    $server = $servers[0];
                    $this->info("  Server: {$server['host']}:{$server['port']}");
                }
                break;
        }
    }

    /**
     * Show troubleshooting guide for failed backends
     */
    private function showTroubleshootingGuide(string $backend, Exception $e): void
    {
        $this->newLine();
        $this->error("🔧 Troubleshooting Guide for {$backend}:");

        switch ($backend) {
            case 'file':
                $this->line("  • Ensure storage/framework/cache/data directory exists and is writable");
                $this->line("  • Check file permissions: chmod 755 storage/framework/cache/data");
                $this->line("  • Verify web server has write access to storage directory");
                break;

            case 'database':
                $this->line("  • Ensure cache table exists: php artisan cache:table && php artisan migrate");
                $this->line("  • Check database connection configuration");
                $this->line("  • Verify DB_CONNECTION, DB_HOST, DB_DATABASE settings in .env");
                break;

            case 'redis':
                $this->line("  • Ensure Redis server is running");
                $this->line("  • For DDEV: Add Redis service with docker-compose.redis.yaml");
                $this->line("  • Check REDIS_HOST, REDIS_PORT, REDIS_PASSWORD in .env");
                $this->line("  • Test connection: redis-cli ping");
                break;

            case 'memcached':
                $this->line("  • Install memcached extension: apt-get install php-memcached");
                $this->line("  • Ensure memcached service is running");
                $this->line("  • For DDEV: Add memcached service configuration");
                $this->line("  • Check MEMCACHED_HOST, MEMCACHED_PORT in .env");
                break;
        }

        $this->newLine();
        $this->line("Error details: " . $e->getMessage());
    }
}