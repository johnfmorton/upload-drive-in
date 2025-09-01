<?php

namespace Tests\Unit\Exceptions;

use App\Enums\CloudStorageErrorType;
use App\Exceptions\CloudStorageException;
use Exception;
use PHPUnit\Framework\TestCase;

class CloudStorageExceptionTest extends TestCase
{
    public function test_basic_exception_creation()
    {
        $context = ['file_id' => '123', 'user_id' => 456];
        $previous = new Exception('Original error');

        $exception = new CloudStorageException(
            'Test error message',
            CloudStorageErrorType::TOKEN_EXPIRED,
            $context,
            'google-drive',
            500,
            $previous
        );

        $this->assertEquals('Test error message', $exception->getMessage());
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED, $exception->getErrorType());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals('google-drive', $exception->getProvider());
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
    }

    public function test_token_expired_factory_method()
    {
        $context = ['token_id' => 'abc123'];
        $previous = new Exception('Token refresh failed');

        $exception = CloudStorageException::tokenExpired('google-drive', $context, $previous);

        $this->assertEquals('Authentication token expired for google-drive', $exception->getMessage());
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED, $exception->getErrorType());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals('google-drive', $exception->getProvider());
        $this->assertEquals($previous, $exception->getPrevious());
    }

    public function test_insufficient_permissions_factory_method()
    {
        $context = ['required_scope' => 'drive.file'];

        $exception = CloudStorageException::insufficientPermissions('dropbox', $context);

        $this->assertEquals('Insufficient permissions for dropbox', $exception->getMessage());
        $this->assertEquals(CloudStorageErrorType::INSUFFICIENT_PERMISSIONS, $exception->getErrorType());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals('dropbox', $exception->getProvider());
    }

    public function test_quota_exceeded_factory_method()
    {
        $context = ['quota_limit' => '1000', 'current_usage' => '1001'];

        $exception = CloudStorageException::quotaExceeded('onedrive', $context);

        $this->assertEquals('API quota exceeded for onedrive', $exception->getMessage());
        $this->assertEquals(CloudStorageErrorType::API_QUOTA_EXCEEDED, $exception->getErrorType());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals('onedrive', $exception->getProvider());
    }

    public function test_network_error_factory_method()
    {
        $context = ['timeout' => 30];

        $exception = CloudStorageException::networkError('google-drive', $context);

        $this->assertEquals('Network error connecting to google-drive', $exception->getMessage());
        $this->assertEquals(CloudStorageErrorType::NETWORK_ERROR, $exception->getErrorType());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals('google-drive', $exception->getProvider());
    }

    public function test_storage_quota_exceeded_factory_method()
    {
        $context = ['available_space' => '0 GB'];

        $exception = CloudStorageException::storageQuotaExceeded('dropbox', $context);

        $this->assertEquals('Storage quota exceeded for dropbox', $exception->getMessage());
        $this->assertEquals(CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED, $exception->getErrorType());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals('dropbox', $exception->getProvider());
    }

    public function test_unknown_factory_method()
    {
        $context = ['debug_info' => 'Additional details'];

        $exception = CloudStorageException::unknown('onedrive', 'Custom error message', $context);

        $this->assertEquals('Custom error message', $exception->getMessage());
        $this->assertEquals(CloudStorageErrorType::UNKNOWN_ERROR, $exception->getErrorType());
        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals('onedrive', $exception->getProvider());
    }

    public function test_unknown_factory_method_with_default_message()
    {
        $exception = CloudStorageException::unknown('test-provider');

        $this->assertEquals('Unknown error occurred', $exception->getMessage());
        $this->assertEquals(CloudStorageErrorType::UNKNOWN_ERROR, $exception->getErrorType());
        $this->assertEquals('test-provider', $exception->getProvider());
    }

    public function test_requires_user_intervention()
    {
        $tokenExpired = CloudStorageException::tokenExpired('test');
        $networkError = CloudStorageException::networkError('test');

        $this->assertTrue($tokenExpired->requiresUserIntervention());
        $this->assertFalse($networkError->requiresUserIntervention());
    }

    public function test_is_recoverable()
    {
        $networkError = CloudStorageException::networkError('test');
        $tokenExpired = CloudStorageException::tokenExpired('test');

        $this->assertTrue($networkError->isRecoverable());
        $this->assertFalse($tokenExpired->isRecoverable());
    }

    public function test_get_severity()
    {
        $tokenExpired = CloudStorageException::tokenExpired('test');
        $networkError = CloudStorageException::networkError('test');
        $quotaExceeded = CloudStorageException::quotaExceeded('test');

        $this->assertEquals('high', $tokenExpired->getSeverity());
        $this->assertEquals('low', $networkError->getSeverity());
        $this->assertEquals('medium', $quotaExceeded->getSeverity());
    }

    public function test_to_array()
    {
        $context = ['file_id' => '123'];
        $exception = CloudStorageException::tokenExpired('google-drive', $context);

        $array = $exception->toArray();

        $this->assertEquals('Authentication token expired for google-drive', $array['message']);
        $this->assertEquals('token_expired', $array['error_type']);
        $this->assertEquals('google-drive', $array['provider']);
        $this->assertEquals($context, $array['context']);
        $this->assertEquals(0, $array['code']);
        $this->assertTrue($array['requires_user_intervention']);
        $this->assertFalse($array['is_recoverable']);
        $this->assertEquals('high', $array['severity']);
    }

    public function test_exception_without_error_type()
    {
        $exception = new CloudStorageException('Test message');

        $this->assertNull($exception->getErrorType());
        $this->assertFalse($exception->requiresUserIntervention());
        $this->assertFalse($exception->isRecoverable());
        $this->assertEquals('high', $exception->getSeverity());
    }
}