<?php

namespace App\Mail;

/**
 * Client-specific email verification mail class.
 * 
 * This class provides client users with verification emails that include
 * messaging about uploading files and the file upload process.
 */
class ClientVerificationMail extends BaseVerificationMail
{
    /**
     * Get the email template path for client verification.
     *
     * @return string The client verification template path
     */
    protected function getTemplate(): string
    {
        return 'emails.verification.client-verification';
    }

    /**
     * Get the email subject line for client verification.
     *
     * @return string The client-specific subject line from language files
     */
    protected function getSubject(): string
    {
        return __('messages.client_verify_email_subject');
    }
}