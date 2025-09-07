<?php

namespace App\Mail;

/**
 * Admin-specific email verification mail class.
 * 
 * This class provides admin users with verification emails that include
 * messaging about administrative capabilities and system management responsibilities.
 */
class AdminVerificationMail extends BaseVerificationMail
{
    /**
     * Get the email template path for admin verification.
     *
     * @return string The admin verification template path
     */
    protected function getTemplate(): string
    {
        return 'emails.verification.admin-verification';
    }

    /**
     * Get the email subject line for admin verification.
     *
     * @return string The admin-specific subject line from language files
     */
    protected function getSubject(): string
    {
        return __('messages.admin_verify_email_subject');
    }
}