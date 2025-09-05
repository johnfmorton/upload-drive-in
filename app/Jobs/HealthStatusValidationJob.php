<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RealTimeHealthValidator;
use App\Services\CloudStorageHealthService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HealthStatusValidationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $operationId = uniqid('health_validation_');
        
        Log::info('Health status validation job started', [
            'operation_id' => $operationId,
            'timestamp' => now()->toISOString()
        ]);

        try {
            $this->validateActiveUserHealthStatus($operationId);
            $this->cleanupStaleHealthRecords($operationId);
            
            Log::info('Health status validation job completed successfully', [
                'operation_id' => $operationId,
                'duration_ms' => $this->getExecutionTime()
            ]);
        } catch (\Exception $e) {
            Log::error('Health status validation job failed', [
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    private function validateActiveUserHealthStatus(string $operationId): void
    {
        // Find users who have been active in the last 7 days or have Google Drive tokens
        $activeUserIds = collect();
        
        // Users who have uploaded files recently (as company users or uploaded_by_user)
        $recentUploaderIds = \DB::table('file_uploads')
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('company_user_id')
            ->pluck('company_user_id')
            ->merge(
                \DB::table('file_uploads')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->whereNotNull('uploaded_by_user_id')
                    ->pluck('uploaded_by_user_id')
            );
            
        $activeUserIds = $activeUserIds->merge($recentUploaderIds);
        
        // Users who have Google Drive tokens
        $tokenUserIds = \DB::table('google_drive_tokens')->pluck('user_id');
        $activeUserIds = $activeUserIds->merge($tokenUserIds);
        
        $activeUsers = User::whereIn('id', $activeUserIds->unique())->get();

        Log::info('Found active users for health validation', [
            'operation_id' => $operationId,
            'count' => $activeUsers->count()
        ]);

        $healthValidator = app(RealTimeHealthValidator::class);
        $healthService = app(CloudStorageHealthService::class);
        
        $validatedCount = 0;
        $errorCount = 0;

        foreach ($activeUsers as $user) {
            try {
                // Validate Google Drive connection health
                $healthStatus = $healthValidator->validateConnectionHealth($user, 'google-drive');
                
                // Update consolidated status if needed
                $healthService->updateHealthStatus($user, 'google-drive', $healthStatus);
                
                $validatedCount++;
                
                Log::debug('Validated user health status', [
                    'operation_id' => $operationId,
                    'user_id' => $user->id,
                    'health_status' => $healthStatus->status,
                    'is_healthy' => $healthStatus->isHealthy()
                ]);
                
            } catch (\Exception $e) {
                $errorCount++;
                
                Log::warning('Failed to validate user health status', [
                    'operation_id' => $operationId,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Health status validation completed', [
            'operation_id' => $operationId,
            'total_users' => $activeUsers->count(),
            'validated' => $validatedCount,
            'errors' => $errorCount
        ]);
    }

    private function cleanupStaleHealthRecords(string $operationId): void
    {
        // Clean up health records older than 30 days for inactive users
        $cutoffDate = now()->subDays(30);
        
        $deletedCount = \DB::table('cloud_storage_health_statuses')
            ->where('updated_at', '<', $cutoffDate)
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('file_uploads')
                    ->where(function ($subQuery) {
                        $subQuery->whereColumn('file_uploads.company_user_id', 'cloud_storage_health_statuses.user_id')
                            ->orWhereColumn('file_uploads.uploaded_by_user_id', 'cloud_storage_health_statuses.user_id');
                    })
                    ->where('file_uploads.created_at', '>=', now()->subDays(30));
            })
            ->delete();

        Log::info('Cleaned up stale health records', [
            'operation_id' => $operationId,
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toISOString()
        ]);
    }

    private function getExecutionTime(): int
    {
        return (int) ((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
    }
}