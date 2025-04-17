<?php

namespace App\Services\CloudStorage;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

abstract class AbstractCloudStorageProvider implements CloudStorageProvider
{
    /**
     * Sanitizes an email address to create a valid and unique folder name.
     * Replaces special characters to avoid issues with folder naming conventions.
     *
     * @param string $email The email address to sanitize
     * @return string The sanitized string suitable for a folder name component
     */
    protected function sanitizeEmailForFolderName(string $email): string
    {
        $sanitized = str_replace(['@', '.'], ['-at-', '-dot-'], $email);
        // Replace any remaining non-alphanumeric characters (except hyphen) with a hyphen
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '-', $sanitized);
        // Replace multiple consecutive hyphens with a single hyphen
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        // Remove leading and trailing hyphens
        return trim($sanitized, '-');
    }

    /**
     * Verifies that a local file exists before attempting operations.
     *
     * @param string $localPath The path to check, relative to the public storage disk
     * @throws \Exception If the file doesn't exist
     */
    protected function verifyLocalFile(string $localPath): void
    {
        if (!Storage::disk('public')->exists($localPath)) {
            Log::error('Local file not found for cloud storage operation.', ['path' => $localPath]);
            throw new \Exception("Local file not found: {$localPath}");
        }
    }

    /**
     * Generates a standard user folder name based on email.
     *
     * @param string $email The user's email address
     * @return string The standardized folder name
     */
    protected function getUserFolderName(string $email): string
    {
        $sanitizedEmail = $this->sanitizeEmailForFolderName($email);
        return "User: {$sanitizedEmail}";
    }

    /**
     * Logs an operation with consistent formatting.
     *
     * @param string $message The log message
     * @param array $context Additional context data
     * @param string $level Log level (info, error, warning, debug)
     */
    protected function log(string $message, array $context = [], string $level = 'info'): void
    {
        $context['provider'] = $this->getProviderName();
        Log::{$level}("[{$this->getProviderName()}] {$message}", $context);
    }
}
