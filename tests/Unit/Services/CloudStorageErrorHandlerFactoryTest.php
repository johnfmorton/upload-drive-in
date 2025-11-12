<?php

namespace Tests\Unit\Services;

use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Enums\CloudStorageErrorType;
use App\Exceptions\CloudStorageException;
use App\Services\CloudStorageErrorHandlerFactory;
use App\Services\GoogleDriveErrorHandler;
use App\Services\S3ErrorHandler;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudStorageErrorHandlerFactoryTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageErrorHandlerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new CloudStorageErrorHandlerFactory();
    }

    public function test_registers_default_handlers()
    {
        $this->assertTrue($this->factory->hasHandler('google-drive'));
        $this->assertTrue($this->factory->hasHandler('amazon-s3'));
        
        $this->assertEquals(GoogleDriveErrorHandler::class, $this->factory->getHandlerClass('google-drive'));
        $this->assertEquals(S3ErrorHandler::class, $this->factory->getHandlerClass('amazon-s3'));
    }

    public function test_creates_google_drive_error_handler()
    {
        $handler = $this->factory->create('google-drive');
        
        $this->assertInstanceOf(GoogleDriveErrorHandler::class, $handler);
        $this->assertInstanceOf(CloudStorageErrorHandlerInterface::class, $handler);
    }

    public function test_creates_s3_error_handler()
    {
        $handler = $this->factory->create('amazon-s3');
        
        $this->assertInstanceOf(S3ErrorHandler::class, $handler);
        $this->assertInstanceOf(CloudStorageErrorHandlerInterface::class, $handler);
    }

    public function test_caches_error_handler_instances()
    {
        $handler1 = $this->factory->create('google-drive');
        $handler2 = $this->factory->create('google-drive');
        
        $this->assertSame($handler1, $handler2);
    }

    public function test_registers_custom_error_handler()
    {
        $this->factory->register('test-provider', TestErrorHandler::class);
        
        $this->assertTrue($this->factory->hasHandler('test-provider'));
        $this->assertEquals(TestErrorHandler::class, $this->factory->getHandlerClass('test-provider'));
    }

    public function test_creates_custom_error_handler()
    {
        $this->factory->register('test-provider', TestErrorHandler::class);
        
        $handler = $this->factory->create('test-provider');
        
        $this->assertInstanceOf(TestErrorHandler::class, $handler);
        $this->assertInstanceOf(CloudStorageErrorHandlerInterface::class, $handler);
    }

    public function test_throws_exception_for_non_existent_handler_class()
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Error handler class \'NonExistentClass\' does not exist');
        
        $this->factory->register('test-provider', 'NonExistentClass');
    }

    public function test_throws_exception_for_invalid_handler_class()
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('must implement CloudStorageErrorHandlerInterface');
        
        $this->factory->register('test-provider', \stdClass::class);
    }

    public function test_throws_exception_for_unregistered_provider()
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('No error handler registered for provider \'unknown-provider\'');
        
        $this->factory->create('unknown-provider');
    }

    public function test_has_handler_returns_correct_values()
    {
        $this->assertTrue($this->factory->hasHandler('google-drive'));
        $this->assertFalse($this->factory->hasHandler('unknown-provider'));
    }

    public function test_gets_registered_providers()
    {
        $providers = $this->factory->getRegisteredProviders();
        
        $this->assertContains('google-drive', $providers);
        $this->assertContains('amazon-s3', $providers);
        $this->assertCount(2, $providers);
    }

    public function test_gets_handler_class()
    {
        $this->assertEquals(GoogleDriveErrorHandler::class, $this->factory->getHandlerClass('google-drive'));
        $this->assertNull($this->factory->getHandlerClass('unknown-provider'));
    }

    public function test_unregisters_handler()
    {
        $this->assertTrue($this->factory->hasHandler('google-drive'));
        
        $this->factory->unregister('google-drive');
        
        $this->assertFalse($this->factory->hasHandler('google-drive'));
        $this->assertNull($this->factory->getHandlerClass('google-drive'));
    }

    public function test_clears_cache_on_registration()
    {
        // Create and cache an instance
        $handler1 = $this->factory->create('google-drive');
        
        // Re-register the same provider
        $this->factory->register('google-drive', GoogleDriveErrorHandler::class);
        
        // Should create a new instance
        $handler2 = $this->factory->create('google-drive');
        
        // They should be different instances due to cache clearing
        $this->assertNotSame($handler1, $handler2);
    }

    public function test_clears_cache()
    {
        // Create and cache instances
        $handler1 = $this->factory->create('google-drive');
        $handler2 = $this->factory->create('amazon-s3');
        
        $this->factory->clearCache();
        
        // Should create new instances after cache clear
        $handler3 = $this->factory->create('google-drive');
        $handler4 = $this->factory->create('amazon-s3');
        
        $this->assertNotSame($handler1, $handler3);
        $this->assertNotSame($handler2, $handler4);
    }

    public function test_gets_statistics()
    {
        // Create some cached instances
        $this->factory->create('google-drive');
        $this->factory->create('amazon-s3');
        
        $stats = $this->factory->getStatistics();
        
        $this->assertEquals(2, $stats['registered_providers']);
        $this->assertEquals(2, $stats['cached_instances']);
        $this->assertContains('google-drive', $stats['providers']);
        $this->assertContains('amazon-s3', $stats['providers']);
        $this->assertContains('google-drive', $stats['cached_providers']);
        $this->assertContains('amazon-s3', $stats['cached_providers']);
    }

    public function test_validates_all_handlers()
    {
        $results = $this->factory->validateAllHandlers();
        
        $this->assertTrue($results['google-drive']);
        $this->assertTrue($results['amazon-s3']);
        $this->assertCount(2, $results);
    }

    public function test_validates_handlers_with_failures()
    {
        // Register a handler that will fail
        $this->factory->register('failing-provider', FailingErrorHandler::class);
        
        $results = $this->factory->validateAllHandlers();
        
        $this->assertTrue($results['google-drive']);
        $this->assertTrue($results['amazon-s3']);
        $this->assertFalse($results['failing-provider']);
    }

    public function test_creates_with_fallback_success()
    {
        $handler = $this->factory->createWithFallback('google-drive', 'amazon-s3');
        
        $this->assertInstanceOf(GoogleDriveErrorHandler::class, $handler);
    }

    public function test_creates_with_fallback_uses_fallback()
    {
        $handler = $this->factory->createWithFallback('unknown-provider', 'google-drive');
        
        $this->assertInstanceOf(GoogleDriveErrorHandler::class, $handler);
    }

    public function test_creates_with_fallback_throws_when_no_fallback()
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('No error handler registered for provider \'unknown-provider\'');
        
        $this->factory->createWithFallback('unknown-provider', 'also-unknown');
    }

    public function test_creates_with_fallback_throws_when_fallback_null()
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('No error handler registered for provider \'unknown-provider\'');
        
        $this->factory->createWithFallback('unknown-provider', null);
    }
}

