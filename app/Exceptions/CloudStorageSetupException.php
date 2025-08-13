<?php

namespace App\Exceptions;

/**
 * Exception for cloud storage setup-related errors.
 * 
 * Provides specific error handling for cloud storage API failures,
 * credential validation, and configuration issues.
 */
class CloudStorageSetupException extends SetupException
{
    /**
     * Create exception for Google Drive API connection failure.
     */
    public static function googleDriveConnectionFailed(string $technicalError, array $context = []): self
    {
        $userMessage = 'Unable to connect to Google Drive API. Please verify your credentials.';
        
        $troubleshootingSteps = [
            'Verify that your Google Drive Client ID is correct and complete',
            'Ensure your Google Drive Client Secret is correct and not expired',
            'Check that the Google Drive API is enabled in your Google Cloud Console',
            'Verify that your OAuth consent screen is properly configured',
            'Ensure your redirect URI matches exactly what is configured in Google Cloud Console',
            'Check that your Google Cloud project has the Drive API enabled',
            'Verify that your credentials are not from a test/development project if in production'
        ];

        return new self(
            "Google Drive connection failed: {$technicalError}",
            500,
            null,
            $userMessage,
            $troubleshootingSteps,
            array_merge(['provider' => 'google-drive'], $context)
        );
    }

    /**
     * Create exception for invalid Google Drive credentials.
     */
    public static function invalidGoogleDriveCredentials(array $validationErrors): self
    {
        $userMessage = 'Google Drive credentials are invalid. Please check your Client ID and Client Secret.';
        
        $troubleshootingSteps = [
            'Ensure your Client ID ends with .apps.googleusercontent.com',
            'Verify that you copied the complete Client ID without extra spaces',
            'Check that your Client Secret is the complete string from Google Cloud Console',
            'Ensure you are using credentials from the correct Google Cloud project',
            'Verify that the OAuth 2.0 client type is set to "Web application"',
            'Check that your Google Cloud project is not suspended or restricted',
            'Try generating new credentials if the current ones are old or compromised'
        ];

        return new self(
            'Invalid Google Drive credentials: ' . implode(', ', $validationErrors),
            400,
            null,
            $userMessage,
            $troubleshootingSteps,
            ['provider' => 'google-drive', 'validation_errors' => $validationErrors]
        );
    }

    /**
     * Create exception for API quota exceeded.
     */
    public static function apiQuotaExceeded(string $provider): self
    {
        $userMessage = "API quota exceeded for {$provider}. Please try again later.";
        
        $troubleshootingSteps = match ($provider) {
            'google-drive' => [
                'Wait for the quota to reset (usually within 24 hours)',
                'Check your Google Cloud Console for quota limits and usage',
                'Consider requesting a quota increase if you need higher limits',
                'Verify that you are not making excessive API calls during setup',
                'Check if other applications are using the same Google Cloud project'
            ],
            default => [
                'Wait for the API quota to reset',
                'Check your API usage in the provider\'s console',
                'Consider upgrading your API plan if available'
            ]
        };

        return new self(
            "API quota exceeded for {$provider}",
            429,
            null,
            $userMessage,
            $troubleshootingSteps,
            ['provider' => $provider]
        );
    }

    /**
     * Create exception for unsupported provider.
     */
    public static function unsupportedProvider(string $provider): self
    {
        $userMessage = "Cloud storage provider '{$provider}' is not supported.";
        
        $troubleshootingSteps = [
            'Choose from the supported providers: Google Drive',
            'Check that you selected the correct provider from the dropdown',
            'Contact support if you need additional provider support',
            'Verify that the provider name is spelled correctly'
        ];

        return new self(
            "Unsupported provider: {$provider}",
            400,
            null,
            $userMessage,
            $troubleshootingSteps,
            ['provider' => $provider, 'supported_providers' => ['google-drive']]
        );
    }

    /**
     * Create exception for environment file update failure.
     */
    public static function environmentUpdateFailed(string $technicalError): self
    {
        $userMessage = 'Unable to save cloud storage configuration. Environment file could not be updated.';
        
        $troubleshootingSteps = [
            'Check that the .env file exists in the application root directory',
            'Ensure the .env file is writable by the web server',
            'Verify that there is sufficient disk space available',
            'Check that no other process has the .env file locked',
            'Ensure proper file permissions (644 or 664) on the .env file',
            'Try manually updating the .env file with the cloud storage credentials'
        ];

        return new self(
            "Environment update failed: {$technicalError}",
            500,
            null,
            $userMessage,
            $troubleshootingSteps,
            ['file' => '.env']
        );
    }

    /**
     * Create exception for OAuth configuration error.
     */
    public static function oauthConfigurationError(string $provider, string $technicalError): self
    {
        $userMessage = "OAuth configuration error for {$provider}. Unable to set up authentication.";
        
        $troubleshootingSteps = match ($provider) {
            'google-drive' => [
                'Verify that your OAuth consent screen is published (not in testing mode)',
                'Check that your redirect URI is exactly: ' . config('app.url') . '/admin/cloud-storage/google-drive/callback',
                'Ensure that the OAuth 2.0 client type is set to "Web application"',
                'Verify that your Google Cloud project has the correct domain configured',
                'Check that your application domain is added to authorized domains',
                'Ensure that the Drive API scopes are properly configured'
            ],
            default => [
                'Check your OAuth application configuration',
                'Verify that redirect URIs are correctly configured',
                'Ensure that your application is approved for production use'
            ]
        };

        return new self(
            "OAuth configuration error for {$provider}: {$technicalError}",
            400,
            null,
            $userMessage,
            $troubleshootingSteps,
            ['provider' => $provider]
        );
    }

    /**
     * Create exception for network connectivity issues.
     */
    public static function networkError(string $provider, string $technicalError): self
    {
        $userMessage = "Network error while connecting to {$provider}. Please check your internet connection.";
        
        $troubleshootingSteps = [
            'Check your internet connection and try again',
            'Verify that your server can make outbound HTTPS connections',
            'Check if there are any firewall rules blocking the connection',
            'Ensure that cURL is properly configured and up to date',
            'Try the connection from a different network if possible',
            'Check if the cloud provider\'s API is experiencing outages',
            'Verify that your server\'s SSL certificates are up to date'
        ];

        return new self(
            "Network error connecting to {$provider}: {$technicalError}",
            503,
            null,
            $userMessage,
            $troubleshootingSteps,
            ['provider' => $provider]
        );
    }
}