<?php

namespace App\Jobs;

use App\Models\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Hourly share cleanup: expire shares past their date, give up on shares
 * that never finished uploading, and purge the files of every ended share
 * that hasn't been purged yet (which also retries earlier failed purges).
 */
class PurgeExpiredShares implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expired = Share::where('status', Share::STATUS_READY)
            ->where('expires_at', '<=', now())
            ->update(['status' => Share::STATUS_EXPIRED]);

        $abandoned = Share::whereIn('status', [Share::STATUS_DRAFT, Share::STATUS_UPLOADING])
            ->where('created_at', '<', now()->subHours(config('shares.stale_upload_hours')))
            ->update(['status' => Share::STATUS_FAILED]);

        $purging = 0;

        Share::whereIn('status', [Share::STATUS_EXPIRED, Share::STATUS_REVOKED, Share::STATUS_FAILED])
            ->whereNull('purged_at')
            ->lazyById()
            ->each(function (Share $share) use (&$purging) {
                PurgeShareFiles::dispatch($share);
                $purging++;
            });

        if ($expired || $abandoned || $purging) {
            Log::info('Share cleanup', compact('expired', 'abandoned', 'purging'));
        }
    }
}
