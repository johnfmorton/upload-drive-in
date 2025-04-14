<?php

namespace App\Mail;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientBatchUploadConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param Collection<int, FileUpload> $fileUploads Collection of uploaded file models.
     * @param string $unsubscribeUrl The URL for the user to disable notifications.
     */
    public function __construct(
        public Collection $fileUploads,
        public string $unsubscribeUrl
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.client_batch_upload_subject', ['app_name' => config('app.name')]), //* New translation key needed
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client.batch-upload-confirmation', // New view file needed
            with: [
                'fileCount' => $this->fileUploads->count(),
                'fileNames' => $this->fileUploads->pluck('original_filename')->all(), // Pass just the names
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
