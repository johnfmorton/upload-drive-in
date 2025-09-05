<?php

namespace App\Jobs;

use App\Models\GoogleDriveToken;
use App\Models\CloudStorageHealthStatus;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CleanupFailedRefreshAttemptsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $operationId = uniqid('cleanup_failed_refresh_');
        
        Log::info('Cleanup failed refresh attempts job started', [
            'operation_id' => $operationId,
            'timestamp' => now()->toISOString()
        ]);

        try {
            $this->cleanupOldFailedRefreshRecords($operationId);
            $this->resetStaleRefreshFlags($operationId);
            $this->cleanupOldNotificationRecords($operationId);
            $this->cleanupOrphanedHealthRecords($operationId);
            
            Log::info('Cleanup failed refresh attempts job completed successfully', [
                'operation_id' => $operationId,
                'duration_ms' => $this->getExecutionTime()
            ]);
        } catch (\Exception $e) {
            Log::error('Cleanup failed refresh attempts job failed', [
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    private function cleanupOldFailedRefreshRecords(string $operationId): void
    {
        // Reset failure counts for tokens with old failed attempts (older than 7 days)
        $cutoffDate = now()->subDays(7);
        
        $updated = GoogleDriveToken::where('last_refresh_attempt_at', '<', $cutoffDate)
            ->where('refresh_failure_count', '>', 0)
            ->update([
                'refresh_failure_count' => 0,
                'last_refresh_attempt_at' => null
            ]);

        Log::info('Reset old failed refresh records', [
            'operation_id' => $operationId,
            'updated_count' => $updated,
            'cutoff_date' => $cutoffDate->toISOString()
        ]);
    }

    private function resetStaleRefreshFlags(string $operationId): void
    {
        // Reset proactive refresh scheduled flags for tokens that should have been refreshed
        $cutoffDate = now()->subHours(2); // If scheduled more than 2 hours ago, reset
        
        $updated = GoogleDriveToken::whereNotNull('proactive_refresh_scheduled_at')
            ->where('proactive_refresh_scheduled_at', '<', $cutoffDate)
            ->where('expires_at', '>', now()) // Only if token hasn't expired yet
            ->update([
                'proactive_refresh_scheduled_at' => null
            ]);

        Log::info('Reset stale proactive refresh flags', [
            'operation_id' => $operationId,
            'updated_count' => $updated,
            'cutoff_date' => $cutoffDate->toISOString()
        ]);
    }

    private function cleanupOldNotificationRecords(string $operationId): void
    {
        // Reset notification tracking for old notifications (older than 30 days)
        $cutoffDate = now()->subDays(30);
        
        $updated = GoogleDriveToken::where('last_notification_sent_at', '<', $cutoffDate)
            ->whereNotNull('last_notification_sent_at')
            ->update([
                'last_notification_sent_at' => null,
                'notification_failure_count' => 0
            ]);

        Log::info('Reset old notification records', [
            'operation_id' => $operationId,
            'updated_count' => $updated,
            'cutoff_date' => $cutoffDate->toISOString()
        ]);
    }

    private function cleanupOrphanedHealthRecords(string $operationId): void
    {
        // Clean up health status records for users that no longer exist
        $deletedCount = DB::table('cloud_storage_health_statuses')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'cloud_storage_health_statuses.user_id');
            })
            ->delete();

        Log::info('Cleaned up orphaned health records', [
            'operation_id' => $operationId,
            'deleted_count' => $deletedCount
        ]);

        // Clean up very old health records (older than 90 days) for inactive users
        $veryOldCutoff = now()->subDays(90);
        
        // Clean up very old health records for users with no recent file activity
        $oldRecordsDeleted = DB::table('cloud_storage_health_statuses')
            ->where('updated_at', '<', $veryOldCutoff)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('file_uploads')
                    ->where(function ($subQuery) {
                        $subQuery->whereColumn('file_uploads.company_user_id', 'cloud_storage_health_statuses.user_id')
                            ->orWhereColumn('file_uploads.uploaded_by_user_id', 'cloud_storage_health_statuses.user_id');
                    })
                    ->where('file_uploads.created_at', '>=', now()->subDays(30));
            })
            ->delete();

        Log::info('Cleaned up very old health records', [
            'operation_id' => $operationId,
            'deleted_count' => $oldRecordsDeleted,
            'cutoff_date' => $veryOldCutoff->toISOString()
        ]);
    }

    private function getExecutionTime(): int
    {
        return (int) ((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
    }
}