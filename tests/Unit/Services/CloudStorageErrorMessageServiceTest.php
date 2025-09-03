<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Models\User;
use App\Services\CloudStorageErrorMessageService;
use PHPUnit\Framework\TestCase;

class CloudStorageErrorMessageServiceTest extends TestCase
{
    private CloudStorageErrorMessageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CloudStorageErrorMessageService();
    }

    public function test_get_actionable_error_message_for_token_expired()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_EXPIRED,
            ['provider' => 'google-drive']
        );

        $this->assertStringContainsString('Google Drive connection has expired', $message);
        $this->assertStringContainsString('reconnect your account', $message);
    }

    public function test_get_actionable_error_message_for_invalid_credentials()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::INVALID_CREDENTIALS,
            ['provider' => 'amazon-s3']
        );

        $this->assertStringContainsString('Invalid Amazon S3 credentials', $message);
        $this->assertStringContainsString('check your configuration', $message);
    }

    public function test_get_actionable_error_message_for_insufficient_permissions()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            ['provider' => 'google-drive']
        );

        $this->assertStringContainsString('Insufficient Google Drive permissions', $message);
        $this->assertStringContainsString('grant full access', $message);
    }

    public function test_get_actionable_error_message_for_api_quota_exceeded()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            ['provider' => 'google-drive']
        );

        $this->assertStringContainsString('Google Drive API limit reached', $message);
        $this->assertStringContainsString('resume automatically', $message);
    }

    public function test_get_actionable_error_message_for_storage_quota_exceeded()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            ['provider' => 'google-drive']
        );

        $this->assertStringContainsString('Google Drive storage is full', $message);
        $this->assertStringContainsString('free up space', $message);
    }

    public function test_get_actionable_error_message_for_network_error()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::NETWORK_ERROR,
            ['provider' => 'google-drive', 'operation' => 'upload']
        );

        $this->assertStringContainsString('Network connection issue', $message);
        $this->assertStringContainsString('check your internet connection', $message);
    }

    public function test_get_actionable_error_message_for_service_unavailable()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            ['provider' => 'google-drive']
        );

        $this->assertStringContainsString('Google Drive is temporarily unavailable', $message);
        $this->assertStringContainsString('try again in a few minutes', $message);
    }

    public function test_get_actionable_error_message_for_timeout()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TIMEOUT,
            ['provider' => 'google-drive', 'operation' => 'upload']
        );

        $this->assertStringContainsString('Google Drive upload timed out', $message);
        $this->assertStringContainsString('usually temporary', $message);
    }

    public function test_get_actionable_error_message_for_file_not_found()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::FILE_NOT_FOUND,
            ['provider' => 'google-drive', 'file_name' => 'test.pdf']
        );

        $this->assertStringContainsString("file 'test.pdf' could not be found", $message);
        $this->assertStringContainsString('deleted or moved', $message);
    }

    public function test_get_actionable_error_message_for_folder_access_denied()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::FOLDER_ACCESS_DENIED,
            ['provider' => 'google-drive']
        );

        $this->assertStringContainsString('Access denied to the Google Drive folder', $message);
        $this->assertStringContainsString('folder permissions', $message);
    }

    public function test_get_actionable_error_message_for_invalid_file_type()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::INVALID_FILE_TYPE,
            ['provider' => 'google-drive', 'file_name' => 'test.exe']
        );

        $this->assertStringContainsString("file type of 'test.exe' is not supported", $message);
        $this->assertStringContainsString('different file format', $message);
    }

    public function test_get_actionable_error_message_for_file_too_large()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::FILE_TOO_LARGE,
            ['provider' => 'google-drive', 'file_name' => 'large_file.zip']
        );

        $this->assertStringContainsString("file 'large_file.zip' is too large", $message);
        $this->assertStringContainsString('reduce the file size', $message);
    }

    public function test_get_actionable_error_message_for_invalid_file_content()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::INVALID_FILE_CONTENT,
            ['provider' => 'google-drive', 'file_name' => 'corrupted.pdf']
        );

        $this->assertStringContainsString("file 'corrupted.pdf' appears to be corrupted", $message);
        $this->assertStringContainsString('uploading the file again', $message);
    }

    public function test_get_actionable_error_message_for_provider_not_configured()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED,
            ['provider' => 'google-drive']
        );

        $this->assertStringContainsString('Google Drive is not properly configured', $message);
        $this->assertStringContainsString('check your settings', $message);
    }

    public function test_get_actionable_error_message_for_unknown_error()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::UNKNOWN_ERROR,
            ['provider' => 'google-drive', 'original_message' => 'Custom error details']
        );

        $this->assertStringContainsString('unexpected error occurred with Google Drive', $message);
        $this->assertStringContainsString('Custom error details', $message);
    }

    public function test_get_actionable_error_message_with_default_provider()
    {
        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_EXPIRED
        );

        $this->assertStringContainsString('Cloud storage connection has expired', $message);
    }

    public function test_get_recovery_instructions_for_token_expired()
    {
        $instructions = $this->service->getRecoveryInstructions(
            CloudStorageErrorType::TOKEN_EXPIRED,
            ['provider' => 'google-drive']
        );

        $this->assertIsArray($instructions);
        $this->assertContains('Go to Settings → Cloud Storage', $instructions);
        $this->assertContains('Click "Reconnect Google Drive"', $instructions);
        $this->assertContains('Complete the authorization process', $instructions);
        $this->assertContains('Retry your operation', $instructions);
    }

    public function test_get_recovery_instructions_for_insufficient_permissions()
    {
        $instructions = $this->service->getRecoveryInstructions(
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            ['provider' => 'google-drive']
        );

        $this->assertIsArray($instructions);
        $this->assertContains('Go to Settings → Cloud Storage', $instructions);
        $this->assertContains('Click "Reconnect Google Drive"', $instructions);
        $this->assertContains('Ensure you grant full access when prompted', $instructions);
    }

    public function test_get_recovery_instructions_for_storage_quota_exceeded()
    {
        $instructions = $this->service->getRecoveryInstructions(
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            ['provider' => 'google-drive']
        );

        $this->assertIsArray($instructions);
        $this->assertContains('Free up space in your Google Drive account', $instructions);
        $this->assertContains('Empty your Google Drive trash', $instructions);
        $this->assertContains('Consider upgrading your Google Drive storage plan', $instructions);
    }

    public function test_get_recovery_instructions_for_api_quota_exceeded()
    {
        $instructions = $this->service->getRecoveryInstructions(
            CloudStorageErrorType::API_QUOTA_EXCEEDED
        );

        $this->assertIsArray($instructions);
        $this->assertContains('Wait for the quota to reset (usually within an hour)', $instructions);
        $this->assertContains('Operations will resume automatically', $instructions);
    }

    public function test_get_recovery_instructions_for_network_error()
    {
        $instructions = $this->service->getRecoveryInstructions(
            CloudStorageErrorType::NETWORK_ERROR
        );

        $this->assertIsArray($instructions);
        $this->assertContains('Check your internet connection', $instructions);
        $this->assertContains('Try again in a few minutes', $instructions);
    }

    public function test_should_show_technical_details_for_admin_user()
    {
        $adminUser = $this->createMockUser(true);
        
        $result = $this->service->shouldShowTechnicalDetails($adminUser);
        
        $this->assertTrue($result);
    }

    public function test_should_not_show_technical_details_for_regular_user()
    {
        $regularUser = $this->createMockUser(false);
        
        $result = $this->service->shouldShowTechnicalDetails($regularUser);
        
        $this->assertFalse($result);
    }

    public function test_should_not_show_technical_details_for_null_user()
    {
        $result = $this->service->shouldShowTechnicalDetails(null);
        
        $this->assertFalse($result);
    }

    public function test_generate_error_response_comprehensive()
    {
        $adminUser = $this->createMockUser(true);
        
        $response = $this->service->generateErrorResponse(
            CloudStorageErrorType::TOKEN_EXPIRED,
            [
                'provider' => 'google-drive',
                'user' => $adminUser,
                'technical_details' => 'OAuth token expired at 2024-01-01 12:00:00',
                'retry_after' => 300
            ]
        );

        $this->assertIsArray($response);
        $this->assertEquals('token_expired', $response['error_type']);
        $this->assertStringContainsString('Google Drive connection has expired', $response['message']);
        $this->assertIsArray($response['instructions']);
        $this->assertFalse($response['is_retryable']);
        $this->assertTrue($response['requires_user_action']);
        $this->assertEquals('OAuth token expired at 2024-01-01 12:00:00', $response['technical_details']);
        $this->assertEquals(300, $response['retry_after']);
    }

    public function test_generate_error_response_without_technical_details()
    {
        $regularUser = $this->createMockUser(false);
        
        $response = $this->service->generateErrorResponse(
            CloudStorageErrorType::NETWORK_ERROR,
            [
                'provider' => 'google-drive',
                'user' => $regularUser,
                'technical_details' => 'Connection timeout after 30 seconds'
            ]
        );

        $this->assertIsArray($response);
        $this->assertEquals('network_error', $response['error_type']);
        $this->assertTrue($response['is_retryable']);
        $this->assertFalse($response['requires_user_action']);
        $this->assertArrayNotHasKey('technical_details', $response);
    }

    public function test_provider_display_names()
    {
        $testCases = [
            ['google-drive', 'Google Drive'],
            ['amazon-s3', 'Amazon S3'],
            ['azure-blob', 'Azure Blob Storage'],
            ['microsoft-teams', 'Microsoft Teams'],
            ['dropbox', 'Dropbox'],
            ['onedrive', 'OneDrive'],
            ['custom-provider', 'Custom provider']
        ];

        foreach ($testCases as [$provider, $expectedName]) {
            $message = $this->service->getActionableErrorMessage(
                CloudStorageErrorType::TOKEN_EXPIRED,
                ['provider' => $provider]
            );
            
            $this->assertStringContainsString($expectedName, $message);
        }
    }

    public function test_error_classification_retryable_errors()
    {
        $retryableErrors = [
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::TIMEOUT,
            CloudStorageErrorType::API_QUOTA_EXCEEDED
        ];

        foreach ($retryableErrors as $errorType) {
            $response = $this->service->generateErrorResponse($errorType);
            $this->assertTrue($response['is_retryable'], "Error type {$errorType->value} should be retryable");
        }
    }

    public function test_error_classification_non_retryable_errors()
    {
        $nonRetryableErrors = [
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::FILE_NOT_FOUND,
            CloudStorageErrorType::INVALID_FILE_TYPE
        ];

        foreach ($nonRetryableErrors as $errorType) {
            $response = $this->service->generateErrorResponse($errorType);
            $this->assertFalse($response['is_retryable'], "Error type {$errorType->value} should not be retryable");
        }
    }

    public function test_error_classification_requires_user_action()
    {
        $userActionErrors = [
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::FOLDER_ACCESS_DENIED,
            CloudStorageErrorType::INVALID_FILE_TYPE,
            CloudStorageErrorType::FILE_TOO_LARGE,
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED
        ];

        foreach ($userActionErrors as $errorType) {
            $response = $this->service->generateErrorResponse($errorType);
            $this->assertTrue($response['requires_user_action'], "Error type {$errorType->value} should require user action");
        }
    }

    public function test_error_classification_no_user_action_required()
    {
        $noUserActionErrors = [
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::TIMEOUT,
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            CloudStorageErrorType::FILE_NOT_FOUND,
            CloudStorageErrorType::UNKNOWN_ERROR
        ];

        foreach ($noUserActionErrors as $errorType) {
            $response = $this->service->generateErrorResponse($errorType);
            $this->assertFalse($response['requires_user_action'], "Error type {$errorType->value} should not require user action");
        }
    }

    private function createMockUser(bool $isAdmin): object
    {
        return new class($isAdmin) {
            private bool $isAdmin;

            public function __construct(bool $isAdmin)
            {
                $this->isAdmin = $isAdmin;
            }

            public function isAdmin(): bool
            {
                return $this->isAdmin;
            }
        };
    }
}