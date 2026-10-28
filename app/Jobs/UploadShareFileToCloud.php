<?php

namespace App\Jobs;

use App\Models\ShareFile;
use App\Services\ShareService;
use App\Services\ShareStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Copy one staged share file to the share's cloud storage, then delete the
 * local copy and mark the share ready if this was the last file.
 */
class UploadShareFileToCloud implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Multi-GB uploads can take a long time
    public int $timeout = 3600;

    // Failures that throw are retried 3 times; releases while another worker
    // holds the lock don't count against that (see retryUntil)
    public int $maxExceptions = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public ShareFile $shareFile
    ) {}

    public function retryUntil(): \DateTime
    {
        return now()->addHours(12);
    }

    public function handle(ShareStorageService $storage, ShareService $shares): void
    {
        // If the queue's retry_after is shorter than the upload, the job can be
        // started again while it's still running; only one run may upload
        $lock = Cache::lock("share-file-upload:{$this->shareFile->id}", $this->timeout + 60);

        if (! $lock->get()) {
            $this->release(300);

            return;
        }

        try {
            $this->upload($storage, $shares);
        } finally {
            $lock->release();
        }
    }

    private function upload(ShareStorageService $storage, ShareService $shares): void
    {
        $file = ShareFile::with('share')->find($this->shareFile->id);

        // Removed by the sender, or already uploaded by an earlier run
        if (! $file || $file->isUploaded()) {
            return;
        }

        if ($file->share->isEnded()) {
            $this->deleteLocalCopy($file);

            return;
        }

        $providerFileId = $storage->upload($file);

        $file->refresh();
        $file->update([
            'provider_file_id' => $providerFileId,
            'status' => ShareFile::STATUS_UPLOADED,
            'last_error' => null,
        ]);

        $this->deleteLocalCopy($file);

        $share = $file->share->fresh();

        // Revoked or expired while uploading: the purge may already have run
        // without knowing about this file, so remove it again
        if ($share->isEnded()) {
            PurgeShareFiles::dispatch($share);

            return;
        }

        $shares->markReadyIfComplete($share);
    }

    private function deleteLocalCopy(ShareFile $file): void
    {
        if ($file->local_path) {
            Storage::disk('local')->delete($file->local_path);
            $file->update(['local_path' => null]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $file = ShareFile::find($this->shareFile->id);

        // A duplicate run can fail after another run already succeeded
        if (! $file || $file->isUploaded()) {
            return;
        }

        $file->update([
            'status' => ShareFile::STATUS_FAILED,
            'last_error' => $exception->getMessage(),
        ]);

        Log::error('Share file upload to cloud storage failed', [
            'share_file_id' => $file->id,
            'share_id' => $file->share_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
