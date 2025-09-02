<?php

namespace App\Contracts;

use App\Models\User;
use App\Services\CloudStorageHealthStatus;

/**
 * Enhanced interface for cloud storage providers
 * 
 * Defines standard methods for upload, delete, health check, authentication,
 * capability detection, and provider-specific features across all cloud storage 
 * providers (Google Drive, Amazon S3, Azure Blob, Dropbox, etc.)
 */
interface CloudStorageProviderInterface
{
    // ========================================
    // EXISTING METHODS (unchanged for backward compatibility)
    // ========================================

    /**
     * Upload a file to the cloud storage provider
     *
     * @param User $user The user whose cloud storage to use
     * @param string $localPath Path to the local file to upload
     * @param string $targetPath Target path/folder in cloud storage
     * @param array $metadata Additional metadata for the file
     * @return string The cloud storage file ID
     * @throws \App\Exceptions\CloudStorageException
     */
    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string;

    /**
     * Delete a file from the cloud storage provider
     *
     * @param User $user The user whose cloud storage to use
     * @param string $fileId The cloud storage file ID
     * @return bool True if deletion was successful
     * @throws \App\Exceptions\CloudStorageException
     */
    public function deleteFile(User $user, string $fileId): bool;

    /**
     * Check the health status of the connection to the cloud storage provider
     *
     * @param User $user The user whose connection to check
     * @return CloudStorageHealthStatus The current health status
     */
    public function getConnectionHealth(User $user): CloudStorageHealthStatus;

    /**
     * Handle OAuth callback after user authorization
     *
     * @param User $user The user to associate the token with
     * @param string $code The authorization code from OAuth callback
     * @return void
     * @throws \App\Exceptions\CloudStorageException
     */
    public function handleAuthCallback(User $user, string $code): void;

    /**
     * Get the OAuth authorization URL for user authentication
     *
     * @param User $user The user to generate auth URL for
     * @return string The OAuth authorization URL
     */
    public function getAuthUrl(User $user): string;

    /**
     * Disconnect the user's cloud storage account
     *
     * @param User $user The user to disconnect
     * @return void
     */
    public function disconnect(User $user): void;

    /**
     * Get the provider name identifier
     *
     * @return string The provider name (e.g., 'google-drive', 'dropbox')
     */
    public function getProviderName(): string;

    /**
     * Check if the user has a valid connection to this provider
     *
     * @param User $user The user to check
     * @return bool True if connection is valid
     */
    public function hasValidConnection(User $user): bool;

    // ========================================
    // NEW ENHANCED METHODS
    // ========================================

    /**
     * Get the capabilities supported by this provider
     *
     * @return array Array of capability names and their support status
     *               e.g., ['folder_creation' => true, 'presigned_urls' => false]
     */
    public function getCapabilities(): array;

    /**
     * Validate the provider configuration
     *
     * @param array $config Configuration array to validate
     * @return array Array of validation errors (empty if valid)
     */
    public function validateConfiguration(array $config): array;

    /**
     * Initialize the provider with configuration
     *
     * @param array $config Provider-specific configuration
     * @return void
     * @throws \App\Exceptions\CloudStorageSetupException
     */
    public function initialize(array $config): void;

    /**
     * Get the authentication type used by this provider
     *
     * @return string Authentication type ('oauth', 'api_key', 'service_account', 'connection_string')
     */
    public function getAuthenticationType(): string;

    /**
     * Get the storage model used by this provider
     *
     * @return string Storage model ('hierarchical', 'flat', 'hybrid')
     */
    public function getStorageModel(): string;

    /**
     * Get the maximum file size supported by this provider
     *
     * @return int Maximum file size in bytes
     */
    public function getMaxFileSize(): int;

    /**
     * Get the supported file types for this provider
     *
     * @return array Array of supported MIME types or ['*'] for all types
     */
    public function getSupportedFileTypes(): array;

    /**
     * Check if the provider supports a specific feature
     *
     * @param string $feature Feature name to check
     * @return bool True if feature is supported
     */
    public function supportsFeature(string $feature): bool;

    /**
     * Clean up provider resources and connections
     *
     * @return void
     */
    public function cleanup(): void;
}