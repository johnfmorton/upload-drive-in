<?php

namespace App\Mail;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientUploadConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param FileUpload $file The model representing the uploaded file.
     * @param string $unsubscribeUrl The URL for the user to disable notifications.
     */
    public function __construct(
        public FileUpload $file,
        public string $unsubscribeUrl
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Use translation helper for subject
        return new Envelope(
            subject: __('messages.client_upload_subject', ['app_name' => config('app.name')]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Pass data to the markdown view
        return new Content(
            markdown: 'emails.client.upload-confirmation',
            with: [
                'fileName' => $this->file->original_filename ?? 'N/A',
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
