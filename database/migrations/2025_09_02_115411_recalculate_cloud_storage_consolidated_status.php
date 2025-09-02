<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Enums\UserRole;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration recalculates consolidated_status for all existing
     * cloud storage health records to fix inconsistencies between
     * the status and consolidated_status fields.
     */
    public function up(): void
    {
        Log::info('Starting cloud storage consolidated status recalculation migration');
        
        // Get all health status records that might have inconsistent data
        $healthRecords = CloudStorageHealthStatus::whereNotNull('status')
            ->where(function ($query) {
                $query->whereNull('consolidated_status')
                      ->orWhere(function ($subQuery) {
                          // Find records where status is healthy but consolidated shows not_connected
                          $subQuery->where('status', 'healthy')
                                   ->where('consolidated_status', 'not_connected');
                      })
                      ->orWhere(function ($subQuery) {
                          // Find records with recent successful operations but showing not_connected
                          $subQuery->where('consolidated_status', 'not_connected')
                                   ->whereNotNull('last_successful_operation_at')
                                   ->where('last_successful_operation_at', '>', now()->subHours(24));
                      });
            })
            ->get();

        if ($healthRecords->isEmpty()) {
            Log::info('No cloud storage health records need recalculation');
            return;
        }

        Log::info("Found {$healthRecords->count()} health records that need recalculation");

        $healthService = app(CloudStorageHealthService::class);
        $fixed = 0;
        $errors = 0;

        foreach ($healthRecords as $record) {
            try {
                $user = User::find($record->user_id);
                if (!$user) {
                    Log::warning("User not found for health record", [
                        'health_record_id' => $record->id,
                        'user_id' => $record->user_id
                    ]);
                    continue;
                }

                $oldStatus = $record->consolidated_status;
                $newStatus = $healthService->determineConsolidatedStatus($user, $record->provider);
                
                if ($oldStatus !== $newStatus) {
                    $record->update(['consolidated_status' => $newStatus]);
                    $fixed++;
                    
                    Log::info('Migration: Updated consolidated status', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'provider' => $record->provider,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ]);
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Migration: Failed to recalculate status for record', [
                    'health_record_id' => $record->id,
                    'user_id' => $record->user_id,
                    'provider' => $record->provider,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Cloud storage consolidated status recalculation migration completed', [
            'total_records_checked' => $healthRecords->count(),
            'records_fixed' => $fixed,
            'errors' => $errors,
        ]);

        // Output summary for console
        echo "Cloud Storage Health Status Migration Summary:\n";
        echo "- Records checked: {$healthRecords->count()}\n";
        echo "- Records fixed: {$fixed}\n";
        echo "- Errors: {$errors}\n";
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This migration only updates data, so there's no meaningful
     * way to reverse it. The down method is left empty intentionally.
     */
    public function down(): void
    {
        // This is a data migration that recalculates values based on current logic.
        // There's no meaningful way to reverse this operation since we're fixing
        // inconsistent data to match the current business logic.
        Log::info('Cloud storage consolidated status migration rollback - no action needed');
    }
};
