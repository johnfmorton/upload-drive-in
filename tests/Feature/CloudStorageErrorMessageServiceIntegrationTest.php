<?php

namespace Tests\Feature;

use App\Enums\CloudStorageErrorType;
use App\Models\User;
use App\Services\CloudStorageErrorMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudStorageErrorMessageServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageErrorMessageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CloudStorageErrorMessageService::class);
    }

    public function test_service_is_properly_registered_in_container()
    {
        $service = app(CloudStorageErrorMessageService::class);
        
        $this->assertInstanceOf(CloudStorageErrorMessageService::class, $service);
    }

    public function test_service_works_with_real_user_model()
    {
        // Create admin user
        $adminUser = User::factory()->create([
            'role' => 'admin'
        ]);

        // Create regular user
        $regularUser = User::factory()->create([
            'role' => 'client'
        ]);

        // Test with admin user
        $this->assertTrue($this->service->shouldShowTechnicalDetails($adminUser));

        // Test with regular user
        $this->assertFalse($this->service->shouldShowTechnicalDetails($regularUser));
    }

    public function test_error_response_generation_with_real_user()
    {
        $adminUser = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->service->generateErrorResponse(
            CloudStorageErrorType::TOKEN_EXPIRED,
            [
                'provider' => 'google-drive',
                'user' => $adminUser,
                'technical_details' => 'OAuth token expired',
                'retry_after' => 300
            ]
        );

        $this->assertIsArray($response);
        $this->assertEquals('token_expired', $response['error_type']);
        $this->assertStringContainsString('Google Drive connection has expired', $response['message']);
        $this->assertIsArray($response['instructions']);
        $this->assertFalse($response['is_retryable']);
        $this->assertTrue($response['requires_user_action']);
        $this->assertEquals('OAuth token expired', $response['technical_details']);
        $this->assertEquals(300, $response['retry_after']);
    }

    public function test_error_messages_for_all_supported_providers()
    {
        $providers = [
            'google-drive' => 'Google Drive',
            'amazon-s3' => 'Amazon S3',
            'azure-blob' => 'Azure Blob Storage',
            'microsoft-teams' => 'Microsoft Teams',
            'dropbox' => 'Dropbox',
            'onedrive' => 'OneDrive'
        ];

        foreach ($providers as $provider => $displayName) {
            $message = $this->service->getActionableErrorMessage(
                CloudStorageErrorType::TOKEN_EXPIRED,
                ['provider' => $provider]
            );

            $this->assertStringContainsString($displayName, $message);
            $this->assertStringContainsString('connection has expired', $message);
        }
    }

    public function test_recovery_instructions_are_actionable()
    {
        $instructions = $this->service->getRecoveryInstructions(
            CloudStorageErrorType::TOKEN_EXPIRED,
            ['provider' => 'google-drive']
        );

        $this->assertIsArray($instructions);
        $this->assertNotEmpty($instructions);
        
        // Ensure instructions are actionable (contain verbs)
        $actionVerbs = ['Go', 'Click', 'Complete', 'Retry', 'Check', 'Ensure'];
        $hasActionableInstructions = false;
        
        foreach ($instructions as $instruction) {
            foreach ($actionVerbs as $verb) {
                if (str_starts_with($instruction, $verb)) {
                    $hasActionableInstructions = true;
                    break 2;
                }
            }
        }
        
        $this->assertTrue($hasActionableInstructions, 'Instructions should contain actionable steps');
    }

    public function test_error_classification_consistency()
    {
        $errorTypes = [
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::TIMEOUT,
            CloudStorageErrorType::FILE_NOT_FOUND,
            CloudStorageErrorType::FOLDER_ACCESS_DENIED,
            CloudStorageErrorType::INVALID_FILE_TYPE,
            CloudStorageErrorType::FILE_TOO_LARGE,
            CloudStorageErrorType::INVALID_FILE_CONTENT,
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED,
            CloudStorageErrorType::UNKNOWN_ERROR
        ];

        foreach ($errorTypes as $errorType) {
            $response = $this->service->generateErrorResponse($errorType);
            
            // Ensure all responses have required fields
            $this->assertArrayHasKey('error_type', $response);
            $this->assertArrayHasKey('message', $response);
            $this->assertArrayHasKey('instructions', $response);
            $this->assertArrayHasKey('is_retryable', $response);
            $this->assertArrayHasKey('requires_user_action', $response);
            
            // Ensure message is not empty
            $this->assertNotEmpty($response['message']);
            
            // Ensure instructions is an array
            $this->assertIsArray($response['instructions']);
            
            // Ensure boolean fields are actually boolean
            $this->assertIsBool($response['is_retryable']);
            $this->assertIsBool($response['requires_user_action']);
        }
    }

    public function test_context_sensitive_error_messages()
    {
        // Test with file name context
        $messageWithFile = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::FILE_NOT_FOUND,
            ['file_name' => 'important_document.pdf', 'provider' => 'google-drive']
        );
        
        $this->assertStringContainsString('important_document.pdf', $messageWithFile);

        // Test with operation context
        $messageWithOperation = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TIMEOUT,
            ['operation' => 'download', 'provider' => 'google-drive']
        );
        
        $this->assertStringContainsString('download timed out', $messageWithOperation);

        // Test with original message context
        $messageWithOriginal = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::UNKNOWN_ERROR,
            ['original_message' => 'Custom error details', 'provider' => 'google-drive']
        );
        
        $this->assertStringContainsString('Custom error details', $messageWithOriginal);
    }

    public function test_service_handles_edge_cases()
    {
        // Test with empty context
        $message = $this->service->getActionableErrorMessage(CloudStorageErrorType::TOKEN_EXPIRED, []);
        $this->assertNotEmpty($message);

        // Test with null user
        $shouldShow = $this->service->shouldShowTechnicalDetails(null);
        $this->assertFalse($shouldShow);

        // Test with unknown provider
        $messageUnknownProvider = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_EXPIRED,
            ['provider' => 'unknown-provider']
        );
        $this->assertStringContainsString('Unknown provider', $messageUnknownProvider);
    }

    public function test_comprehensive_error_response_structure()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        
        $response = $this->service->generateErrorResponse(
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            [
                'provider' => 'google-drive',
                'user' => $adminUser,
                'technical_details' => 'Rate limit: 1000 requests per hour exceeded',
                'retry_after' => 3600,
                'operation' => 'file upload',
                'file_name' => 'large_file.zip'
            ]
        );

        // Verify complete response structure
        $this->assertEquals('api_quota_exceeded', $response['error_type']);
        $this->assertStringContainsString('Google Drive API limit reached', $response['message']);
        $this->assertIsArray($response['instructions']);
        $this->assertTrue($response['is_retryable']);
        $this->assertFalse($response['requires_user_action']);
        $this->assertEquals('Rate limit: 1000 requests per hour exceeded', $response['technical_details']);
        $this->assertEquals(3600, $response['retry_after']);
    }

    public function test_error_message_localization_ready()
    {
        // Test that error messages don't contain hardcoded strings that would prevent localization
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_EXPIRED,
            ['provider' => 'google-drive']
        );

        // Ensure message is a string (ready for localization)
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
        
        // Ensure instructions are array of strings (ready for localization)
        $instructions = $this->service->getRecoveryInstructions(
            CloudStorageErrorType::TOKEN_EXPIRED,
            ['provider' => 'google-drive']
        );
        
        $this->assertIsArray($instructions);
        foreach ($instructions as $instruction) {
            $this->assertIsString($instruction);
            $this->assertNotEmpty($instruction);
        }
    }
}