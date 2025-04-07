<?php

namespace App\Console\Commands;

use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingUploads extends Command
{
    protected $signature = 'uploads:process-pending';
    protected $description = 'Process pending file uploads to Google Drive';

    public function handle()
    {
        $this->info('Starting to process pending uploads...');
        Log::info('Starting to process pending uploads');

        $pendingUploads = FileUpload::whereNull('google_drive_file_id')
                                    ->orWhere('google_drive_file_id', '')
                                    ->get();
        $count = $pendingUploads->count();

        Log::info('Found pending uploads', ['count' => $count]);
        $this->info("Found {$count} pending uploads.");

        if ($count === 0) {
            $this->info('No pending uploads found.');
            return;
        }

        foreach ($pendingUploads as $upload) {
            try {
                Log::info('Processing upload', [
                    'file_id' => $upload->id,
                    'filename' => $upload->filename,
                    'original_filename' => $upload->original_filename,
                    'email' => $upload->email,
                    'exists_in_storage' => \Storage::disk('public')->exists('uploads/' . $upload->filename)
                ]);

                UploadToGoogleDrive::dispatch($upload);
                $this->info("Dispatched upload job for file: {$upload->original_filename}");
                Log::info('Dispatched Google Drive upload job', [
                    'file_id' => $upload->id,
                    'file' => $upload->original_filename,
                    'email' => $upload->email
                ]);
            } catch (\Exception $e) {
                $this->error("Failed to dispatch job for file: {$upload->original_filename}");
                Log::error('Failed to dispatch Google Drive upload job', [
                    'file' => $upload->original_filename,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->info('Finished processing pending uploads.');
        Log::info('Finished processing pending uploads');
    }
}