/**
 * Test error handler implementation
 */
class TestErrorHandler implements CloudStorageErrorHandlerInterface
{
    public function classifyError(\Throwable $exception): CloudStorageErrorType
    {
        return CloudStorageErrorType::UNKNOWN_ERROR;
    }

    public function getUserFriendlyMessage(CloudStorageErrorType $type, array $context = []): string
    {
        return 'Test error message';
    }

    public function shouldRetry(CloudStorageErrorType $type, int $attemptCount): bool
    {
        return false;
    }

    public function getRetryDelay(CloudStorageErrorType $type, int $attemptCount): int
    {
        return 60;
    }

    public function getMaxRetryAttempts(CloudStorageErrorType $type): int
    {
        return 0;
    }

    public function requiresUserIntervention(CloudStorageErrorType $type): bool
    {
        return true;
    }

    public function getRecommendedActions(CloudStorageErrorType $type, array $context = []): array
    {
        return ['Test action'];
    }
}

/**
 * Error handler that fails during instantiation
 */
class FailingErrorHandler implements CloudStorageErrorHandlerInterface
{
    public function __construct()
    {
        throw new Exception('Intentional failure for testing');
    }

    public function classifyError(\Throwable $exception): CloudStorageErrorType
    {
        return CloudStorageErrorType::UNKNOWN_ERROR;
    }

    public function getUserFriendlyMessage(CloudStorageErrorType $type, array $context = []): string
    {
        return 'Test error message';
    }

    public function shouldRetry(CloudStorageErrorType $type, int $attemptCount): bool
    {
        return false;
    }

    public function getRetryDelay(CloudStorageErrorType $type, int $attemptCount): int
    {
        return 60;
    }

    public function getMaxRetryAttempts(CloudStorageErrorType $type): int
    {
        return 0;
    }

    public function requiresUserIntervention(CloudStorageErrorType $type): bool
    {
        return true;
    }

    public function getRecommendedActions(CloudStorageErrorType $type, array $context = []): array
    {
        return ['Test action'];
    }
}