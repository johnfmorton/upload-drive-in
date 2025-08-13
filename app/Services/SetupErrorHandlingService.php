<?php

namespace App\Services;

use App\Exceptions\CloudStorageSetupException;
use App\Exceptions\DatabaseSetupException;
use App\Exceptions\SetupException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service for handling and formatting setup-related errors.
 * 
 * Provides centralized error handling, logging, and user-friendly
 * error message formatting for the setup wizard.
 */
class SetupErrorHandlingService
{
    /**
     * Handle and format a setup exception for display to the user.
     */
    public function handleSetupException(Throwable $exception, string $context = ''): array
    {
        // Log the full exception details
        Log::error('Setup error occurred', [
            'context' => $context,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Handle specific setup exceptions
        if ($exception instanceof SetupException) {
            return $this->formatSetupException($exception, $context);
        }

        // Handle generic exceptions
        return $this->formatGenericException($exception, $context);
    }

    /**
     * Format a SetupException for user display.
     */
    private function formatSetupException(SetupException $exception, string $context): array
    {
        $errorInfo = $exception->getErrorInfo();
        
        return [
            'type' => 'setup_error',
            'context' => $context,
            'user_message' => $errorInfo['message'],
            'technical_message' => $errorInfo['technical_message'],
            'troubleshooting_steps' => $errorInfo['troubleshooting_steps'],
            'has_troubleshooting' => $errorInfo['has_troubleshooting'],
            'error_context' => $errorInfo['context'],
            'severity' => $this->determineSeverity($exception),
            'recovery_suggestions' => $this->getRecoverySuggestions($exception),
            'documentation_links' => $this->getDocumentationLinks($exception)
        ];
    }

    /**
     * Format a generic exception for user display.
     */
    private function formatGenericException(Throwable $exception, string $context): array
    {
        $userMessage = $this->generateUserFriendlyMessage($exception, $context);
        $troubleshootingSteps = $this->generateGenericTroubleshootingSteps($context);

        return [
            'type' => 'generic_error',
            'context' => $context,
            'user_message' => $userMessage,
            'technical_message' => $exception->getMessage(),
            'troubleshooting_steps' => $troubleshootingSteps,
            'has_troubleshooting' => !empty($troubleshootingSteps),
            'error_context' => [
                'exception_type' => get_class($exception),
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine()
            ],
            'severity' => 'high',
            'recovery_suggestions' => $this->getGenericRecoverySuggestions($context),
            'documentation_links' => $this->getGenericDocumentationLinks()
        ];
    }

    /**
     * Generate user-friendly message for generic exceptions.
     */
    private function generateUserFriendlyMessage(Throwable $exception, string $context): string
    {
        return match ($context) {
            'database_setup' => 'An unexpected error occurred during database setup. Please check your configuration and try again.',
            'admin_creation' => 'An unexpected error occurred while creating the administrator account. Please verify your input and try again.',
            'storage_setup' => 'An unexpected error occurred during cloud storage setup. Please check your credentials and try again.',
            'system_check' => 'A system requirement check failed. Please ensure your server meets all requirements.',
            default => 'An unexpected error occurred during setup. Please check the logs and try again.'
        };
    }

    /**
     * Generate generic troubleshooting steps based on context.
     */
    private function generateGenericTroubleshootingSteps(string $context): array
    {
        return match ($context) {
            'database_setup' => [
                'Check that your database server is running and accessible',
                'Verify that your database credentials are correct',
                'Ensure that the database user has proper permissions',
                'Check the Laravel logs for detailed error information',
                'Try refreshing the page and attempting the setup again'
            ],
            'admin_creation' => [
                'Ensure all required fields are filled correctly',
                'Check that the email address is valid and not already in use',
                'Verify that the password meets security requirements',
                'Check the Laravel logs for detailed error information',
                'Try using a different email address if the current one fails'
            ],
            'storage_setup' => [
                'Verify that your cloud storage credentials are correct',
                'Check that your internet connection is stable',
                'Ensure that the cloud storage API is accessible',
                'Check the Laravel logs for detailed error information',
                'Try re-entering your credentials if they appear to be incorrect'
            ],
            'system_check' => [
                'Ensure that all required PHP extensions are installed',
                'Check that file permissions are set correctly',
                'Verify that your PHP version meets the minimum requirements',
                'Check server logs for additional error information',
                'Contact your hosting provider if server configuration issues persist'
            ],
            default => [
                'Check the Laravel logs for detailed error information',
                'Ensure that all system requirements are met',
                'Try refreshing the page and attempting the operation again',
                'Contact support if the problem persists'
            ]
        };
    }

    /**
     * Determine the severity level of an exception.
     */
    private function determineSeverity(SetupException $exception): string
    {
        return match (true) {
            $exception instanceof DatabaseSetupException => 'critical',
            $exception instanceof CloudStorageSetupException => 'high',
            default => 'medium'
        };
    }

    /**
     * Get recovery suggestions for specific exceptions.
     */
    private function getRecoverySuggestions(SetupException $exception): array
    {
        if ($exception instanceof DatabaseSetupException) {
            return [
                'Try using SQLite instead of MySQL if you\'re having connection issues',
                'Check with your hosting provider about database configuration',
                'Consider using a different database name if the current one has issues',
                'Verify that your database server version is supported'
            ];
        }

        if ($exception instanceof CloudStorageSetupException) {
            return [
                'Double-check your credentials in the Google Cloud Console',
                'Try creating new OAuth 2.0 credentials if the current ones don\'t work',
                'Ensure that your Google Cloud project is properly configured',
                'Consider testing with a different Google account if available'
            ];
        }

        return [
            'Try restarting the setup process from the beginning',
            'Check that all system requirements are met',
            'Contact support if the issue persists'
        ];
    }

    /**
     * Get generic recovery suggestions.
     */
    private function getGenericRecoverySuggestions(string $context): array
    {
        return [
            'Try refreshing the page and attempting the operation again',
            'Check that all required information is entered correctly',
            'Ensure that your server meets all system requirements',
            'Contact support if the problem continues'
        ];
    }

    /**
     * Get documentation links for specific exceptions.
     */
    private function getDocumentationLinks(SetupException $exception): array
    {
        if ($exception instanceof DatabaseSetupException) {
            return [
                [
                    'title' => 'Database Configuration Guide',
                    'url' => '/docs/database-setup',
                    'description' => 'Learn how to configure MySQL and SQLite databases'
                ],
                [
                    'title' => 'Troubleshooting Database Issues',
                    'url' => '/docs/database-troubleshooting',
                    'description' => 'Common database setup problems and solutions'
                ]
            ];
        }

        if ($exception instanceof CloudStorageSetupException) {
            return [
                [
                    'title' => 'Google Drive Setup Guide',
                    'url' => '/docs/google-drive-setup',
                    'description' => 'Step-by-step guide to configure Google Drive integration'
                ],
                [
                    'title' => 'Google Cloud Console Setup',
                    'url' => 'https://console.developers.google.com/',
                    'description' => 'Create and manage your Google Drive API credentials'
                ]
            ];
        }

        return [];
    }

    /**
     * Get generic documentation links.
     */
    private function getGenericDocumentationLinks(): array
    {
        return [
            [
                'title' => 'Setup Guide',
                'url' => '/docs/setup',
                'description' => 'Complete setup documentation'
            ],
            [
                'title' => 'System Requirements',
                'url' => '/docs/requirements',
                'description' => 'Server and system requirements'
            ]
        ];
    }

    /**
     * Check if an error is recoverable.
     */
    public function isRecoverable(Throwable $exception): bool
    {
        // Database connection errors are often recoverable with correct configuration
        if ($exception instanceof DatabaseSetupException) {
            return true;
        }

        // Cloud storage credential errors are recoverable
        if ($exception instanceof CloudStorageSetupException) {
            return true;
        }

        // File permission errors might be recoverable
        if (str_contains($exception->getMessage(), 'permission') || 
            str_contains($exception->getMessage(), 'writable')) {
            return true;
        }

        // Network errors are often temporary
        if (str_contains($exception->getMessage(), 'network') || 
            str_contains($exception->getMessage(), 'connection')) {
            return true;
        }

        return false;
    }

    /**
     * Get suggested next steps based on the error.
     */
    public function getNextSteps(Throwable $exception, string $context): array
    {
        if (!$this->isRecoverable($exception)) {
            return [
                'Contact system administrator for assistance',
                'Check server logs for additional information',
                'Consider alternative configuration options'
            ];
        }

        return match ($context) {
            'database_setup' => [
                'Verify database configuration and try again',
                'Consider switching to SQLite if MySQL is problematic',
                'Check with hosting provider about database access'
            ],
            'storage_setup' => [
                'Double-check your Google Drive credentials',
                'Try re-creating your OAuth 2.0 credentials',
                'Ensure your Google Cloud project is properly configured'
            ],
            'admin_creation' => [
                'Verify all form fields are filled correctly',
                'Try using a different email address',
                'Ensure password meets all requirements'
            ],
            default => [
                'Review the error details and troubleshooting steps',
                'Try the operation again after addressing any issues',
                'Contact support if problems persist'
            ]
        };
    }
}