<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CloudStorageHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ManageCloudStorageCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-storage:cache 
                            {action : The action to perform (clear, status, stats)}
                            {--user= : User ID to target (optional)}
                            {--provider=google-drive : Provider to target}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage cloud storage caching and rate limiting';

    /**
     * Execute the console command.
     */
    public function handle(CloudStorageHealthService $healthService): int
    {
        $action = $this->argument('action');
        $userId = $this->option('user');
        $provider = $this->option('provider');

        switch ($action) {
            case 'clear':
                return $this->clearCaches($healthService, $userId, $provider);
            case 'status':
                return $this->showStatus($healthService, $userId, $provider);
            case 'stats':
                return $this->showStats($healthService);
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: clear, status, stats');
                return 1;
        }
    }

    /**
     * Clear caches for specified user(s) and provider.
     */
    private function clearCaches(CloudStorageHealthService $healthService, ?string $userId, string $provider): int
    {
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return 1;
            }

            $healthService->clearCaches($user, $provider);
            $this->info("Cleared caches for user {$user->email} and provider {$provider}");
        } else {
            // Clear all caches for all users
            $users = User::all();
            $count = 0;

            foreach ($users as $user) {
                $healthService->clearCaches($user, $provider);
                $count++;
            }

            $this->info("Cleared caches for {$count} users and provider {$provider}");
        }

        return 0;
    }

    /**
     * Show cache and rate limiting status.
     */
    private function showStatus(CloudStorageHealthService $healthService, ?string $userId, string $provider): int
    {
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return 1;
            }

            $this->showUserStatus($healthService, $user, $provider);
        } else {
            // Show status for all users with cloud storage connections
            $users = User::whereHas('cloudStorageHealthStatuses', function ($query) use ($provider) {
                $query->where('provider', $provider);
            })->get();

            if ($users->isEmpty()) {
                $this->info("No users found with {$provider} connections");
                return 0;
            }

            foreach ($users as $user) {
                $this->showUserStatus($healthService, $user, $provider);
                $this->line('');
            }
        }

        return 0;
    }

    /**
     * Show status for a specific user.
     */
    private function showUserStatus(CloudStorageHealthService $healthService, User $user, string $provider): void
    {
        $this->info("User: {$user->email} (ID: {$user->id})");
        
        // Check cache status
        $tokenCacheKey = "token_valid_{$user->id}_{$provider}";
        $apiCacheKey = "api_connectivity_{$user->id}_{$provider}";
        
        $tokenCached = Cache::has($tokenCacheKey);
        $apiCached = Cache::has($apiCacheKey);
        
        $this->line("  Token validation cached: " . ($tokenCached ? 'Yes' : 'No'));
        if ($tokenCached) {
            $this->line("    Result: " . (Cache::get($tokenCacheKey) ? 'Valid' : 'Invalid'));
        }
        
        $this->line("  API connectivity cached: " . ($apiCached ? 'Yes' : 'No'));
        if ($apiCached) {
            $this->line("    Result: " . (Cache::get($apiCacheKey) ? 'Connected' : 'Disconnected'));
        }

        // Show rate limiting status
        $rateLimitStatus = $healthService->getRateLimitStatus($user, $provider);
        
        $this->line("  Token refresh rate limit:");
        $this->line("    Attempts: {$rateLimitStatus['token_refresh']['attempts']}/{$rateLimitStatus['token_refresh']['max_attempts']}");
        $this->line("    Can attempt: " . ($rateLimitStatus['token_refresh']['can_attempt'] ? 'Yes' : 'No'));
        
        $this->line("  Connectivity test rate limit:");
        $this->line("    Attempts: {$rateLimitStatus['connectivity_test']['attempts']}/{$rateLimitStatus['connectivity_test']['max_attempts']}");
        $this->line("    Can attempt: " . ($rateLimitStatus['connectivity_test']['can_attempt'] ? 'Yes' : 'No'));
    }

    /**
     * Show overall caching statistics.
     */
    private function showStats(CloudStorageHealthService $healthService): int
    {
        $this->info('Cloud Storage Caching Statistics');
        $this->line('');

        // Count cached items
        $tokenCacheCount = 0;
        $apiCacheCount = 0;
        $rateLimitCount = 0;

        $users = User::all();
        foreach ($users as $user) {
            $tokenCacheKey = "token_valid_{$user->id}_google-drive";
            $apiCacheKey = "api_connectivity_{$user->id}_google-drive";
            $tokenRateLimitKey = "token_refresh_rate_limit_{$user->id}_google-drive";
            $connectivityRateLimitKey = "connectivity_test_rate_limit_{$user->id}_google-drive";

            if (Cache::has($tokenCacheKey)) $tokenCacheCount++;
            if (Cache::has($apiCacheKey)) $apiCacheCount++;
            if (Cache::has($tokenRateLimitKey) || Cache::has($connectivityRateLimitKey)) $rateLimitCount++;
        }

        $this->table([
            'Cache Type',
            'Count',
            'Description'
        ], [
            ['Token Validation', $tokenCacheCount, 'Cached token validation results'],
            ['API Connectivity', $apiCacheCount, 'Cached API connectivity test results'],
            ['Rate Limiting', $rateLimitCount, 'Users with active rate limits'],
        ]);

        $this->line('');
        $this->info('Cache Configuration:');
        $this->line('  Token validation cache TTL: 5 minutes (success), 1 minute (failure)');
        $this->line('  API connectivity cache TTL: 2 minutes (success), 30 seconds (failure)');
        $this->line('  Token refresh rate limit: 10 attempts per hour');
        $this->line('  Connectivity test rate limit: 20 attempts per hour');

        return 0;
    }
}