<?php

namespace App\Mail;

use App\Models\Share;
use App\Models\ShareFile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Tells a sender that someone downloaded from their share for the first time.
 */
class ShareDownloadedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Share $share,
        public ShareFile $file
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.shares_email_downloaded_subject', ['title' => $this->share->title]),
        );
    }

    public function content(): Content
    {
        $sender = $this->share->user;

        return new Content(
            markdown: 'emails.shares.downloaded',
            with: [
                'share' => $this->share,
                'fileName' => $this->file->original_filename,
                'downloadedAt' => $this->share->first_downloaded_at ?? now(),
                'shareUrl' => $sender->isAdmin()
                    ? route('admin.shares.show', ['share' => $this->share])
                    : route('employee.shares.show', ['username' => $sender->username, 'share' => $this->share]),
                'unsubscribeUrl' => URL::temporarySignedRoute(
                    'notifications.share-downloads.unsubscribe',
                    now()->addDays(30),
                    ['user' => $sender->id]
                ),
            ],
        );
    }
}
