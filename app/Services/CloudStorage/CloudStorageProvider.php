<?php

namespace App\Services\CloudStorage;

interface CloudStorageProvider
{
    /**
     * Get the authenticated service instance for the cloud storage provider.
     *
     * @return mixed The provider-specific service instance
     * @throws \Exception If authentication fails
     */
    public function getService(): mixed;

    /**
     * Get the root folder ID for this provider's storage.
     *
     * @return string The provider-specific root folder identifier
     * @throws \Exception If the root folder ID is not configured
     */
    public function getRootFolderId(): string;

    /**
     * Find a user's folder ID based on their email.
     *
     * @param string $email The user's email address
     * @return string|null The folder ID if found, null otherwise
     * @throws \Exception If the provider API call fails
     */
    public function findUserFolderId(string $email): ?string;

    /**
     * Get or create a folder for the user.
     *
     * @param string $email The user's email address
     * @return string The folder ID (either existing or newly created)
     * @throws \Exception If folder cannot be found or created
     */
    public function getOrCreateUserFolderId(string $email): string;

    /**
     * Upload a file to the cloud storage.
     *
     * @param string $localPath The path to the file in local storage
     * @param string $folderId The target folder ID in the cloud storage
     * @param string $filename The desired filename in the cloud storage
     * @param string $mimeType The MIME type of the file
     * @param string|null $description Optional description for the file
     * @return string The provider-specific file ID of the uploaded file
     * @throws \Exception If the upload fails
     */
    public function uploadFile(
        string $localPath,
        string $folderId,
        string $filename,
        string $mimeType,
        ?string $description = null
    ): string;

    /**
     * Delete a file from the cloud storage.
     *
     * @param string $fileId The provider-specific file ID to delete
     * @return bool True if deletion was successful, false otherwise
     * @throws \Exception If the deletion fails
     */
    public function deleteFile(string $fileId): bool;

    /**
     * Delete a folder from the cloud storage.
     *
     * @param string $folderId The provider-specific folder ID to delete
     * @return bool True if deletion was successful, false otherwise
     * @throws \Exception If the deletion fails
     */
    public function deleteFolder(string $folderId): bool;

    /**
     * Get the name of the provider.
     *
     * @return string The unique identifier for this provider (e.g., 'google-drive', 'microsoft-teams')
     */
    public function getProviderName(): string;
}
