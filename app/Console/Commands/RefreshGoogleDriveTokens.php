<?php

namespace App\Console\Commands;

use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Notifications\GoogleDriveTokenRefreshFailed;
use Google\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class RefreshGoogleDriveTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-drive:refresh-tokens 
                            {--force : Force refresh all tokens regardless of expiration}
                            {--dry-run : Show what would be refreshed without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Proactively refresh Google Drive tokens that are expiring soon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Google Drive token refresh process...');
        
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        // Get tokens that need refreshing (expire within 24 hours or already expired)
        $query = GoogleDriveToken::with('user');
        
        if (!$force) {
            $query->where(function ($q) {
                $q->where('expires_at', '<=', Carbon::now()->addDay())
                  ->orWhereNull('expires_at');
            });
        }
        
        $tokens = $query->get();
        
        if ($tokens->isEmpty()) {
            $this->info('No tokens need refreshing at this time.');
            Log::info('Google Drive token refresh: No tokens needed refreshing');
            return self::SUCCESS;
        }
        
        $this->info("Found {$tokens->count()} token(s) that need refreshing.");
        
        $refreshed = 0;
        $failed = 0;
        $skipped = 0;
        $failedUsers = [];
        
        foreach ($tokens as $token) {
            $userName = $token->user->name ?? 'Unknown User';
            $userId = $token->user_id;
            
            try {
                if ($dryRun) {
                    $this->line("Would refresh token for user: {$userName} (ID: {$userId})");
                    continue;
                }
                
                if (!$token->refresh_token) {
                    $this->warn("Skipping user {$userName} (ID: {$userId}) - no refresh token available");
                    Log::warning('Google Drive token refresh: No refresh token available', [
                        'user_id' => $userId,
                        'user_name' => $userName
                    ]);
                    $skipped++;
                    continue;
                }
                
                $this->line("Refreshing token for user: {$userName} (ID: {$userId})");
                
                // Create Google Client and refresh token
                $client = new Client();
                $client->setClientId(config('services.google.client_id'));
                $client->setClientSecret(config('services.google.client_secret'));
                
                // Set the current token
                $client->setAccessToken([
                    'access_token' => $token->access_token,
                    'refresh_token' => $token->refresh_token,
                    'token_type' => $token->token_type ?? 'Bearer',
                    'expires_in' => $token->expires_at ? max(0, $token->expires_at->diffInSeconds(now())) : 0,
                ]);
                
                // Refresh the token
                $newTokenData = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);
                
                if (isset($newTokenData['error'])) {
                    throw new \Exception('Google API error: ' . $newTokenData['error_description'] ?? $newTokenData['error']);
                }
                
                // Update the token record
                $token->update([
                    'access_token' => $newTokenData['access_token'],
                    'refresh_token' => $newTokenData['refresh_token'] ?? $token->refresh_token, // Keep existing if not provided
                    'token_type' => $newTokenData['token_type'] ?? 'Bearer',
                    'expires_at' => isset($newTokenData['expires_in']) 
                        ? Carbon::now()->addSeconds($newTokenData['expires_in'])
                        : null,
                ]);
                
                $this->info("✓ Successfully refreshed token for user: {$userName}");
                Log::info('Google Drive token refreshed successfully', [
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'expires_at' => $token->expires_at?->toDateTimeString()
                ]);
                
                $refreshed++;
                
            } catch (\Exception $e) {
                $this->error("✗ Failed to refresh token for user {$userName} (ID: {$userId}): {$e->getMessage()}");
                Log::error('Google Drive token refresh failed', [
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $failedUsers[] = [
                    'id' => $userId,
                    'name' => $userName,
                    'error' => $e->getMessage()
                ];
                
                $failed++;
            }
        }
        
        // Summary
        $this->newLine();
        $this->info("Token refresh summary:");
        $this->line("- Refreshed: {$refreshed}");
        $this->line("- Failed: {$failed}");
        $this->line("- Skipped: {$skipped}");
        
        Log::info('Google Drive token refresh completed', [
            'total_tokens' => $tokens->count(),
            'refreshed' => $refreshed,
            'failed' => $failed,
            'skipped' => $skipped,
            'dry_run' => $dryRun
        ]);
        
        // Send notification if there were failures
        if ($failed > 0 && !$dryRun) {
            $this->warn("Some tokens failed to refresh. Sending notification to admin users.");
            
            // Get admin users to notify
            $adminUsers = User::where('role', 'admin')->get();
            
            if ($adminUsers->isNotEmpty()) {
                Notification::send($adminUsers, new GoogleDriveTokenRefreshFailed($failedUsers, $failed));
                Log::info('Google Drive token refresh failure notification sent', [
                    'admin_count' => $adminUsers->count(),
                    'failed_users' => count($failedUsers)
                ]);
            }
            
            return self::FAILURE;
        }
        
        return self::SUCCESS;
    }
}