# Cloud Storage Provider Testing Guide

This guide explains how to test cloud storage providers in the enhanced cloud storage system, including unit tests, integration tests, and compliance verification.

## Overview

The testing framework provides multiple layers of testing for cloud storage providers:

1. **Interface Compliance Tests** - Verify providers implement the required interface correctly
2. **Unit Tests** - Test provider logic in isolation using mocks
3. **Integration Tests** - Test against real provider APIs
4. **Mock Providers** - Test business logic without external dependencies

## Test Structure

```
tests/
├── Unit/
│   ├── Contracts/
│   │   ├── CloudStorageProviderTestCase.php          # Base unit test class
│   │   └── CloudStorageProviderComplianceTest.php    # Interface compliance tests
│   └── Services/
│       ├── YourProviderTest.php                      # Provider-specific unit tests
│       └── YourProviderErrorHandlerTest.php          # Error handler tests
├── Integration/
│   ├── CloudStorageProviderIntegrationTestCase.php   # Base integration test class
│   ├── YourProviderIntegrationTest.php               # Provider integration tests
│   └── ...
├── Mocks/
│   ├── MockCloudStorageProvider.php                  # Controllable mock provider
│   └── FailingMockCloudStorageProvider.php           # Always-failing mock provider
└── Feature/
    └── YourProviderFeatureTest.php                   # End-to-end feature tests
```

## Creating Unit Tests for a New Provider

### Step 1: Create Provider Unit Test

Extend the base test case for comprehensive provider testing:

```php
<?php

namespace Tests\Unit\Services;

use Tests\Unit\Contracts\CloudStorageProviderTestCase;
use App\Services\YourProvider;
use App\Contracts\CloudStorageProviderInterface;

class YourProviderTest extends CloudStorageProviderTestCase
{
    protected function getProviderName(): string
    {
        return 'your-provider';
    }

    protected function createProvider(): CloudStorageProviderInterface
    {
        return new YourProvider(
            // Mock dependencies here
        );
    }

    protected function getTestConfig(): array
    {
        return [
            'api_key' => 'test-api-key',
            'endpoint' => 'https://api.yourprovider.com',
            'region' => 'us-east-1',
        ];
    }

    // Add provider-specific tests here
    public function test_provider_specific_feature(): void
    {
        $provider = $this->createProvider();
        
        // Test provider-specific functionality
        $this->assertTrue($provider->supportsFeature('your_specific_feature'));
    }
}
```

### Step 2: Add Provider to Compliance Tests

Update the compliance test to include your new provider:

```php
// In tests/Unit/Contracts/CloudStorageProviderComplianceTest.php

protected function getProviderClasses(): array
{
    return [
        GoogleDriveProvider::class,
        S3Provider::class,
        YourProvider::class, // Add your provider here
    ];
}
```

### Step 3: Create Error Handler Tests

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\YourProviderErrorHandler;
use App\Enums\CloudStorageErrorType;

class YourProviderErrorHandlerTest extends TestCase
{
    public function test_classifies_provider_specific_errors(): void
    {
        $handler = new YourProviderErrorHandler();
        
        $exception = new YourProviderException('Specific error message');
        $errorType = $handler->classifyError($exception);
        
        $this->assertEquals(CloudStorageErrorType::YOUR_SPECIFIC_ERROR, $errorType);
    }
}
```

## Creating Integration Tests

### Step 1: Create Integration Test Class

```php
<?php

namespace Tests\Integration;

use App\Contracts\CloudStorageProviderInterface;
use App\Services\YourProvider;

class YourProviderIntegrationTest extends CloudStorageProviderIntegrationTestCase
{
    protected function getProviderName(): string
    {
        return 'your-provider';
    }

    protected function createProvider(): CloudStorageProviderInterface
    {
        return app(YourProvider::class);
    }

    protected function getIntegrationConfig(): array
    {
        return [
            'api_key' => $this->getRequiredEnvVar('YOUR_PROVIDER_API_KEY'),
            'endpoint' => env('YOUR_PROVIDER_ENDPOINT', 'https://api.yourprovider.com'),
            'region' => env('YOUR_PROVIDER_REGION', 'us-east-1'),
        ];
    }

    protected function shouldSkipIntegrationTests(): bool
    {
        return env('SKIP_INTEGRATION_TESTS', true) || 
               empty(env('YOUR_PROVIDER_API_KEY'));
    }

