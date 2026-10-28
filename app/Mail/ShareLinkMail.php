<?php

namespace App\Mail;

use App\Models\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends a share's download link to one of the recipients the sender named.
 */
class ShareLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Share $share
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.shares_email_link_subject', [
                'name' => $this->share->user?->name ?? config('app.name'),
                'title' => $this->share->title,
            ]),
        );
    }

    public function content(): Content
    {
        $files = $this->share->files();

        return new Content(
            markdown: 'emails.shares.link',
            with: [
                'share' => $this->share,
                'senderName' => $this->share->user?->name ?? config('app.name'),
                'fileCount' => $files->count(),
                'totalSize' => (int) $files->sum('file_size'),
                'url' => $this->share->publicUrl(),
            ],
        );
    }
}
