<?php

namespace App\Listeners;

use App\Events\ShareReady;
use App\Mail\ShareLinkMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email the link to the recipients the sender named, one message each so
 * recipients don't see each other's addresses.
 */
class SendShareLinkToRecipients implements ShouldQueue
{
    public function handle(ShareReady $event): void
    {
        $share = $event->share->fresh('user');

        if (! $share || ! $share->isAvailable() || empty($share->recipient_emails)) {
            return;
        }

        foreach ($share->recipient_emails as $email) {
            try {
                Mail::to($email)->send(new ShareLinkMail($share));
            } catch (\Throwable $e) {
                Log::error('Failed to email share link', [
                    'share' => $share->ulid,
                    'recipient' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
