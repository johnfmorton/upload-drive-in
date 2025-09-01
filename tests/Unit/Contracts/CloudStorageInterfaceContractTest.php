<?php

namespace Tests\Unit\Contracts;

use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Contracts\CloudStorageProviderInterface;
use App\Enums\CloudStorageErrorType;
use App\Models\User;
use App\Services\CloudStorageHealthStatus;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class CloudStorageInterfaceContractTest extends TestCase
{
    public function test_cloud_storage_provider_interface_has_required_methods()
    {
        $reflection = new ReflectionClass(CloudStorageProviderInterface::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $expectedMethods = [
            'uploadFile',
            'deleteFile',
            'getConnectionHealth',
            'handleAuthCallback',
            'getAuthUrl',
            'disconnect',
            'getProviderName',
            'hasValidConnection',
        ];

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains($expectedMethod, $methodNames, "Method {$expectedMethod} is missing from CloudStorageProviderInterface");
        }
    }

    public function test_cloud_storage_provider_interface_upload_file_signature()
    {
        $reflection = new ReflectionClass(CloudStorageProviderInterface::class);
        $method = $reflection->getMethod('uploadFile');
        $parameters = $method->getParameters();

        $this->assertCount(4, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());
        $this->assertEquals('localPath', $parameters[1]->getName());
        $this->assertEquals('targetPath', $parameters[2]->getName());
        $this->assertEquals('metadata', $parameters[3]->getName());
        $this->assertTrue($parameters[3]->isDefaultValueAvailable());
        $this->assertEquals([], $parameters[3]->getDefaultValue());

        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    public function test_cloud_storage_provider_interface_delete_file_signature()
    {
        $reflection = new ReflectionClass(CloudStorageProviderInterface::class);
        $method = $reflection->getMethod('deleteFile');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());
        $this->assertEquals('fileId', $parameters[1]->getName());

        $this->assertEquals('bool', $method->getReturnType()->getName());
    }

    public function test_cloud_storage_provider_interface_get_connection_health_signature()
    {
        $reflection = new ReflectionClass(CloudStorageProviderInterface::class);
        $method = $reflection->getMethod('getConnectionHealth');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals(CloudStorageHealthStatus::class, $returnType->getName());
    }

    public function test_cloud_storage_error_handler_interface_has_required_methods()
    {
        $reflection = new ReflectionClass(CloudStorageErrorHandlerInterface::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $expectedMethods = [
            'classifyError',
            'getUserFriendlyMessage',
            'shouldRetry',
            'getRetryDelay',
            'getMaxRetryAttempts',
            'requiresUserIntervention',
            'getRecommendedActions',
        ];

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains($expectedMethod, $methodNames, "Method {$expectedMethod} is missing from CloudStorageErrorHandlerInterface");
        }
    }

    public function test_cloud_storage_error_handler_interface_classify_error_signature()
    {
        $reflection = new ReflectionClass(CloudStorageErrorHandlerInterface::class);
        $method = $reflection->getMethod('classifyError');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('exception', $parameters[0]->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals(CloudStorageErrorType::class, $returnType->getName());
    }

    public function test_cloud_storage_error_handler_interface_get_user_friendly_message_signature()
    {
        $reflection = new ReflectionClass(CloudStorageErrorHandlerInterface::class);
        $method = $reflection->getMethod('getUserFriendlyMessage');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('type', $parameters[0]->getName());
        $this->assertEquals('context', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertEquals([], $parameters[1]->getDefaultValue());

        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    public function test_cloud_storage_error_handler_interface_should_retry_signature()
    {
        $reflection = new ReflectionClass(CloudStorageErrorHandlerInterface::class);
        $method = $reflection->getMethod('shouldRetry');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('type', $parameters[0]->getName());
        $this->assertEquals('attemptCount', $parameters[1]->getName());

        $this->assertEquals('bool', $method->getReturnType()->getName());
    }

    public function test_cloud_storage_error_handler_interface_get_retry_delay_signature()
    {
        $reflection = new ReflectionClass(CloudStorageErrorHandlerInterface::class);
        $method = $reflection->getMethod('getRetryDelay');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('type', $parameters[0]->getName());
        $this->assertEquals('attemptCount', $parameters[1]->getName());

        $this->assertEquals('int', $method->getReturnType()->getName());
    }

    public function test_cloud_storage_error_handler_interface_get_recommended_actions_signature()
    {
        $reflection = new ReflectionClass(CloudStorageErrorHandlerInterface::class);
        $method = $reflection->getMethod('getRecommendedActions');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('type', $parameters[0]->getName());
        $this->assertEquals('context', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertEquals([], $parameters[1]->getDefaultValue());

        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    public function test_interfaces_are_properly_defined()
    {
        $providerReflection = new ReflectionClass(CloudStorageProviderInterface::class);
        $errorHandlerReflection = new ReflectionClass(CloudStorageErrorHandlerInterface::class);

        $this->assertTrue($providerReflection->isInterface());
        $this->assertTrue($errorHandlerReflection->isInterface());
    }

    public function test_cloud_storage_error_type_enum_is_usable_in_interfaces()
    {
        $this->assertTrue(enum_exists(CloudStorageErrorType::class));
        
        // Test that all enum cases are accessible
        $cases = CloudStorageErrorType::cases();
        $this->assertGreaterThan(0, count($cases));
        
        // Test that enum methods work
        $tokenExpired = CloudStorageErrorType::TOKEN_EXPIRED;
        $this->assertIsString($tokenExpired->value);
        $this->assertIsString($tokenExpired->getDescription());
        $this->assertIsBool($tokenExpired->isRecoverable());
        $this->assertIsBool($tokenExpired->requiresUserIntervention());
        $this->assertIsString($tokenExpired->getSeverity());
    }

    public function test_cloud_storage_health_status_is_usable_in_interfaces()
    {
        $this->assertTrue(class_exists(CloudStorageHealthStatus::class));
        
        // Test that factory methods work
        $healthy = CloudStorageHealthStatus::healthy('test-provider');
        $this->assertInstanceOf(CloudStorageHealthStatus::class, $healthy);
        $this->assertEquals('test-provider', $healthy->provider);
        $this->assertTrue($healthy->isHealthy());
    }
}