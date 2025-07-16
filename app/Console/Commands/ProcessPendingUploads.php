<?php

namespace App\Console\Commands;

use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessPendingUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:process-pending 
                            {--user-id= : Process pending uploads for a specific user ID}
                            {--dry-run : Show what would be processed without actually processing}
                            {--limit=50 : Maximum number of uploads to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending file uploads that failed to upload to Google Drive';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->option('user-id');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Processing pending uploads...');

        // Build query for pending uploads (no google_drive_file_id)
        $query = FileUpload::whereNull('google_drive_file_id')
            ->orWhere('google_drive_file_id', '');

        if ($userId) {
            // Process for specific user (either as uploader or target)
            $query->where(function ($q) use ($userId) {
                $q->where('uploaded_by_user_id', $userId)
                  ->orWhere('company_user_id', $userId);
            });
        }

        $pendingUploads = $query->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($pendingUploads->isEmpty()) {
            $this->info('No pending uploads found.');
            return self::SUCCESS;
        }

        $this->info("Found {$pendingUploads->count()} pending uploads.");

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($pendingUploads as $upload) {
            $this->line("Processing upload ID {$upload->id}: {$upload->original_filename}");

            // Check if local file still exists
            $localPath = 'uploads/' . $upload->filename;
            if (!Storage::disk('public')->exists($localPath)) {
                $this->warn("  ⚠️  Local file missing: {$localPath}");
                $skipped++;
                continue;
            }

            // Determine target user for Google Drive
            $targetUser = $this->getTargetUser($upload);
            if (!$targetUser) {
                $this->error("  ❌ No valid target user found");
                $errors++;
                continue;
            }

            if (!$targetUser->hasGoogleDriveConnected()) {
                $this->warn("  ⚠️  Target user {$targetUser->email} has no Google Drive connection");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->info("  🔍 [DRY RUN] Would process with user: {$targetUser->email}");
                $processed++;
                continue;
            }

            try {
                // Dispatch the upload job
                UploadToGoogleDrive::dispatch($upload);
                $this->info("  ✅ Queued for processing with user: {$targetUser->email}");
                $processed++;
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to queue: {$e->getMessage()}");
                $errors++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->info("  Processed: {$processed}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Errors: {$errors}");

        if ($dryRun) {
            $this->info('This was a dry run. Use without --dry-run to actually process the uploads.');
        }

        return self::SUCCESS;
    }

    /**
     * Determine the target user for Google Drive upload.
     */
    private function getTargetUser(FileUpload $upload): ?User
    {
        // If uploaded by a specific user (employee), try to use them
        if ($upload->uploaded_by_user_id) {
            $user = User::find($upload->uploaded_by_user_id);
            if ($user && $user->hasGoogleDriveConnected()) {
                return $user;
            }
        }

        // If there's a company user specified, try to use them
        if ($upload->company_user_id) {
            $user = User::find($upload->company_user_id);
            if ($user && $user->hasGoogleDriveConnected()) {
                return $user;
            }
        }

        // Fallback to any admin user with Google Drive connected
        return User::where('role', UserRole::ADMIN)
            ->whereHas('googleDriveToken')
            ->first();
    }
}