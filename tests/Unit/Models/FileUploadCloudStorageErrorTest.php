<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\FileUpload;
use App\Models\User;
use App\Enums\CloudStorageErrorType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class FileUploadCloudStorageErrorTest extends TestCase
{
    use RefreshDatabase;

    private FileUpload $fileUpload;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->fileUpload = FileUpload::factory()->create([
            'client_user_id' => $this->user->id,
            'storage_provider' => 'google-drive'
        ]);
    }

    /** @test */
    public function it_can_update_cloud_storage_error_with_enum()
    {
        $errorType = CloudStorageErrorType::TOKEN_EXPIRED;
        $errorContext = ['message' => 'Token expired', 'code' => 401];
        $connectionHealthAt = now();
        $retryRecommendedAt = now()->addMinutes(30);

        $result = $this->fileUpload->updateCloudStorageError(
            $errorType,
            $errorContext,
            $connectionHealthAt,
            $retryRecommendedAt
        );

        $this->assertTrue($result);
        $this->assertEquals($errorType->value, $this->fileUpload->cloud_storage_error_type);
        $this->assertEquals($errorContext, $this->fileUpload->cloud_storage_error_context);
        $this->assertEquals($connectionHealthAt->format('Y-m-d H:i:s'), $this->fileUpload->connection_health_at_failure->format('Y-m-d H:i:s'));
        $this->assertEquals($retryRecommendedAt->format('Y-m-d H:i:s'), $this->fileUpload->retry_recommended_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_update_cloud_storage_error_with_string()
    {
        $errorType = 'network_error';
        $errorContext = ['message' => 'Network timeout'];

        $result = $this->fileUpload->updateCloudStorageError($errorType, $errorContext);

        $this->assertTrue($result);
        $this->assertEquals($errorType, $this->fileUpload->cloud_storage_error_type);
        $this->assertEquals($errorContext, $this->fileUpload->cloud_storage_error_context);
    }

    /** @test */
    public function it_can_clear_cloud_storage_error()
    {
        // First set an error
        $this->fileUpload->updateCloudStorageError(
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            ['message' => 'Quota exceeded'],
            now(),
            now()->addHour()
        );

        // Then clear it
        $result = $this->fileUpload->clearCloudStorageError();

        $this->assertTrue($result);
        $this->assertNull($this->fileUpload->cloud_storage_error_type);
        $this->assertNull($this->fileUpload->cloud_storage_error_context);
        $this->assertNull($this->fileUpload->connection_health_at_failure);
        $this->assertNull($this->fileUpload->retry_recommended_at);
    }

    /** @test */
    public function it_can_check_if_has_cloud_storage_error()
    {
        $this->assertFalse($this->fileUpload->hasCloudStorageError());

        $this->fileUpload->updateCloudStorageError(CloudStorageErrorType::NETWORK_ERROR);

        $this->assertTrue($this->fileUpload->hasCloudStorageError());
    }

    /** @test */
    public function it_can_get_cloud_storage_error_type_as_enum()
    {
        $this->assertNull($this->fileUpload->getCloudStorageErrorType());

        $errorType = CloudStorageErrorType::INSUFFICIENT_PERMISSIONS;
        $this->fileUpload->updateCloudStorageError($errorType);

        $this->assertEquals($errorType, $this->fileUpload->getCloudStorageErrorType());
    }

    /** @test */
    public function it_returns_null_for_invalid_error_type()
    {
        $this->fileUpload->cloud_storage_error_type = 'invalid_error_type';
        $this->fileUpload->save();

        $this->assertNull($this->fileUpload->getCloudStorageErrorType());
    }

    /** @test */
    public function it_can_check_if_cloud_storage_error_is_recoverable()
    {
        // Test recoverable error
        $this->fileUpload->updateCloudStorageError(CloudStorageErrorType::NETWORK_ERROR);
        $this->assertTrue($this->fileUpload->isCloudStorageErrorRecoverable());

        // Test non-recoverable error
        $this->fileUpload->updateCloudStorageError(CloudStorageErrorType::TOKEN_EXPIRED);
        $this->assertFalse($this->fileUpload->isCloudStorageErrorRecoverable());

        // Test no error
        $this->fileUpload->clearCloudStorageError();
        $this->assertFalse($this->fileUpload->isCloudStorageErrorRecoverable());
    }

    /** @test */
    public function it_can_check_if_cloud_storage_error_requires_user_intervention()
    {
        // Test error requiring intervention
        $this->fileUpload->updateCloudStorageError(CloudStorageErrorType::TOKEN_EXPIRED);
        $this->assertTrue($this->fileUpload->cloudStorageErrorRequiresUserIntervention());

        // Test error not requiring intervention
        $this->fileUpload->updateCloudStorageError(CloudStorageErrorType::NETWORK_ERROR);
        $this->assertFalse($this->fileUpload->cloudStorageErrorRequiresUserIntervention());

        // Test no error
        $this->fileUpload->clearCloudStorageError();
        $this->assertFalse($this->fileUpload->cloudStorageErrorRequiresUserIntervention());
    }

    /** @test */
    public function it_can_get_cloud_storage_error_severity()
    {
        $this->fileUpload->updateCloudStorageError(CloudStorageErrorType::TOKEN_EXPIRED);
        $this->assertEquals('high', $this->fileUpload->getCloudStorageErrorSeverity());

        $this->fileUpload->updateCloudStorageError(CloudStorageErrorType::NETWORK_ERROR);
        $this->assertEquals('low', $this->fileUpload->getCloudStorageErrorSeverity());

        $this->fileUpload->clearCloudStorageError();
        $this->assertNull($this->fileUpload->getCloudStorageErrorSeverity());
    }

    /** @test */
    public function it_generates_user_friendly_error_messages()
    {
        // Test token expired message
        $this->fileUpload->updateCloudStorageError(CloudStorageErrorType::TOKEN_EXPIRED);
        $message = $this->fileUpload->getCloudStorageErrorMessage();
        $this->assertStringContainsString('connection has expired', $message);
        $this->assertStringContainsString('google-drive', $message);

        // Test API quota exceeded with context
        $this->fileUpload->updateCloudStorageError(
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            ['retry_after' => 'Try again in 1 hour.']
        );
        $message = $this->fileUpload->getCloudStorageErrorMessage();
        $this->assertStringContainsString('API limit reached', $message);
        $this->assertStringContainsString('Try again in 1 hour', $message);

        // Test unknown error with original message
        $this->fileUpload->updateCloudStorageError(
            CloudStorageErrorType::UNKNOWN_ERROR,
            ['original_message' => 'Custom error message']
        );
        $message = $this->fileUpload->getCloudStorageErrorMessage();
        $this->assertStringContainsString('Custom error message', $message);

        // Test no error
        $this->fileUpload->clearCloudStorageError();
        $this->assertNull($this->fileUpload->getCloudStorageErrorMessage());
    }

    /** @test */
    public function it_has_cloud_storage_error_accessors()
    {
        $this->fileUpload->updateCloudStorageError(
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            ['message' => 'Storage full']
        );

        $array = $this->fileUpload->toArray();

        $this->assertArrayHasKey('cloud_storage_error_message', $array);
        $this->assertArrayHasKey('cloud_storage_error_description', $array);
        $this->assertArrayHasKey('cloud_storage_error_severity', $array);

        $this->assertStringContainsString('storage quota exceeded', $array['cloud_storage_error_message']);
        $this->assertEquals('Cloud storage quota exceeded', $array['cloud_storage_error_description']);
        $this->assertEquals('medium', $array['cloud_storage_error_severity']);
    }

    /** @test */
    public function it_can_scope_uploads_with_cloud_storage_error()
    {
        $uploadWithError = FileUpload::factory()->create([
            'cloud_storage_error_type' => CloudStorageErrorType::TOKEN_EXPIRED->value
        ]);
        $uploadWithoutError = FileUpload::factory()->create();

        $results = FileUpload::withCloudStorageError()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($uploadWithError));
        $this->assertFalse($results->contains($uploadWithoutError));
    }

    /** @test */
    public function it_can_scope_uploads_with_specific_error_type()
    {
        $tokenExpiredUpload = FileUpload::factory()->create([
            'cloud_storage_error_type' => CloudStorageErrorType::TOKEN_EXPIRED->value
        ]);
        $networkErrorUpload = FileUpload::factory()->create([
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value
        ]);

        // Test with enum
        $results = FileUpload::withCloudStorageErrorType(CloudStorageErrorType::TOKEN_EXPIRED)->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($tokenExpiredUpload));

        // Test with string
        $results = FileUpload::withCloudStorageErrorType('network_error')->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($networkErrorUpload));
    }

    /** @test */
    public function it_can_scope_uploads_with_recoverable_errors()
    {
        $recoverableUpload = FileUpload::factory()->create([
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value
        ]);
        $nonRecoverableUpload = FileUpload::factory()->create([
            'cloud_storage_error_type' => CloudStorageErrorType::TOKEN_EXPIRED->value
        ]);

        $results = FileUpload::withRecoverableCloudStorageError()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($recoverableUpload));
        $this->assertFalse($results->contains($nonRecoverableUpload));
    }

    /** @test */
    public function it_can_scope_uploads_requiring_user_intervention()
    {
        $interventionUpload = FileUpload::factory()->create([
            'cloud_storage_error_type' => CloudStorageErrorType::TOKEN_EXPIRED->value
        ]);
        $autoRecoverUpload = FileUpload::factory()->create([
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value
        ]);

        $results = FileUpload::withCloudStorageErrorRequiringIntervention()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($interventionUpload));
        $this->assertFalse($results->contains($autoRecoverUpload));
    }

    /** @test */
    public function it_can_scope_uploads_by_error_severity()
    {
        $highSeverityUpload = FileUpload::factory()->create([
            'cloud_storage_error_type' => CloudStorageErrorType::TOKEN_EXPIRED->value
        ]);
        $lowSeverityUpload = FileUpload::factory()->create([
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value
        ]);

        $highResults = FileUpload::withCloudStorageErrorSeverity('high')->get();
        $this->assertCount(1, $highResults);
        $this->assertTrue($highResults->contains($highSeverityUpload));

        $lowResults = FileUpload::withCloudStorageErrorSeverity('low')->get();
        $this->assertCount(1, $lowResults);
        $this->assertTrue($lowResults->contains($lowSeverityUpload));
    }

    /** @test */
    public function it_casts_cloud_storage_error_context_to_array()
    {
        $context = ['message' => 'Test error', 'code' => 500];
        
        $this->fileUpload->updateCloudStorageError(
            CloudStorageErrorType::UNKNOWN_ERROR,
            $context
        );

        $this->fileUpload->refresh();
        $this->assertIsArray($this->fileUpload->cloud_storage_error_context);
        $this->assertEquals($context, $this->fileUpload->cloud_storage_error_context);
    }

    /** @test */
    public function it_casts_timestamps_to_carbon_instances()
    {
        $connectionHealthAt = now();
        $retryRecommendedAt = now()->addHour();

        $this->fileUpload->updateCloudStorageError(
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            null,
            $connectionHealthAt,
            $retryRecommendedAt
        );

        $this->fileUpload->refresh();
        $this->assertInstanceOf(Carbon::class, $this->fileUpload->connection_health_at_failure);
        $this->assertInstanceOf(Carbon::class, $this->fileUpload->retry_recommended_at);
    }
}