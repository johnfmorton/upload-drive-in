<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Models\User;
use App\Services\CloudStorageLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloudStorageLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageLogService $logService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logService = new CloudStorageLogService();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => \App\Enums\UserRole::ADMIN,
        ]);
    }

    public function test_log_operation_start_creates_structured_log(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'Cloud storage operation started: upload',
                \Mockery::on(function ($context) {
                    return isset($context['operation_id']) &&
                           $context['operation'] === 'upload' &&
                           $context['provider'] === 'google-drive' &&
                           $context['user_id'] === $this->user->id &&
                           $context['user_email'] === $this->user->email &&
                           $context['operation_status'] === 'started' &&
                           isset($context['started_at']) &&
                           isset($context['timestamp']);
                })
            );

        $operationId = $this->logService->logOperationStart(
            'upload',
            'google-drive',
            $this->user,
            ['file_name' => 'test.pdf']
        );

        $this->assertNotEmpty($operationId);
        $this->assertStringStartsWith('cs_', $operationId);
    }

    public function test_log_operation_success_logs_to_multiple_channels(): void
    {
        $operationId = 'cs_test123_' . now()->timestamp;
        $durationMs = 1500.5;

        // Expect cloud-storage channel log
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'Cloud storage operation successful: upload',
                \Mockery::on(function ($context) use ($operationId, $durationMs) {
                    return $context['operation_id'] === $operationId &&
                           $context['operation_status'] === 'success' &&
                           $context['duration_ms'] === $durationMs &&
                           isset($context['completed_at']);
                })
            );

        // Expect audit channel log
        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'Cloud storage success: upload',
                \Mockery::on(function ($context) use ($operationId, $durationMs) {
                    return $context['operation_id'] === $operationId &&
                           $context['user_id'] === $this->user->id &&
                           $context['provider'] === 'google-drive' &&
                           $context['duration_ms'] === $durationMs;
                })
            );

        // Expect performance channel log
        Log::shouldReceive('channel')
            ->with('performance')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'Cloud storage performance: upload',
                \Mockery::on(function ($context) use ($durationMs) {
                    return $context['duration_ms'] === $durationMs &&
                           $context['outcome'] === 'success' &&
                           $context['performance_category'] === 'normal';
                })
            );

        $this->logService->logOperationSuccess(
            $operationId,
            'upload',
            'google-drive',
            $this->user,
            ['file_id' => 'drive123'],
            $durationMs
        );
    }

    public function test_log_operation_failure_includes_error_classification(): void
    {
        $operationId = 'cs_test123_' . now()->timestamp;
        $errorType = CloudStorageErrorType::TOKEN_EXPIRED;
        $exception = new \Exception('Token has expired');

        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('error')
            ->once()
            ->with(
                'Cloud storage operation failed: upload',
                \Mockery::on(function ($context) use ($operationId, $errorType) {
                    return $context['operation_id'] === $operationId &&
                           $context['operation_status'] === 'failed' &&
                           $context['error_type'] === $errorType->value &&
                           $context['error_classification'] === 'authentication' &&
                           $context['is_retryable'] === false &&
                           $context['requires_user_action'] === true &&
                           isset($context['exception']);
                })
            );

        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Cloud storage failure: upload', \Mockery::any());

        // Expect performance channel log for failures too
        Log::shouldReceive('channel')
            ->with('performance')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Cloud storage performance: upload', \Mockery::any());

        $this->logService->logOperationFailure(
            $operationId,
            'upload',
            'google-drive',
            $this->user,
            $errorType,
            'Token has expired',
            ['file_name' => 'test.pdf'],
            1000.0,
            $exception
        );
    }

    public function test_log_retry_decision_logs_retry_reasoning(): void
    {
        $operationId = 'cs_test123_' . now()->timestamp;
        $errorType = CloudStorageErrorType::NETWORK_ERROR;

        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('log')
            ->once()
            ->with(
                'info',
                'Cloud storage operation will be retried: upload (attempt 2)',
                \Mockery::on(function ($context) use ($operationId, $errorType) {
                    return $context['operation_id'] === $operationId &&
                           $context['retry_decision'] === 'retry' &&
                           $context['attempt_number'] === 2 &&
                           $context['error_type'] === $errorType->value &&
                           $context['retry_delay_seconds'] === 30 &&
                           $context['retry_reason'] === 'Network error - temporary issue';
                })
            );

        $this->logService->logRetryDecision(
            $operationId,
            'upload',
            'google-drive',
            $this->user,
            $errorType,
            2,
            true,
            30
        );
    }

    public function test_log_health_status_change_categorizes_severity(): void
    {
        $errorType = CloudStorageErrorType::TOKEN_EXPIRED;

        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('log')
            ->once()
            ->with(
                'error',
                'Cloud storage health status changed: healthy → unhealthy',
                \Mockery::on(function ($context) use ($errorType) {
                    return $context['event_type'] === 'health_status_change' &&
                           $context['previous_status'] === 'healthy' &&
                           $context['new_status'] === 'unhealthy' &&
                           $context['error_type'] === $errorType->value &&
                           $context['status_severity'] === 'high' &&
                           isset($context['status_changed_at']);
                })
            );

        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Cloud storage health status change', \Mockery::any());

        $this->logService->logHealthStatusChange(
            'google-drive',
            $this->user,
            'healthy',
            'unhealthy',
            $errorType,
            'Token expired'
        );
    }

    public function test_log_token_refresh_logs_to_audit_channel(): void
    {
        $newExpiresAt = now()->addHour();

        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('log')
            ->once()
            ->with(
                'info',
                'Cloud storage token refreshed successfully: google-drive',
                \Mockery::on(function ($context) use ($newExpiresAt) {
                    return $context['event_type'] === 'token_refresh' &&
                           $context['refresh_status'] === 'success' &&
                           $context['new_expires_at'] === $newExpiresAt->toISOString() &&
                           isset($context['refreshed_at']);
                })
            );

        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'Token refresh: google-drive',
                \Mockery::on(function ($context) use ($newExpiresAt) {
                    return $context['user_id'] === $this->user->id &&
                           $context['provider'] === 'google-drive' &&
                           $context['success'] === true &&
                           $context['new_expires_at'] === $newExpiresAt->toISOString();
                })
            );

        $this->logService->logTokenRefresh(
            'google-drive',
            $this->user,
            true,
            $newExpiresAt
        );
    }

    public function test_log_oauth_event_logs_security_events(): void
    {
        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('log')
            ->once()
            ->with(
                'info',
                'OAuth event successful: callback_complete for google-drive',
                \Mockery::on(function ($context) {
                    return $context['event_type'] === 'oauth_event' &&
                           $context['oauth_event'] === 'callback_complete' &&
                           $context['oauth_status'] === 'success' &&
                           isset($context['event_at']);
                })
            );

        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'OAuth event: callback_complete',
                \Mockery::on(function ($context) {
                    return $context['user_id'] === $this->user->id &&
                           $context['provider'] === 'google-drive' &&
                           $context['event'] === 'callback_complete' &&
                           $context['success'] === true;
                })
            );

        $this->logService->logOAuthEvent(
            'google-drive',
            $this->user,
            'callback_complete',
            true
        );
    }

    public function test_log_bulk_operation_summary_calculates_metrics(): void
    {
        $errorSummary = [
            'token_expired' => 2,
            'network_error' => 1,
        ];

        Log::shouldReceive('channel')
            ->with('cloud-storage')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'Bulk cloud storage operation completed: bulk_upload',
                \Mockery::on(function ($context) use ($errorSummary) {
                    return $context['event_type'] === 'bulk_operation_summary' &&
                           $context['total_items'] === 10 &&
                           $context['success_count'] === 7 &&
                           $context['failure_count'] === 3 &&
                           $context['success_rate'] === 70.0 &&
                           $context['total_duration_ms'] === 5000.0 &&
                           $context['average_duration_ms'] === 500.0 &&
                           $context['error_summary'] === $errorSummary;
                })
            );

        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Bulk operation: bulk_upload', \Mockery::any());

        $this->logService->logBulkOperationSummary(
            'bulk_upload',
            'google-drive',
            $this->user,
            10,
            7,
            3,
            5000.0,
            $errorSummary
        );
    }

    public function test_performance_metrics_categorization(): void
    {
        $testCases = [
            [500.0, 'fast'],
            [2000.0, 'normal'],
            [10000.0, 'slow'],
            [20000.0, 'very_slow'],
        ];

        foreach ($testCases as [$duration, $expectedCategory]) {
            Log::shouldReceive('channel')
                ->with('performance')
                ->once()
                ->andReturnSelf();
            
            Log::shouldReceive('info')
                ->once()
                ->with(
                    'Cloud storage performance: test_operation',
                    \Mockery::on(function ($context) use ($duration, $expectedCategory) {
                        return $context['duration_ms'] === $duration &&
                               $context['performance_category'] === $expectedCategory;
                    })
                );

            $this->logService->logPerformanceMetrics(
                'test_operation',
                'google-drive',
                $duration,
                'success'
            );
        }
    }

    public function test_error_classification_mapping(): void
    {
        $testCases = [
            [CloudStorageErrorType::TOKEN_EXPIRED, 'authentication'],
            [CloudStorageErrorType::INSUFFICIENT_PERMISSIONS, 'authentication'],
            [CloudStorageErrorType::API_QUOTA_EXCEEDED, 'quota'],
            [CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED, 'quota'],
            [CloudStorageErrorType::NETWORK_ERROR, 'network'],
            [CloudStorageErrorType::FILE_NOT_FOUND, 'access'],
            [CloudStorageErrorType::FOLDER_ACCESS_DENIED, 'access'],
            [CloudStorageErrorType::INVALID_FILE_TYPE, 'validation'],
            [CloudStorageErrorType::UNKNOWN_ERROR, 'unknown'],
        ];

        foreach ($testCases as [$errorType, $expectedClassification]) {
            $operationId = 'cs_test_' . $errorType->value;

            Log::shouldReceive('channel')
                ->with('cloud-storage')
                ->once()
                ->andReturnSelf();
            
            Log::shouldReceive('error')
                ->once()
                ->with(
                    'Cloud storage operation failed: test',
                    \Mockery::on(function ($context) use ($errorType, $expectedClassification) {
                        return $context['error_type'] === $errorType->value &&
                               $context['error_classification'] === $expectedClassification;
                    })
                );

            Log::shouldReceive('channel')
                ->with('audit')
                ->once()
                ->andReturnSelf();
            
            Log::shouldReceive('warning')
                ->once()
                ->with('Cloud storage failure: test', \Mockery::any());

            $this->logService->logOperationFailure(
                $operationId,
                'test',
                'google-drive',
                $this->user,
                $errorType,
                'Test error message'
            );
        }
    }

    public function test_operation_id_generation_is_unique(): void
    {
        $operationIds = [];
        
        for ($i = 0; $i < 10; $i++) {
            Log::shouldReceive('channel')->andReturnSelf();
            Log::shouldReceive('info');
            
            $operationId = $this->logService->logOperationStart(
                'test',
                'google-drive',
                $this->user
            );
            
            $this->assertNotContains($operationId, $operationIds);
            $operationIds[] = $operationId;
        }
    }
}