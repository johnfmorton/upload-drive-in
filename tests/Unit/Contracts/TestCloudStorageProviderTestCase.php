<?php

namespace Tests\Unit\Contracts;

use App\Contracts\CloudStorageProviderInterface;
use Tests\Mocks\MockCloudStorageProvider;

/**
 * Test the CloudStorageProviderTestCase base class using the mock provider.
 */
class TestCloudStorageProviderTestCase extends CloudStorageProviderTestCase
{
    protected function getProviderName(): string
    {
        return 'mock-provider';
    }

    protected function createProvider(): CloudStorageProviderInterface
    {
        return new MockCloudStorageProvider();
    }

    protected function getTestConfig(): array
    {
        return [
            'api_key' => 'test-api-key',
            'endpoint' => 'https://mock.example.com',
        ];
    }
}