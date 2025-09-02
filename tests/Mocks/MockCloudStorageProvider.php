<?php

namespace Tests\Mocks;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Services\CloudStorageHealthStatus;

/**
 * Mock cloud storage provider for testing business logic.
 * 
 * This mock provider allows tests to control behavior and verify
 * interactions without making actual API calls.
 */
class MockCloudStorageProvider implements CloudStorageProviderInterface
{
    protected array $uploadedFiles = [];
    protected array $deletedFiles = [];
    protected array $authCallbacks = [];
    protected array $disconnectedUsers = [];
    protected bool $shouldFailUpload = false;
    protected bool $shouldFailDelete = false;
    protected bool $shouldFailAuth = false;
    protected bool $hasValidConnection = true;
    protected string $healthStatus = 'healthy';
    protected array $capabilities = [
        'file_upload' => true,
        'file_delete' => true,
        'folder_creation' => true,
        'folder_delete' => true,
    ];

    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string
    {
        if ($this->shouldFailUpload) {
            throw new \Exception('Mock upload failure');
        }

        $fileId = 'mock_file_' . uniqid();
        $this->uploadedFiles[] = [
            'user_id' => $user->id,
            'local_path' => $localPath,
            'target_path' => $targetPath,
            'metadata' => $metadata,
            'file_id' => $fileId,
            'uploaded_at' => now(),
        ];

        return $fileId;
    }

    public function deleteFile(User $user, string $fileId): bool
    {
        if ($this->shouldFailDelete) {
            throw new \Exception('Mock delete failure');
        }

        $this->deletedFiles[] = [
            'user_id' => $user->id,
            'file_id' => $fileId,
            'deleted_at' => now(),
        ];

        return true;
    }

    public function getConnectionHealth(User $user): CloudStorageHealthStatus
    {
        if ($this->hasValidConnection && $this->healthStatus === 'healthy') {
            return CloudStorageHealthStatus::healthy(
                provider: $this->getProviderName(),
                lastSuccessfulOperation: now(),
            );
        } elseif (!$this->hasValidConnection) {
            return CloudStorageHealthStatus::disconnected(
                provider: $this->getProviderName(),
            );
        } else {
            return CloudStorageHealthStatus::unhealthy(
                provider: $this->getProviderName(),
                consecutiveFailures: 1,
                lastErrorMessage: 'Mock error message'
            );
        }
    }

    public function handleAuthCallback(User $user, string $code): void
    {
        if ($this->shouldFailAuth) {
            throw new \Exception('Mock auth failure');
        }

        $this->authCallbacks[] = [
            'user_id' => $user->id,
            'code' => $code,
            'handled_at' => now(),
        ];
    }

    public function getAuthUrl(User $user, bool $isReconnection = false): string
    {
        return 'https://mock-provider.com/auth?user_id=' . $user->id . ($isReconnection ? '&reconnection=true' : '');
    }

    public function disconnect(User $user): void
    {
        $this->disconnectedUsers[] = [
            'user_id' => $user->id,
            'disconnected_at' => now(),
        ];
    }

    public function getProviderName(): string
    {
        return 'mock-provider';
    }

    public function hasValidConnection(User $user): bool
    {
        return $this->hasValidConnection;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function validateConfiguration(array $config): array
    {
        $errors = [];

        if (empty($config['api_key'])) {
            $errors[] = 'API key is required';
        }

        if (empty($config['endpoint'])) {
            $errors[] = 'Endpoint is required';
        }

        return $errors;
    }

    public function initialize(array $config): void
    {
        // Mock initialization - just validate config
        $errors = $this->validateConfiguration($config);
        if (!empty($errors)) {
            throw new \Exception('Configuration validation failed: ' . implode(', ', $errors));
        }
    }

    public function getAuthenticationType(): string
    {
        return 'api_key';
    }

    public function getStorageModel(): string
    {
        return 'hierarchical';
    }

    public function getMaxFileSize(): int
    {
        return 1073741824; // 1GB
    }

    public function getSupportedFileTypes(): array
    {
        return ['*'];
    }

    public function supportsFeature(string $feature): bool
    {
        return $this->capabilities[$feature] ?? false;
    }

    public function cleanup(): void
    {
        // Mock cleanup - reset state
        $this->uploadedFiles = [];
        $this->deletedFiles = [];
        $this->authCallbacks = [];
        $this->disconnectedUsers = [];
    }

    // Mock control methods for testing

    public function setShouldFailUpload(bool $shouldFail): void
    {
        $this->shouldFailUpload = $shouldFail;
    }

    public function setShouldFailDelete(bool $shouldFail): void
    {
        $this->shouldFailDelete = $shouldFail;
    }

    public function setShouldFailAuth(bool $shouldFail): void
    {
        $this->shouldFailAuth = $shouldFail;
    }

    public function setHasValidConnection(bool $hasConnection): void
    {
        $this->hasValidConnection = $hasConnection;
    }

    public function setHealthStatus(string $status): void
    {
        $this->healthStatus = $status;
    }

    public function setCapabilities(array $capabilities): void
    {
        $this->capabilities = $capabilities;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function getDeletedFiles(): array
    {
        return $this->deletedFiles;
    }

    public function getAuthCallbacks(): array
    {
        return $this->authCallbacks;
    }

    public function getDisconnectedUsers(): array
    {
        return $this->disconnectedUsers;
    }

    public function wasFileUploaded(string $targetPath): bool
    {
        foreach ($this->uploadedFiles as $upload) {
            if ($upload['target_path'] === $targetPath) {
                return true;
            }
        }
        return false;
    }

    public function wasFileDeleted(string $fileId): bool
    {
        foreach ($this->deletedFiles as $deletion) {
            if ($deletion['file_id'] === $fileId) {
                return true;
            }
        }
        return false;
    }

    public function wasUserAuthenticated(int $userId): bool
    {
        foreach ($this->authCallbacks as $callback) {
            if ($callback['user_id'] === $userId) {
                return true;
            }
        }
        return false;
    }

    public function wasUserDisconnected(int $userId): bool
    {
        foreach ($this->disconnectedUsers as $disconnection) {
            if ($disconnection['user_id'] === $userId) {
                return true;
            }
        }
        return false;
    }
}