    // Add provider-specific integration tests
    public function test_provider_specific_integration(): void
    {
        // Test provider-specific functionality against real API
        $this->requiresFeature('your_specific_feature');
        
        // Perform integration test
        $result = $this->provider->yourSpecificMethod();
        $this->assertNotNull($result);
    }
}
```

### Step 2: Configure Environment Variables

Add required environment variables for integration testing:

```env
# .env.testing or .env
SKIP_INTEGRATION_TESTS=true
YOUR_PROVIDER_API_KEY=your-test-api-key
YOUR_PROVIDER_ENDPOINT=https://api.yourprovider.com
YOUR_PROVIDER_REGION=us-east-1
```

## Using Mock Providers

### For Business Logic Testing

Use the mock provider to test business logic without external dependencies:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Mocks\MockCloudStorageProvider;
use App\Services\CloudStorageManager;

class FileUploadServiceTest extends TestCase
{
    public function test_file_upload_workflow(): void
    {
        $mockProvider = new MockCloudStorageProvider();
        
        // Configure mock behavior
        $mockProvider->setCapabilities([
            'file_upload' => true,
            'file_delete' => true,
        ]);
        
        // Bind mock provider
        $this->app->bind(CloudStorageProviderInterface::class, function () use ($mockProvider) {
            return $mockProvider;
        });
        
        // Test your business logic
        $service = app(FileUploadService::class);
        $result = $service->uploadFile($user, $file);
        
        // Verify interactions with mock
        $this->assertTrue($mockProvider->wasFileUploaded('expected/path.txt'));
    }
}
```

### For Error Scenario Testing

Use the failing mock provider to test error handling:

```php
public function test_handles_upload_failure(): void
{
    $failingProvider = new FailingMockCloudStorageProvider('Upload failed');
    
    $this->app->bind(CloudStorageProviderInterface::class, function () use ($failingProvider) {
        return $failingProvider;
    });
    
    $service = app(FileUploadService::class);
    
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Upload failed');
    
    $service->uploadFile($user, $file);
}
```

## Running Tests

### Unit Tests Only

```bash
# Run all unit tests
ddev artisan test --testsuite=Unit

# Run provider-specific tests
ddev artisan test tests/Unit/Services/YourProviderTest.php

# Run compliance tests
ddev artisan test tests/Unit/Contracts/CloudStorageProviderComplianceTest.php
```

### Integration Tests

```bash
# Enable integration tests
export SKIP_INTEGRATION_TESTS=false

# Run integration tests
ddev artisan test --testsuite=Integration

# Run specific provider integration tests
ddev artisan test tests/Integration/YourProviderIntegrationTest.php
```

### All Tests

```bash
# Run all tests
ddev artisan test

# Run with coverage
ddev artisan test --coverage
```

## Test Configuration

### PHPUnit Configuration

Ensure your `phpunit.xml` includes the test suites:

```xml
<testsuites>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
    <testsuite name="Integration">
        <directory suffix="Test.php">./tests/Integration</directory>
    </testsuite>
</testsuites>
```

### Environment Configuration

Create separate environment files for testing:

```env
# .env.testing
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Disable integration tests by default
SKIP_INTEGRATION_TESTS=true

# Mock external services
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

## Best Practices

### 1. Test Isolation

- Each test should be independent
- Use database transactions or refresh database
- Clean up external resources in integration tests
- Mock external dependencies in unit tests

### 2. Comprehensive Coverage

- Test all interface methods
- Test error scenarios
- Test edge cases and boundary conditions
- Test provider-specific features

### 3. Meaningful Assertions

```php
// ❌ Weak assertion
$this->assertTrue($result);

// ✅ Strong assertion
$this->assertInstanceOf(CloudStorageHealthStatus::class, $result);
$this->assertTrue($result->isConnected);
$this->assertEquals('healthy', $result->status);
```

### 4. Clear Test Names

```php
// ❌ Unclear
public function test_upload(): void

// ✅ Clear
public function test_can_upload_file_with_metadata(): void
```

### 5. Proper Setup and Teardown

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Set up test dependencies
    $this->provider = $this->createProvider();
    $this->testUser = User::factory()->create();
}

protected function tearDown(): void
{
    // Clean up resources
    $this->cleanupTestFiles();
    
    parent::tearDown();
}
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        
    - name: Install Dependencies
      run: composer install
      
    - name: Run Unit Tests
      run: php artisan test --testsuite=Unit
      
    - name: Run Integration Tests
      run: php artisan test --testsuite=Integration
      env:
        SKIP_INTEGRATION_TESTS: false
        # Add provider credentials as secrets
```

## Troubleshooting

### Common Issues

1. **Provider not found**: Ensure provider is registered in service container
2. **Interface compliance failures**: Check method signatures match interface
3. **Integration test failures**: Verify credentials and network connectivity
4. **Mock behavior issues**: Check mock configuration and state

### Debug Tips

```php
// Add debug output in tests
public function test_debug_example(): void
{
    $provider = $this->createProvider();
    
    dump($provider->getCapabilities()); // Debug output
    
    $this->assertTrue($provider->supportsFeature('file_upload'));
}
```

### Logging in Tests

```php
// Enable logging in tests
protected function setUp(): void
{
    parent::setUp();
    
    Log::info('Starting test: ' . $this->getName());
}
```

This comprehensive testing framework ensures that all cloud storage providers are thoroughly tested and maintain consistent behavior across the system.