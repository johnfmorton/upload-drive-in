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
    /**
     * Names of intended recipient users (admins/employees) for this upload.
     * @var array<int, string>
     */
    public array $recipientNames = [];

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
        $count = $this->fileUploads->count();

        return new Envelope(
            subject: trans_choice('messages.client_batch_upload_subject', $count, [
                'count'    => $count,
                'app_name' => config('app.name'),
            ]),
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
                'recipientNames' => $this->recipientNames,
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
