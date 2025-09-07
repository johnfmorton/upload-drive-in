<?php

namespace App\Services;

use App\Mail\AdminVerificationMail;
use App\Mail\ClientVerificationMail;
use App\Mail\EmployeeVerificationMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

/**
 * Factory service for creating role-based email verification mail instances.
 * 
 * This service handles the logic for determining which verification email template
 * to use based on user roles or context strings, with proper fallback behavior
 * and debugging support.
 */
class VerificationMailFactory
{
    /**
     * Create a verification mail instance for a specific user.
     * 
     * This method analyzes the user's role and returns the appropriate
     * verification mail class. If no user is provided or role detection fails,
     * it falls back to the client verification template.
     *
     * @param User|null $user The user to create verification mail for
     * @param string $verificationUrl The verification URL to include in the email
     * @return Mailable The appropriate verification mail instance
     */
    public function createForUser(?User $user, string $verificationUrl): Mailable
    {
        $userRole = null;
        $mailClass = null;

        if ($user) {
            if ($user->isAdmin()) {
                $userRole = 'admin';
                $mailClass = AdminVerificationMail::class;
            } elseif ($user->isEmployee()) {
                $userRole = 'employee';
                $mailClass = EmployeeVerificationMail::class;
            } elseif ($user->isClient()) {
                $userRole = 'client';
                $mailClass = ClientVerificationMail::class;
            }
        }

        // Fallback to client verification for unknown roles or null user
        if (!$mailClass) {
            $userRole = 'client';
            $mailClass = ClientVerificationMail::class;
        }

        // Log the template selection for debugging
        Log::info('Email verification template selected', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'detected_role' => $userRole,
            'mail_class' => $mailClass,
            'verification_url' => $verificationUrl,
            'method' => 'createForUser'
        ]);

        return new $mailClass($verificationUrl, $userRole);
    }

    /**
     * Create a verification mail instance for a specific context string.
     * 
     * This method allows for explicit role selection using string contexts,
     * useful when the user object is not available but the role context is known.
     *
     * @param string $context The role context ('admin', 'employee', 'client')
     * @param string $verificationUrl The verification URL to include in the email
     * @return Mailable The appropriate verification mail instance
     */
    public function createForContext(string $context, string $verificationUrl): Mailable
    {
        $mailClass = match (strtolower($context)) {
            'admin' => AdminVerificationMail::class,
            'employee' => EmployeeVerificationMail::class,
            'client' => ClientVerificationMail::class,
            default => ClientVerificationMail::class
        };

        // Normalize context for logging
        $normalizedContext = strtolower($context);
        $selectedRole = match ($mailClass) {
            AdminVerificationMail::class => 'admin',
            EmployeeVerificationMail::class => 'employee',
            default => 'client'
        };

        // Log the template selection for debugging
        Log::info('Email verification template selected', [
            'requested_context' => $context,
            'normalized_context' => $normalizedContext,
            'selected_role' => $selectedRole,
            'mail_class' => $mailClass,
            'verification_url' => $verificationUrl,
            'method' => 'createForContext'
        ]);

        return new $mailClass($verificationUrl, $selectedRole);
    }

    /**
     * Get the available verification contexts.
     * 
     * This method returns an array of valid context strings that can be used
     * with the createForContext method.
     *
     * @return array<string> Array of valid context strings
     */
    public function getAvailableContexts(): array
    {
        return ['admin', 'employee', 'client'];
    }

    /**
     * Determine the appropriate context for a user.
     * 
     * This is a helper method that returns the string context for a given user,
     * useful for debugging or when you need the context string rather than the mail instance.
     *
     * @param User|null $user The user to determine context for
     * @return string The context string ('admin', 'employee', or 'client')
     */
    public function determineContextForUser(?User $user): string
    {
        if (!$user) {
            return 'client';
        }

        if ($user->isAdmin()) {
            return 'admin';
        }

        if ($user->isEmployee()) {
            return 'employee';
        }

        return 'client';
    }
}