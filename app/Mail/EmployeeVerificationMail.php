<?php

namespace App\Mail;

/**
 * Employee-specific email verification mail class.
 * 
 * This class provides employee users with verification emails that include
 * messaging about receiving client uploads and Google Drive integration.
 */
class EmployeeVerificationMail extends BaseVerificationMail
{
    /**
     * Get the email template path for employee verification.
     *
     * @return string The employee verification template path
     */
    protected function getTemplate(): string
    {
        return 'emails.verification.employee-verification';
    }

    /**
     * Get the email subject line for employee verification.
     *
     * @return string The employee-specific subject line from language files
     */
    protected function getSubject(): string
    {
        return __('messages.employee_verify_email_subject');
    }
}