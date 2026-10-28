<?php

namespace App\Jobs;

use App\Models\Share;
use App\Services\ShareStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delete an ended (expired, revoked or failed) share's files from cloud
 * storage and the staging disk. Safe to run more than once; if it keeps
 * failing, PurgeExpiredShares dispatches it again every hour until
 * purged_at is set.
 */
class PurgeShareFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public Share $share
    ) {}

    public function handle(ShareStorageService $storage): void
    {
        $share = $this->share->fresh();

        if (! $share || ! $share->isEnded()) {
            return;
        }

        $storage->deleteShareFiles($share);

        $share->update(['purged_at' => now()]);

        Log::info('Purged share files', ['share' => $share->ulid, 'status' => $share->status]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Purging share files failed; will retry on the next hourly run', [
            'share' => $this->share->ulid,
            'error' => $exception->getMessage(),
        ]);
    }
}
