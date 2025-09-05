<?php

namespace App\Jobs;

use App\Enums\TokenRefreshErrorType;
use App\Models\User;
use App\Services\ProactiveTokenRenewalService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Background job for refreshing authentication tokens
 * 
 * Handles proactive token refresh operations in the background
 * to ensure tokens are renewed before they expire
 */
class RefreshTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     * This will be dynamically set based on error type
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * This will be dynamically calculated based on error type
     *
     * @var array<int, int>
     */
    public array $backoff = [];

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param User $user The user whose token should be refreshed
     * @param string $provider The cloud storage provider (e.g., 'google-drive')
     */
    public function __construct(
        public readonly User $user,
        public readonly string $provider
    ) {}

    /**
     * Execute the job.
     *
     * @param ProactiveTokenRenewalService $renewalService Injected token renewal service
     * @return void
     * @throws Exception Throws exceptions on failure, allowing the queue worker to handle retries/failures.
     */
    public function handle(ProactiveTokenRenewalService $renewalService): void
    {
        $operationId = uniqid('refresh_job_', true);
        
        Log::info('Starting token refresh job', [
            'user_id' => $this->user->id,
            'provider' => $this->provider,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'operation_id' => $operationId
        ]);

        try {
            // Attempt to refresh the token
            $result = $renewalService->refreshTokenIfNeeded($this->user, $this->provider);

            if ($result->isSuccessful()) {
                Log::info('Token refresh job completed successfully', [
                    'user_id' => $this->user->id,
                    'provider' => $this->provider,
                    'was_already_valid' => $result->wasAlreadyValid,
                    'was_refreshed_by_another_process' => $result->wasRefreshedByAnotherProcess,
                    'total_attempts' => $this->attempts(),
                    'operation_id' => $operationId
                ]);
                
                // Job completed successfully, no need to retry
                return;
            } else {
                // Refresh failed, handle the error
                $errorType = $result->getErrorType();
                $exception = $result->getException() ?? new Exception($result->message ?? 'Token refresh failed');

                Log::error('Token refresh job failed', [
                    'user_id' => $this->user->id,
                    'provider' => $this->provider,
                    'error_type' => $errorType?->value,
                    'error_message' => $result->message,
                    'attempt' => $this->attempts(),
                    'operation_id' => $operationId
                ]);

                // Update job retry configuration based on error type
                if ($errorType) {
                    $this->configureRetryBehavior($errorType);
                    
                    // For errors that don't allow retries, fail immediately
                    if (!$errorType->isRecoverable() || $errorType->getMaxRetryAttempts() === 0) {
                        Log::warning('Error type does not allow retries, failing job immediately', [
                            'user_id' => $this->user->id,
                            'provider' => $this->provider,
                            'error_type' => $errorType->value,
                            'requires_user_intervention' => $errorType->requiresUserIntervention(),
                            'operation_id' => $operationId
                        ]);
                        
                        $this->fail($exception);
                        return;
                    }
                }

                // Re-throw the exception to let the queue worker handle retries
                throw $exception;
            }
        } catch (Exception $e) {
            Log::error('Token refresh job threw exception', [
                'user_id' => $this->user->id,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'attempt' => $this->attempts(),
                'operation_id' => $operationId
            ]);

            // Re-throw to let queue worker handle retries
            throw $e;
        }
    }

    /**
     * Configure retry behavior based on error type
     *
     * @param TokenRefreshErrorType $errorType The error type
     * @return void
     */
    private function configureRetryBehavior(TokenRefreshErrorType $errorType): void
    {
        // Update maximum attempts based on error type
        $maxAttempts = $errorType->getMaxRetryAttempts();
        if ($maxAttempts > 0) {
            $this->tries = $maxAttempts + 1; // +1 because attempts start at 1
        } else {
            $this->tries = 1; // No retries allowed
        }

        // Calculate retry delay for current attempt
        $retryDelay = $errorType->getRetryDelay($this->attempts());
        if ($retryDelay > 0) {
            $this->backoff = [$retryDelay];
        }

        Log::debug('Configured retry behavior for error type', [
            'user_id' => $this->user->id,
            'provider' => $this->provider,
            'error_type' => $errorType->value,
            'max_attempts' => $maxAttempts,
            'current_attempt' => $this->attempts(),
            'retry_delay' => $retryDelay,
            'is_recoverable' => $errorType->isRecoverable()
        ]);
    }

    /**
     * Handle job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        $operationId = uniqid('refresh_job_failed_', true);
        
        Log::error('Token refresh job permanently failed after all retries', [
            'user_id' => $this->user->id,
            'provider' => $this->provider,
            'total_attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'exception_type' => get_class($exception),
            'operation_id' => $operationId
        ]);

        try {
            // Use the ProactiveTokenRenewalService to handle the failure
            $renewalService = app(ProactiveTokenRenewalService::class);
            
            // Classify the error type for proper handling
            $errorType = $this->classifyError($exception);
            
            $renewalService->handleRefreshFailure(
                $this->user, 
                $this->provider, 
                $errorType, 
                $exception
            );
            
            Log::info('Token refresh failure handled by renewal service', [
                'user_id' => $this->user->id,
                'provider' => $this->provider,
                'error_type' => $errorType->value,
                'operation_id' => $operationId
            ]);
        } catch (Exception $handlingError) {
            Log::error('Failed to handle token refresh job failure', [
                'user_id' => $this->user->id,
                'provider' => $this->provider,
                'original_error' => $exception->getMessage(),
                'handling_error' => $handlingError->getMessage(),
                'operation_id' => $operationId
            ]);
        }
    }

    /**
     * Classify the error type for appropriate handling
     *
     * @param \Throwable $exception The exception to classify
     * @return TokenRefreshErrorType The classified error type
     */
    private function classifyError(\Throwable $exception): TokenRefreshErrorType
    {
        $message = strtolower($exception->getMessage());

        // Network-related errors
        if (str_contains($message, 'timeout') || 
            str_contains($message, 'connection') ||
            str_contains($message, 'network')) {
            return TokenRefreshErrorType::NETWORK_TIMEOUT;
        }

        // Invalid refresh token errors
        if (str_contains($message, 'invalid_grant') ||
            str_contains($message, 'invalid refresh token') ||
            str_contains($message, 'refresh token') && str_contains($message, 'invalid')) {
            return TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
        }

        // Expired refresh token errors
        if (str_contains($message, 'expired') && str_contains($message, 'refresh')) {
            return TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
        }

        // API quota errors
        if (str_contains($message, 'quota') ||
            str_contains($message, 'rate limit') ||
            str_contains($message, 'too many requests')) {
            return TokenRefreshErrorType::API_QUOTA_EXCEEDED;
        }

        // Service unavailable errors
        if (str_contains($message, 'service unavailable') ||
            str_contains($message, 'temporarily unavailable') ||
            str_contains($message, '503')) {
            return TokenRefreshErrorType::SERVICE_UNAVAILABLE;
        }

        // Default to unknown error
        return TokenRefreshErrorType::UNKNOWN_ERROR;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'token-refresh',
            "user:{$this->user->id}",
            "provider:{$this->provider}",
        ];
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil(): \DateTime
    {
        // Allow retries for up to 1 hour from the first attempt
        return now()->addHour();
    }
}