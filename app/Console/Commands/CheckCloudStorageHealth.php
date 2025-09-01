<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use App\Enums\CloudStorageErrorType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckCloudStorageHealth extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cloud-storage:check-health 
                            {--provider=* : Specific provider(s) to check (default: all)}
                            {--user= : Specific user ID to check (default: all users)}
                            {--notify : Send notifications for issues found}';

    /**
     * The console command description.
     */
    protected $description = 'Check the health of cloud storage connections and update status';

    public function __construct(
        private CloudStorageHealthService $healthService,
        private GoogleDriveService $googleDriveService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cloud storage health check...');
        
        $providers = $this->option('provider') ?: ['google-drive'];
        $userId = $this->option('user');
        $shouldNotify = $this->option('notify');
        
        $users = $userId 
            ? User::where('id', $userId)->get()
            : User::whereHas('googleDriveToken')->get();
            
        if ($users->isEmpty()) {
            $this->warn('No users found with cloud storage connections.');
            return self::SUCCESS;
        }
        
        $this->info("Checking health for {$users->count()} users across " . implode(', ', $providers) . " providers");
        
        $healthyCount = 0;
        $degradedCount = 0;
        $unhealthyCount = 0;
        $errorCount = 0;
        
        foreach ($users as $user) {
            foreach ($providers as $provider) {
                try {
                    $this->line("Checking {$provider} for user {$user->email}...");
                    
                    $healthStatus = $this->checkProviderHealth($user, $provider);
                    
                    switch ($healthStatus->status) {
                        case 'healthy':
                            $healthyCount++;
                            $this->info("  ✓ Healthy");
                            break;
                        case 'degraded':
                            $degradedCount++;
                            $this->warn("  ⚠ Degraded: {$healthStatus->last_error_message}");
                            break;
                        case 'unhealthy':
                            $unhealthyCount++;
                            $this->error("  ✗ Unhealthy: {$healthStatus->last_error_message}");
                            break;
                        default:
                            $this->line("  - Disconnected");
                    }
                    
                    // Check for token expiration warnings
                    if ($healthStatus->isTokenExpiringSoon()) {
                        $this->warn("  ⏰ Token will refresh soon: {$healthStatus->token_expires_at}");
                        
                        if ($shouldNotify) {
                            $this->sendTokenExpirationWarning($user, $provider, $healthStatus);
                        }
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $this->error("  ✗ Error checking {$provider}: {$e->getMessage()}");
                    
                    Log::error('Health check failed for user', [
                        'user_id' => $user->id,
                        'provider' => $provider,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        // Display summary
        $this->newLine();
        $this->info('Health Check Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Healthy', $healthyCount],
                ['Degraded', $degradedCount],
                ['Unhealthy', $unhealthyCount],
                ['Errors', $errorCount],
            ]
        );
        
        // Send notifications if requested
        if ($shouldNotify) {
            $this->newLine();
            $this->info('Sending notifications for issues found...');
            
            foreach ($providers as $provider) {
                // Send notifications for expiring tokens
                $expiringNotifications = $this->healthService->notifyUsersWithExpiringTokens($provider, 24);
                if ($expiringNotifications > 0) {
                    $this->info("Sent {$expiringNotifications} token refresh notifications for {$provider}");
                }
                
                // Send notifications for unhealthy connections
                $unhealthyNotifications = $this->healthService->notifyUsersWithUnhealthyConnections($provider);
                if ($unhealthyNotifications > 0) {
                    $this->info("Sent {$unhealthyNotifications} unhealthy connection notifications for {$provider}");
                }
            }
        }
        
        // Log summary
        Log::info('Cloud storage health check completed', [
            'healthy' => $healthyCount,
            'degraded' => $degradedCount,
            'unhealthy' => $unhealthyCount,
            'errors' => $errorCount,
            'total_checked' => $users->count() * count($providers),
            'notifications_sent' => $shouldNotify,
        ]);
        
        return self::SUCCESS;
    }
    
    /**
     * Check health for a specific provider.
     */
    private function checkProviderHealth(User $user, string $provider): \App\Models\CloudStorageHealthStatus
    {
        return match ($provider) {
            'google-drive' => $this->checkGoogleDriveHealth($user),
            default => throw new Exception("Unsupported provider: {$provider}")
        };
    }
    
    /**
     * Check Google Drive specific health.
     */
    private function checkGoogleDriveHealth(User $user): \App\Models\CloudStorageHealthStatus
    {
        try {
            // Check if user has a token
            $token = $user->googleDriveToken;
            if (!$token) {
                $this->healthService->markConnectionAsUnhealthy(
                    $user, 
                    'google-drive', 
                    'No Google Drive token found',
                    CloudStorageErrorType::TOKEN_EXPIRED
                );
                return $this->healthService->getOrCreateHealthStatus($user, 'google-drive');
            }
            
            // Update token expiration info
            if ($token->expires_at) {
                $this->healthService->updateTokenExpiration($user, 'google-drive', $token->expires_at);
            }
            
            // Try to get a valid token (this will refresh if needed)
            $validToken = $this->googleDriveService->getValidToken($user);
            
            // Perform a lightweight API call to verify connection
            $driveService = $this->googleDriveService->getDriveService($user);
            
            // Simple API call to verify connection - get user's Drive info
            $about = $driveService->about->get(['fields' => 'user']);
            
            if ($about && $about->getUser()) {
                // Connection is healthy
                $providerData = [
                    'user_email' => $about->getUser()->getEmailAddress(),
                    'last_health_check' => now()->toISOString(),
                ];
                
                $this->healthService->recordSuccessfulOperation($user, 'google-drive', $providerData);
                
                // Update token expiration if we have new info
                if ($validToken->expires_at) {
                    $this->healthService->updateTokenExpiration($user, 'google-drive', $validToken->expires_at);
                }
            } else {
                $this->healthService->markConnectionAsUnhealthy(
                    $user, 
                    'google-drive', 
                    'Unable to retrieve user information from Google Drive',
                    CloudStorageErrorType::UNKNOWN_ERROR
                );
            }
            
        } catch (Exception $e) {
            // Classify the error and mark as unhealthy
            $errorType = $this->classifyGoogleDriveError($e);
            $this->healthService->markConnectionAsUnhealthy($user, 'google-drive', $e->getMessage(), $errorType);
        }
        
        return $this->healthService->getOrCreateHealthStatus($user, 'google-drive');
    }
    
    /**
     * Classify Google Drive errors.
     */
    private function classifyGoogleDriveError(Exception $e): CloudStorageErrorType
    {
        $message = strtolower($e->getMessage());
        
        if (str_contains($message, 'token') && str_contains($message, 'expired')) {
            return CloudStorageErrorType::TOKEN_EXPIRED;
        }
        
        if (str_contains($message, 'refresh token')) {
            return CloudStorageErrorType::TOKEN_EXPIRED;
        }
        
        if (str_contains($message, 'insufficient') || str_contains($message, 'permission')) {
            return CloudStorageErrorType::INSUFFICIENT_PERMISSIONS;
        }
        
        if (str_contains($message, 'quota') || str_contains($message, 'limit')) {
            return CloudStorageErrorType::API_QUOTA_EXCEEDED;
        }
        
        if (str_contains($message, 'network') || str_contains($message, 'connection')) {
            return CloudStorageErrorType::NETWORK_ERROR;
        }
        
        return CloudStorageErrorType::UNKNOWN_ERROR;
    }
    
    /**
     * Send token expiration warning notification.
     */
    private function sendTokenExpirationWarning(User $user, string $provider, \App\Models\CloudStorageHealthStatus $healthStatus): void
    {
        try {
            $user->notify(new \App\Notifications\CloudStorageConnectionAlert(
                $provider,
                'token_expiring',
                $healthStatus
            ));
            
            $this->line("  📧 Token expiration warning sent to {$user->email}");
            
        } catch (\Exception $e) {
            Log::error('Failed to send token expiration warning', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            $this->error("  ✗ Failed to send notification to {$user->email}: {$e->getMessage()}");
        }
    }
    
    /**
     * Get or create health status (helper method).
     */
    private function getOrCreateHealthStatus(User $user, string $provider): \App\Models\CloudStorageHealthStatus
    {
        return \App\Models\CloudStorageHealthStatus::firstOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'status' => 'disconnected',
                'consecutive_failures' => 0,
                'requires_reconnection' => false,
            ]
        );
    }
}