<?php

namespace App\Listeners;

use App\Events\ShareFirstDownloaded;
use App\Mail\ShareDownloadedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Email the sender the first time someone downloads from their share,
 * unless they've turned these emails off.
 */
class SendShareDownloadedNotification implements ShouldQueue
{
    public function handle(ShareFirstDownloaded $event): void
    {
        $sender = $event->share->user;

        if (! $sender?->email || ! $sender->receive_share_download_notifications) {
            return;
        }

        Mail::to($sender->email)->send(new ShareDownloadedMail($event->share, $event->file));
    }
}
