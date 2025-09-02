<?php

namespace Tests\Unit\Contracts;

use Tests\TestCase;
use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Services\CloudStorageHealthStatus;
use ReflectionClass;
use ReflectionMethod;

class CloudStorageProviderInterfaceTest extends TestCase
{
    private ReflectionClass $interfaceReflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interfaceReflection = new ReflectionClass(CloudStorageProviderInterface::class);
    }

    public function test_interface_exists(): void
    {
        $this->assertTrue($this->interfaceReflection->isInterface());
        $this->assertEquals(CloudStorageProviderInterface::class, $this->interfaceReflection->getName());
    }

    public function test_interface_has_all_existing_methods(): void
    {
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

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $this->interfaceReflection->hasMethod($methodName),
                "Interface should have method: {$methodName}"
            );
        }
    }

    public function test_interface_has_all_new_enhanced_methods(): void
    {
        $expectedMethods = [
            'getCapabilities',
            'validateConfiguration',
            'initialize',
            'getAuthenticationType',
            'getStorageModel',
            'getMaxFileSize',
            'getSupportedFileTypes',
            'supportsFeature',
            'cleanup',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $this->interfaceReflection->hasMethod($methodName),
                "Interface should have enhanced method: {$methodName}"
            );
        }
    }

    public function test_upload_file_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('uploadFile');
        $parameters = $method->getParameters();

        $this->assertCount(4, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());
        $this->assertEquals('localPath', $parameters[1]->getName());
        $this->assertEquals('targetPath', $parameters[2]->getName());
        $this->assertEquals('metadata', $parameters[3]->getName());
        $this->assertTrue($parameters[3]->isDefaultValueAvailable());
        $this->assertEquals([], $parameters[3]->getDefaultValue());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    public function test_delete_file_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('deleteFile');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());
        $this->assertEquals('fileId', $parameters[1]->getName());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('bool', $method->getReturnType()->getName());
    }

    public function test_get_connection_health_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('getConnectionHealth');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals(CloudStorageHealthStatus::class, $method->getReturnType()->getName());
    }

    public function test_handle_auth_callback_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('handleAuthCallback');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());
        $this->assertEquals('code', $parameters[1]->getName());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    public function test_get_auth_url_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('getAuthUrl');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    public function test_disconnect_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('disconnect');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    public function test_get_provider_name_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('getProviderName');
        $parameters = $method->getParameters();

        $this->assertCount(0, $parameters);

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    public function test_has_valid_connection_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('hasValidConnection');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('bool', $method->getReturnType()->getName());
    }

    public function test_get_capabilities_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('getCapabilities');
        $parameters = $method->getParameters();

        $this->assertCount(0, $parameters);

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    public function test_validate_configuration_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('validateConfiguration');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('config', $parameters[0]->getName());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    public function test_initialize_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('initialize');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('config', $parameters[0]->getName());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    public function test_get_authentication_type_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('getAuthenticationType');
        $parameters = $method->getParameters();

        $this->assertCount(0, $parameters);

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    public function test_get_storage_model_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('getStorageModel');
        $parameters = $method->getParameters();

        $this->assertCount(0, $parameters);

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    public function test_get_max_file_size_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('getMaxFileSize');
        $parameters = $method->getParameters();

        $this->assertCount(0, $parameters);

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('int', $method->getReturnType()->getName());
    }

    public function test_get_supported_file_types_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('getSupportedFileTypes');
        $parameters = $method->getParameters();

        $this->assertCount(0, $parameters);

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    public function test_supports_feature_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('supportsFeature');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('feature', $parameters[0]->getName());

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('bool', $method->getReturnType()->getName());
    }

    public function test_cleanup_method_signature(): void
    {
        $method = $this->interfaceReflection->getMethod('cleanup');
        $parameters = $method->getParameters();

        $this->assertCount(0, $parameters);

        $this->assertTrue($method->hasReturnType());
        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    public function test_interface_has_correct_total_method_count(): void
    {
        $methods = $this->interfaceReflection->getMethods();
        
        // 8 existing methods + 9 new enhanced methods = 17 total
        $this->assertCount(17, $methods);
    }
}