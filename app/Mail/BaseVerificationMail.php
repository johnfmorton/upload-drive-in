<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Abstract base class for all email verification mail classes.
 * 
 * This class provides shared functionality for role-based email verification
 * while allowing each role to customize their template and subject line.
 */
abstract class BaseVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The verification URL for the email.
     */
    public string $verificationUrl;

    /**
     * The user role for context (optional).
     */
    public ?string $userRole;

    /**
     * The company name for personalization.
     */
    public string $companyName;

    /**
     * Create a new message instance.
     *
     * @param string $verificationUrl The URL for email verification
     * @param string|null $userRole The user role for context
     */
    public function __construct(string $verificationUrl, ?string $userRole = null)
    {
        $this->verificationUrl = $verificationUrl;
        $this->userRole = $userRole;
        $this->companyName = config('app.company_name', config('app.name'));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getSubject(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: $this->getTemplate(),
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

    /**
     * Get the email template path for this verification type.
     * 
     * Each role-specific mail class must implement this method
     * to return the appropriate template path.
     *
     * @return string The template path (e.g., 'emails.verification.admin-verification')
     */
    abstract protected function getTemplate(): string;

    /**
     * Get the email subject line for this verification type.
     * 
     * Each role-specific mail class must implement this method
     * to return the appropriate subject line, typically from language files.
     *
     * @return string The email subject line
     */
    abstract protected function getSubject(): string;
}