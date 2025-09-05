<?php

namespace Tests\Unit\Services;

use App\Enums\TokenRefreshErrorType;
use App\Services\RefreshResult;
use Exception;
use Tests\TestCase;

class RefreshResultTest extends TestCase
{
    public function test_success_creates_successful_result(): void
    {
        $tokenData = ['access_token' => 'new_token', 'expires_in' => 3600];
        $result = RefreshResult::success($tokenData, 'Custom success message');

        $this->assertTrue($result->success);
        $this->assertTrue($result->isSuccessful());
        $this->assertTrue($result->wasTokenRefreshed());
        $this->assertFalse($result->wasAlreadyValid);
        $this->assertFalse($result->wasRefreshedByAnotherProcess);
        $this->assertEquals('Custom success message', $result->message);
        $this->assertEquals($tokenData, $result->tokenData);
        $this->assertNull($result->errorType);
        $this->assertNull($result->exception);
    }

    public function test_success_with_default_message(): void
    {
        $result = RefreshResult::success();

        $this->assertTrue($result->success);
        $this->assertEquals('Token refreshed successfully', $result->message);
        $this->assertNull($result->tokenData);
    }

    public function test_already_valid_creates_correct_result(): void
    {
        $result = RefreshResult::alreadyValid('Token is still good');

        $this->assertTrue($result->success);
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->wasTokenRefreshed());
        $this->assertTrue($result->wasAlreadyValid);
        $this->assertFalse($result->wasRefreshedByAnotherProcess);
        $this->assertEquals('Token is still good', $result->message);
        $this->assertNull($result->tokenData);
        $this->assertNull($result->errorType);
        $this->assertNull($result->exception);
    }

    public function test_already_valid_with_default_message(): void
    {
        $result = RefreshResult::alreadyValid();

        $this->assertTrue($result->success);
        $this->assertEquals('Token is already valid', $result->message);
    }

    public function test_refreshed_by_another_process_creates_correct_result(): void
    {
        $result = RefreshResult::refreshedByAnotherProcess('Another process did it');

        $this->assertTrue($result->success);
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->wasTokenRefreshed());
        $this->assertFalse($result->wasAlreadyValid);
        $this->assertTrue($result->wasRefreshedByAnotherProcess);
        $this->assertEquals('Another process did it', $result->message);
        $this->assertNull($result->tokenData);
        $this->assertNull($result->errorType);
        $this->assertNull($result->exception);
    }

    public function test_refreshed_by_another_process_with_default_message(): void
    {
        $result = RefreshResult::refreshedByAnotherProcess();

        $this->assertTrue($result->success);
        $this->assertEquals('Token was refreshed by another process', $result->message);
    }

    public function test_failure_creates_failed_result(): void
    {
        $errorType = TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
        $exception = new Exception('Invalid token');
        $result = RefreshResult::failure($errorType, $exception, 'Custom error message');

        $this->assertFalse($result->success);
        $this->assertFalse($result->isSuccessful());
        $this->assertFalse($result->wasTokenRefreshed());
        $this->assertFalse($result->wasAlreadyValid);
        $this->assertFalse($result->wasRefreshedByAnotherProcess);
        $this->assertEquals('Custom error message', $result->message);
        $this->assertNull($result->tokenData);
        $this->assertEquals($errorType, $result->errorType);
        $this->assertEquals($errorType, $result->getErrorType());
        $this->assertEquals($exception, $result->exception);
        $this->assertEquals($exception, $result->getException());
    }

    public function test_failure_with_default_message_from_exception(): void
    {
        $errorType = TokenRefreshErrorType::NETWORK_TIMEOUT;
        $exception = new Exception('Connection timed out');
        $result = RefreshResult::failure($errorType, $exception);

        $this->assertFalse($result->success);
        $this->assertEquals('Connection timed out', $result->message);
    }

    public function test_get_description_for_already_valid(): void
    {
        $result = RefreshResult::alreadyValid();
        
        $description = $result->getDescription();
        
        $this->assertEquals('Token was already valid and did not need refreshing', $description);
    }

    public function test_get_description_for_refreshed_by_another_process(): void
    {
        $result = RefreshResult::refreshedByAnotherProcess();
        
        $description = $result->getDescription();
        
        $this->assertEquals('Token was refreshed by another concurrent process', $description);
    }

    public function test_get_description_for_successful_refresh(): void
    {
        $result = RefreshResult::success();
        
        $description = $result->getDescription();
        
        $this->assertEquals('Token was successfully refreshed', $description);
    }

    public function test_get_description_for_failure(): void
    {
        $errorType = TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
        $exception = new Exception('Token expired');
        $result = RefreshResult::failure($errorType, $exception);
        
        $description = $result->getDescription();
        
        $this->assertEquals('Token refresh failed: Token expired', $description);
    }

    public function test_to_array_for_successful_result(): void
    {
        $tokenData = ['access_token' => 'new_token'];
        $result = RefreshResult::success($tokenData, 'Success');
        
        $array = $result->toArray();
        
        $this->assertEquals([
            'success' => true,
            'message' => 'Success',
            'error_type' => null,
            'was_already_valid' => false,
            'was_refreshed_by_another_process' => false,
            'has_token_data' => true,
        ], $array);
    }

    public function test_to_array_for_already_valid_result(): void
    {
        $result = RefreshResult::alreadyValid('Already valid');
        
        $array = $result->toArray();
        
        $this->assertEquals([
            'success' => true,
            'message' => 'Already valid',
            'error_type' => null,
            'was_already_valid' => true,
            'was_refreshed_by_another_process' => false,
            'has_token_data' => false,
        ], $array);
    }

    public function test_to_array_for_refreshed_by_another_process_result(): void
    {
        $result = RefreshResult::refreshedByAnotherProcess('Another process');
        
        $array = $result->toArray();
        
        $this->assertEquals([
            'success' => true,
            'message' => 'Another process',
            'error_type' => null,
            'was_already_valid' => false,
            'was_refreshed_by_another_process' => true,
            'has_token_data' => false,
        ], $array);
    }

    public function test_to_array_for_failed_result(): void
    {
        $errorType = TokenRefreshErrorType::API_QUOTA_EXCEEDED;
        $exception = new Exception('Quota exceeded');
        $result = RefreshResult::failure($errorType, $exception, 'Failed');
        
        $array = $result->toArray();
        
        $this->assertEquals([
            'success' => false,
            'message' => 'Failed',
            'error_type' => 'api_quota_exceeded',
            'was_already_valid' => false,
            'was_refreshed_by_another_process' => false,
            'has_token_data' => false,
        ], $array);
    }

    public function test_to_array_handles_empty_token_data(): void
    {
        $result = RefreshResult::success(null, 'Success without data');
        
        $array = $result->toArray();
        
        $this->assertFalse($array['has_token_data']);
    }

    public function test_to_array_handles_empty_array_token_data(): void
    {
        $result = RefreshResult::success([], 'Success with empty array');
        
        $array = $result->toArray();
        
        $this->assertFalse($array['has_token_data']);
    }
}