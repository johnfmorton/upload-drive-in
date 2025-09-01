<?php

namespace Tests\Unit\Enums;

use App\Enums\CloudStorageErrorType;
use PHPUnit\Framework\TestCase;

class CloudStorageErrorTypeTest extends TestCase
{
    public function test_enum_values_are_correct()
    {
        $this->assertEquals('token_expired', CloudStorageErrorType::TOKEN_EXPIRED->value);
        $this->assertEquals('insufficient_permissions', CloudStorageErrorType::INSUFFICIENT_PERMISSIONS->value);
        $this->assertEquals('api_quota_exceeded', CloudStorageErrorType::API_QUOTA_EXCEEDED->value);
        $this->assertEquals('network_error', CloudStorageErrorType::NETWORK_ERROR->value);
        $this->assertEquals('file_not_found', CloudStorageErrorType::FILE_NOT_FOUND->value);
        $this->assertEquals('folder_access_denied', CloudStorageErrorType::FOLDER_ACCESS_DENIED->value);
        $this->assertEquals('storage_quota_exceeded', CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED->value);
        $this->assertEquals('invalid_file_type', CloudStorageErrorType::INVALID_FILE_TYPE->value);
        $this->assertEquals('file_too_large', CloudStorageErrorType::FILE_TOO_LARGE->value);
        $this->assertEquals('invalid_file_content', CloudStorageErrorType::INVALID_FILE_CONTENT->value);
        $this->assertEquals('service_unavailable', CloudStorageErrorType::SERVICE_UNAVAILABLE->value);
        $this->assertEquals('invalid_credentials', CloudStorageErrorType::INVALID_CREDENTIALS->value);
        $this->assertEquals('timeout', CloudStorageErrorType::TIMEOUT->value);
        $this->assertEquals('unknown_error', CloudStorageErrorType::UNKNOWN_ERROR->value);
    }

    public function test_get_description_returns_correct_descriptions()
    {
        $this->assertEquals('Authentication token has expired', CloudStorageErrorType::TOKEN_EXPIRED->getDescription());
        $this->assertEquals('Insufficient permissions for the operation', CloudStorageErrorType::INSUFFICIENT_PERMISSIONS->getDescription());
        $this->assertEquals('API quota or rate limit exceeded', CloudStorageErrorType::API_QUOTA_EXCEEDED->getDescription());
        $this->assertEquals('Network connectivity issue', CloudStorageErrorType::NETWORK_ERROR->getDescription());
        $this->assertEquals('File or folder not found', CloudStorageErrorType::FILE_NOT_FOUND->getDescription());
        $this->assertEquals('Access denied to folder or resource', CloudStorageErrorType::FOLDER_ACCESS_DENIED->getDescription());
        $this->assertEquals('Cloud storage quota exceeded', CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED->getDescription());
        $this->assertEquals('File type not allowed', CloudStorageErrorType::INVALID_FILE_TYPE->getDescription());
        $this->assertEquals('File size exceeds limits', CloudStorageErrorType::FILE_TOO_LARGE->getDescription());
        $this->assertEquals('Invalid or malformed file content', CloudStorageErrorType::INVALID_FILE_CONTENT->getDescription());
        $this->assertEquals('Service temporarily unavailable', CloudStorageErrorType::SERVICE_UNAVAILABLE->getDescription());
        $this->assertEquals('Invalid authentication configuration', CloudStorageErrorType::INVALID_CREDENTIALS->getDescription());
        $this->assertEquals('Operation timed out', CloudStorageErrorType::TIMEOUT->getDescription());
        $this->assertEquals('Unknown error occurred', CloudStorageErrorType::UNKNOWN_ERROR->getDescription());
    }

