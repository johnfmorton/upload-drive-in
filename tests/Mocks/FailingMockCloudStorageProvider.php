<?php

namespace Tests\Mocks;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Services\CloudStorageHealthStatus;

/**
 * Mock cloud storage provider that always fails operations.
 * 
 * This mock provider is useful for testing error handling
 * and failure scenarios in business logic.
 */
class FailingMockCloudStorageProvider implements CloudStorageProviderInterface
{
    protected string $failureMessage;

    public function __construct(string $failureMessage = 'Mock provider failure')
    {
        $this->failureMessage = $failureMessage;
    }

    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string
    {
        throw new \Exception($this->failureMessage);
    }

    public function deleteFile(User $user, string $fileId): bool
    {
        throw new \Exception($this->failureMessage);
    }

    public function getConnectionHealth(User $user): CloudStorageHealthStatus
    {
        return CloudStorageHealthStatus::unhealthy(
            provider: $this->getProviderName(),
            consecutiveFailures: 5,
            lastErrorMessage: $this->failureMessage
        );
    }

    public function handleAuthCallback(User $user, string $code): void
    {
        throw new \Exception($this->failureMessage);
    }

    public function getAuthUrl(User $user, bool $isReconnection = false): string
    {
        throw new \Exception($this->failureMessage);
    }

    public function disconnect(User $user): void
    {
        throw new \Exception($this->failureMessage);
    }

    public function getProviderName(): string
    {
        return 'failing-mock-provider';
    }

    public function hasValidConnection(User $user): bool
    {
        return false;
    }

    public function getCapabilities(): array
    {
        return [
            'file_upload' => false,
            'file_delete' => false,
            'folder_creation' => false,
            'folder_delete' => false,
        ];
    }

    public function validateConfiguration(array $config): array
    {
        return ['Configuration validation failed: ' . $this->failureMessage];
    }

    public function initialize(array $config): void
    {
        throw new \Exception($this->failureMessage);
    }

    public function getAuthenticationType(): string
    {
        return 'oauth';
    }

    public function getStorageModel(): string
    {
        return 'hierarchical';
    }

    public function getMaxFileSize(): int
    {
        return 0; // No files allowed
    }

    public function getSupportedFileTypes(): array
    {
        return []; // No file types supported
    }

    public function supportsFeature(string $feature): bool
    {
        return false; // No features supported
    }

    public function cleanup(): void
    {
        throw new \Exception($this->failureMessage);
    }

    public function setFailureMessage(string $message): void
    {
        $this->failureMessage = $message;
    }

    public function getFailureMessage(): string
    {
        return $this->failureMessage;
    }
}