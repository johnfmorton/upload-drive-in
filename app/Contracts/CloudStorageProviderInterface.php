<?php

namespace App\Contracts;

use App\Models\User;
use App\Services\CloudStorageHealthStatus;

/**
 * Interface for cloud storage providers
 * 
 * Defines standard methods for upload, delete, health check, and authentication
 * across all cloud storage providers (Google Drive, Dropbox, OneDrive, etc.)
 */
interface CloudStorageProviderInterface
{
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
}