    public function test_is_recoverable_returns_correct_values()
    {
        // Recoverable errors
        $this->assertTrue(CloudStorageErrorType::NETWORK_ERROR->isRecoverable());
        $this->assertTrue(CloudStorageErrorType::SERVICE_UNAVAILABLE->isRecoverable());
        $this->assertTrue(CloudStorageErrorType::TIMEOUT->isRecoverable());
        $this->assertTrue(CloudStorageErrorType::API_QUOTA_EXCEEDED->isRecoverable());

        // Non-recoverable errors
        $this->assertFalse(CloudStorageErrorType::TOKEN_EXPIRED->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::INSUFFICIENT_PERMISSIONS->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::FILE_NOT_FOUND->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::FOLDER_ACCESS_DENIED->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::INVALID_FILE_TYPE->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::FILE_TOO_LARGE->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::INVALID_FILE_CONTENT->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::INVALID_CREDENTIALS->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::UNKNOWN_ERROR->isRecoverable());
    }

    public function test_requires_user_intervention_returns_correct_values()
    {
        // Requires user intervention
        $this->assertTrue(CloudStorageErrorType::TOKEN_EXPIRED->requiresUserIntervention());
        $this->assertTrue(CloudStorageErrorType::INSUFFICIENT_PERMISSIONS->requiresUserIntervention());
        $this->assertTrue(CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED->requiresUserIntervention());
        $this->assertTrue(CloudStorageErrorType::INVALID_CREDENTIALS->requiresUserIntervention());

        // Does not require user intervention
        $this->assertFalse(CloudStorageErrorType::NETWORK_ERROR->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::SERVICE_UNAVAILABLE->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::TIMEOUT->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::API_QUOTA_EXCEEDED->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::FILE_NOT_FOUND->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::FOLDER_ACCESS_DENIED->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::INVALID_FILE_TYPE->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::FILE_TOO_LARGE->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::INVALID_FILE_CONTENT->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::UNKNOWN_ERROR->requiresUserIntervention());
    }

    public function test_get_severity_returns_correct_values()
    {
        // High severity
        $this->assertEquals('high', CloudStorageErrorType::TOKEN_EXPIRED->getSeverity());
        $this->assertEquals('high', CloudStorageErrorType::INSUFFICIENT_PERMISSIONS->getSeverity());
        $this->assertEquals('high', CloudStorageErrorType::INVALID_CREDENTIALS->getSeverity());
        $this->assertEquals('high', CloudStorageErrorType::UNKNOWN_ERROR->getSeverity());

        // Medium severity
        $this->assertEquals('medium', CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED->getSeverity());
        $this->assertEquals('medium', CloudStorageErrorType::API_QUOTA_EXCEEDED->getSeverity());
        $this->assertEquals('medium', CloudStorageErrorType::FILE_NOT_FOUND->getSeverity());
        $this->assertEquals('medium', CloudStorageErrorType::FOLDER_ACCESS_DENIED->getSeverity());
        $this->assertEquals('medium', CloudStorageErrorType::INVALID_FILE_TYPE->getSeverity());
        $this->assertEquals('medium', CloudStorageErrorType::FILE_TOO_LARGE->getSeverity());
        $this->assertEquals('medium', CloudStorageErrorType::INVALID_FILE_CONTENT->getSeverity());

        // Low severity
        $this->assertEquals('low', CloudStorageErrorType::NETWORK_ERROR->getSeverity());
        $this->assertEquals('low', CloudStorageErrorType::SERVICE_UNAVAILABLE->getSeverity());
        $this->assertEquals('low', CloudStorageErrorType::TIMEOUT->getSeverity());
    }

    public function test_all_enum_cases_are_covered()
    {
        $expectedCases = [
            'TOKEN_EXPIRED',
            'INSUFFICIENT_PERMISSIONS',
            'API_QUOTA_EXCEEDED',
            'NETWORK_ERROR',
            'FILE_NOT_FOUND',
            'FOLDER_ACCESS_DENIED',
            'STORAGE_QUOTA_EXCEEDED',
            'INVALID_FILE_TYPE',
            'FILE_TOO_LARGE',
            'INVALID_FILE_CONTENT',
            'SERVICE_UNAVAILABLE',
            'INVALID_CREDENTIALS',
            'TIMEOUT',
            'UNKNOWN_ERROR',
        ];

        $actualCases = array_map(fn($case) => $case->name, CloudStorageErrorType::cases());

        $this->assertEquals($expectedCases, $actualCases);
        $this->assertCount(14, CloudStorageErrorType::cases());
    }
}