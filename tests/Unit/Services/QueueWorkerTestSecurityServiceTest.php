<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\QueueWorkerTestSecurityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class QueueWorkerTestSecurityServiceTest extends TestCase
{
    private QueueWorkerTestSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new QueueWorkerTestSecurityService();
        Cache::flush();
    }

    public function test_validate_test_request_accepts_valid_data()
    {
        $validData = [
            'timeout' => 30,
            'force' => true,
        ];

        $result = $this->securityService->validateTestRequest($validData);

        $this->assertEquals(30, $result['timeout']);
        $this->assertTrue($result['force']);
    }

    public function test_validate_test_request_rejects_invalid_timeout()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'timeout' => -5, // Below minimum
        ];

        $this->securityService->validateTestRequest($invalidData);
    }

    public function test_validate_test_request_rejects_excessive_timeout()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'timeout' => 200, // Above maximum
        ];

        $this->securityService->validateTestRequest($invalidData);
    }

    public function test_validate_test_request_handles_missing_optional_fields()
    {
        $minimalData = [];

        $result = $this->securityService->validateTestRequest($minimalData);

        $this->assertEmpty($result);
    }

    public function test_validate_cached_status_accepts_valid_data()
    {
        $validCachedData = [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly',
            'test_completed_at' => '2025-01-01T12:00:00Z',
            'processing_time' => 1.23,
            'error_message' => null,
            'test_job_id' => 'test_12345',
            'details' => 'Test completed successfully',
            'can_retry' => false,
        ];

        $result = $this->securityService->validateCachedStatus($validCachedData);

        $this->assertIsArray($result);
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals('Queue worker is functioning properly', $result['message']);
    }

    public function test_validate_cached_status_rejects_invalid_status()
    {
        $invalidData = [
            'status' => 'invalid_status',
            'message' => 'Test message',
        ];

        $result = $this->securityService->validateCachedStatus($invalidData);

        $this->assertNull($result);
    }

    public function test_validate_cached_status_rejects_non_array_data()
    {
        $result1 = $this->securityService->validateCachedStatus('string_data');
        $this->assertNull($result1);

        $result2 = $this->securityService->validateCachedStatus(123);
        $this->assertNull($result2);

        $result3 = $this->securityService->validateCachedStatus(null);
        $this->assertNull($result3);
    }

    public function test_validate_cached_status_handles_oversized_fields()
    {
        $oversizedData = [
            'status' => 'completed',
            'message' => str_repeat('A', 600), // Exceeds 500 char limit
            'error_message' => str_repeat('B', 1200), // Exceeds 1000 char limit
        ];

        $result = $this->securityService->validateCachedStatus($oversizedData);

        $this->assertNull($result);
    }

    public function test_validate_status_update_sanitizes_html()
    {
        $dataWithHtml = [
            'status' => 'completed',
            'message' => '<script>alert("xss")</script>Test message',
            'error_message' => '<img src=x onerror=alert(1)>Error occurred',
            'test_job_id' => 'test_<b>123</b>',
            'details' => 'Test <i>completed</i> successfully',
        ];

        $result = $this->securityService->validateStatusUpdate($dataWithHtml);

        $this->assertStringNotContainsString('<script>', $result['message']);
        $this->assertStringNotContainsString('<img', $result['error_message']);
        $this->assertStringNotContainsString('<b>', $result['test_job_id']);
        $this->assertStringNotContainsString('<i>', $result['details']);
        
        // Should still contain the text content
        $this->assertStringContainsString('Test message', $result['message']);
        $this->assertStringContainsString('Error occurred', $result['error_message']);
    }

    public function test_validate_status_update_preserves_valid_data()
    {
        $validData = [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly',
            'processing_time' => 1.23,
            'can_retry' => true,
        ];

        $result = $this->securityService->validateStatusUpdate($validData);

        $this->assertEquals('completed', $result['status']);
        $this->assertEquals('Queue worker is functioning properly', $result['message']);
        $this->assertEquals(1.23, $result['processing_time']);
        $this->assertTrue($result['can_retry']);
    }

    public function test_check_security_thresholds_detects_suspicious_activity()
    {
        $identifier = 'test_user_123';
        
        // Simulate suspicious activity
        Cache::put("security:suspicious_activity:{$identifier}", 25, 3600);

        $result = $this->securityService->checkSecurityThresholds($identifier);

        $this->assertTrue($result['is_suspicious']);
    }

    public function test_check_security_thresholds_normal_activity()
    {
        $identifier = 'test_user_456';
        
        // Simulate normal activity
        Cache::put("security:suspicious_activity:{$identifier}", 5, 3600);

        $result = $this->securityService->checkSecurityThresholds($identifier);

        $this->assertFalse($result['is_suspicious']);
    }

    public function test_record_security_event_logs_to_security_channel()
    {
        // Mock the Log facade to verify the call
        \Log::shouldReceive('channel')
            ->with('security')
            ->once()
            ->andReturnSelf();
            
        \Log::shouldReceive('info')
            ->with('Queue worker test security event: test_event', ['test' => 'data'])
            ->once();

        $this->securityService->recordSecurityEvent('test_event', ['test' => 'data']);
    }

    public function test_clear_security_data_removes_all_traces()
    {
        $identifier = 'test_user_789';
        
        // Set up security data
        Cache::put('queue_worker_test:' . $identifier, 'test_data', 60);
        Cache::put('setup:queue_test:cooldown:' . $identifier, time() + 30, 60);
        Cache::put("security:suspicious_activity:{$identifier}", 10, 60);

        $this->securityService->clearSecurityData($identifier);

        // Verify all data is cleared
        $this->assertNull(Cache::get('queue_worker_test:' . $identifier));
        $this->assertNull(Cache::get('setup:queue_test:cooldown:' . $identifier));
        $this->assertNull(Cache::get("security:suspicious_activity:{$identifier}"));
    }

    public function test_sanitize_data_handles_different_types()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->securityService);
        $method = $reflection->getMethod('sanitizeData');
        $method->setAccessible(true);

        $testData = [
            'string_field' => '<script>alert("test")</script>Normal text',
            'numeric_field' => 123.45,
            'boolean_field' => true,
            'null_field' => null,
            'array_field' => ['nested' => 'value'],
        ];

        $result = $method->invoke($this->securityService, $testData);

        $this->assertStringNotContainsString('<script>', $result['string_field']);
        $this->assertEquals(123.45, $result['numeric_field']);
        $this->assertTrue($result['boolean_field']);
        $this->assertNull($result['null_field']);
        $this->assertIsString($result['array_field']); // Arrays get converted to strings
    }

    public function test_validate_test_request_with_edge_case_values()
    {
        // Test minimum valid timeout
        $minData = ['timeout' => 5];
        $result = $this->securityService->validateTestRequest($minData);
        $this->assertEquals(5, $result['timeout']);

        // Test maximum valid timeout
        $maxData = ['timeout' => 120];
        $result = $this->securityService->validateTestRequest($maxData);
        $this->assertEquals(120, $result['timeout']);

        // Test boolean edge cases
        $boolData = ['force' => false];
        $result = $this->securityService->validateTestRequest($boolData);
        $this->assertFalse($result['force']);
    }

    public function test_validate_cached_status_with_all_optional_fields()
    {
        $completeData = [
            'status' => 'failed',
            'message' => 'Test failed',
            'test_completed_at' => '2025-01-01T12:00:00Z',
            'processing_time' => 0.5,
            'error_message' => 'Connection timeout',
            'test_job_id' => 'test_67890',
            'details' => 'Detailed error information',
            'can_retry' => true,
        ];

        $result = $this->securityService->validateCachedStatus($completeData);

        $this->assertIsArray($result);
        $this->assertEquals('failed', $result['status']);
        $this->assertEquals('Test failed', $result['message']);
        $this->assertEquals('Connection timeout', $result['error_message']);
        $this->assertTrue($result['can_retry']);
    }
}