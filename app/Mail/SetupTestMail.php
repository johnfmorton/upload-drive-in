<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SetupTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $testId,
        public string $appName,
        public string $sentAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Email - ' . $this->appName . ' Setup',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.setup-test',
        );
    }
}
