<?php

namespace App\Mail;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminBatchUploadNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param Collection<int, FileUpload> $fileUploads Collection of uploaded file models.
     * @param User $user The user who uploaded the files.
     */
    public function __construct(
        public Collection $fileUploads,
        public User $user
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.admin_batch_upload_subject', [ //* New translation key needed
                'count' => $this->fileUploads->count(),
                'userName' => $this->user->name,
                'app_name' => config('app.name')
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.batch-upload-notification', // New view file needed
            with: [
                'fileUploads' => $this->fileUploads, // Pass the whole collection
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
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
