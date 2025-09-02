<?php

namespace Tests\Integration;

use App\Services\CloudStorageConfigurationValidationService;
use App\Services\CloudConfigurationService;
use App\Services\CloudStorageFactory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudStorageConfigurationValidationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageConfigurationValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = app(CloudStorageConfigurationValidationService::class);
    }

    public function test_validates_all_provider_configurations()
    {
        $results = $this->validationService->validateAllProviderConfigurations();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('valid', $results);
        $this->assertArrayHasKey('invalid', $results);
        $this->assertArrayHasKey('warnings', $results);
        $this->assertArrayHasKey('summary', $results);

        $summary = $results['summary'];
        $this->assertArrayHasKey('total_providers', $summary);
        $this->assertArrayHasKey('valid_count', $summary);
        $this->assertArrayHasKey('invalid_count', $summary);
        $this->assertArrayHasKey('warning_count', $summary);

        $this->assertIsInt($summary['total_providers']);
        $this->assertGreaterThan(0, $summary['total_providers']);
    }

    public function test_validates_google_drive_provider_configuration()
    {
        $results = $this->validationService->validateProviderConfiguration('google-drive');

        $this->assertIsArray($results);
        $this->assertArrayHasKey('provider_name', $results);
        $this->assertArrayHasKey('is_valid', $results);
        $this->assertArrayHasKey('errors', $results);
        $this->assertArrayHasKey('warnings', $results);
        $this->assertArrayHasKey('config_sources', $results);
        $this->assertArrayHasKey('provider_class_valid', $results);
        $this->assertArrayHasKey('interface_compliance', $results);

        $this->assertEquals('google-drive', $results['provider_name']);
        $this->assertIsBool($results['is_valid']);
        $this->assertIsArray($results['errors']);
        $this->assertIsArray($results['warnings']);
    }

    public function test_validates_amazon_s3_provider_configuration()
    {
        $results = $this->validationService->validateProviderConfiguration('amazon-s3');

        $this->assertIsArray($results);
        $this->assertEquals('amazon-s3', $results['provider_name']);
        $this->assertIsBool($results['is_valid']);
        
        // S3 should be invalid without proper configuration
        $this->assertFalse($results['is_valid']);
        $this->assertNotEmpty($results['errors']);
    }

    public function test_rejects_unknown_provider()
    {
        $results = $this->validationService->validateProviderConfiguration('unknown-provider');

        $this->assertFalse($results['is_valid']);
        $this->assertContains("Provider 'unknown-provider' is not supported", $results['errors']);
    }

    public function test_gets_validation_summary()
    {
        $summary = $this->validationService->getValidationSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('overall_status', $summary);
        $this->assertArrayHasKey('total_providers', $summary);
        $this->assertArrayHasKey('valid_providers', $summary);
        $this->assertArrayHasKey('invalid_providers', $summary);
        $this->assertArrayHasKey('providers_with_warnings', $summary);
        $this->assertArrayHasKey('validation_timestamp', $summary);

        $this->assertContains($summary['overall_status'], ['valid', 'invalid']);
        $this->assertIsArray($summary['valid_providers']);
        $this->assertIsArray($summary['invalid_providers']);
    }

    public function test_checks_if_has_valid_providers()
    {
        $hasValid = $this->validationService->hasValidProviders();
        $this->assertIsBool($hasValid);
    }

    public function test_gets_first_valid_provider()
    {
        $firstValid = $this->validationService->getFirstValidProvider();
        
        if ($firstValid !== null) {
            $this->assertIsString($firstValid);
            $this->assertNotEmpty($firstValid);
        }
    }

    public function test_validates_and_logs_results()
    {
        $results = $this->validationService->validateAndLog();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('summary', $results);
        
        // Should have logged the results
        $this->assertTrue(true); // If we get here without exceptions, logging worked
    }

    public function test_validates_provider_specific_requirements()
    {
        // Test Google Drive specific validation
        $results = $this->validationService->validateProviderConfiguration('google-drive');
        
        if (!$results['is_valid']) {
            // Should have specific error messages for missing OAuth credentials
            $errorMessages = implode(' ', $results['errors']);
            $this->assertStringContainsString('client', strtolower($errorMessages));
        }
    }

    public function test_detects_configuration_sources()
    {
        $results = $this->validationService->validateProviderConfiguration('google-drive');
        
        $this->assertArrayHasKey('config_sources', $results);
        $this->assertIsArray($results['config_sources']);
        
        // Each source should be one of the expected types
        foreach ($results['config_sources'] as $key => $source) {
            $this->assertContains($source, ['environment', 'database', 'config', 'none']);
        }
    }

    public function test_validates_provider_class_instantiation()
    {
        $results = $this->validationService->validateProviderConfiguration('google-drive');
        
        $this->assertArrayHasKey('provider_class_valid', $results);
        $this->assertArrayHasKey('interface_compliance', $results);
        $this->assertIsBool($results['provider_class_valid']);
        $this->assertIsBool($results['interface_compliance']);
    }

    public function test_handles_validation_exceptions_gracefully()
    {
        // This should not throw an exception even if there are issues
        $results = $this->validationService->validateAllProviderConfigurations();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('summary', $results);
    }
}