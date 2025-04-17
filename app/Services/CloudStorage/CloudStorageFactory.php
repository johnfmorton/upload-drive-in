<?php

namespace App\Services\CloudStorage;

use InvalidArgumentException;

class CloudStorageFactory
{
    /**
     * Create a new cloud storage provider instance.
     *
     * @param string $provider The provider identifier (e.g., 'google-drive', 'microsoft-teams')
     * @return CloudStorageProvider
     * @throws InvalidArgumentException If the provider is not supported
     */
    public function create(string $provider): CloudStorageProvider
    {
        return match ($provider) {
            'google-drive' => new GoogleDriveProvider(),
            // Add more providers as they are implemented
            // 'microsoft-teams' => new MicrosoftTeamsProvider(),
            // 'dropbox' => new DropboxProvider(),
            default => throw new InvalidArgumentException("Unsupported cloud storage provider: {$provider}")
        };
    }

    /**
     * Get the default provider name from configuration.
     *
     * @return string
     */
    public function getDefaultProvider(): string
    {
        return config('cloud-storage.default', 'google-drive');
    }

    /**
     * Create a provider instance using the default provider.
     *
     * @return CloudStorageProvider
     */
    public function createDefault(): CloudStorageProvider
    {
        return $this->create($this->getDefaultProvider());
    }
}
