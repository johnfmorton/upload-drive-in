<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ClearOldUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:clear-old {--hours=24}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear uploaded files older than a specified number of hours (default: 24)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        if ($hours <= 0) {
            $this->error('Please provide a positive number of hours.');
            return 1;
        }

        $directory = 'uploads';
        $disk = Storage::disk('public');
        $deletedCount = 0;
        $cutoff = Carbon::now()->subHours($hours);

        $this->info("Clearing files older than {$hours} hours from 'storage/app/public/{$directory}'...");

        if (!$disk->exists($directory)) {
            $this->info("Directory '{$directory}' does not exist. Nothing to clear.");
            return 0;
        }

        $files = $disk->files($directory);

        foreach ($files as $file) {
            $lastModifiedTimestamp = $disk->lastModified($file);
            $lastModified = Carbon::createFromTimestamp($lastModifiedTimestamp);

            if ($lastModified->lt($cutoff)) {
                Log::channel('uploads_cleanup')->info("Deleting old uploaded file: {$file}");
                $disk->delete($file);
                $deletedCount++;
                $this->line("Deleted: {$file}");
            }
        }

        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} old file(s).");
        } else {
            $this->info('No old files found to delete.');
        }

        return 0;
    }
}
