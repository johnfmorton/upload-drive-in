<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleDriveServiceTokenRefreshErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private GoogleDriveService $service;
    private User $user;
    private GoogleDriveToken $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->subHour(), // Expired token
        ]);
        
        $this->service = new GoogleDriveService();
    }

    #[Test]
    public function it_handles_expired_refresh_token_error()
    {
        // Mock the Google Client to return invalid_grant error
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('fetchAccessTokenWithRefreshToken')
            ->willReturn([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.'
            ]);

        // Use reflection to set the mock client
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);

        // Call the private refreshToken method using reflection
        $refreshTokenMethod = $reflection->getMethod('refreshToken');
        $refreshTokenMethod->setAccessible(true);
        
        $result = $refreshTokenMethod->invoke($this->service, $this->token);

        $this->assertFalse($result['success']);
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED, $result['error_type']);
        $this->assertTrue($result['requires_user_intervention']);
        $this->assertFalse($result['is_recoverable']);
        $this->assertStringContainsString('Refresh token is expired or revoked', $result['error']);
    }

    #[Test]
    public function it_handles_invalid_client_credentials_error()
    {
        // Mock the Google Client to return invalid_client error
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('fetchAccessTokenWithRefreshToken')
            ->willReturn([
                'error' => 'invalid_client',
                'error_description' => 'The OAuth client was not found.'
            ]);

        // Use reflection to set the mock client
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);

        // Call the private refreshToken method using reflection
        $refreshTokenMethod = $reflection->getMethod('refreshToken');
        $refreshTokenMethod->setAccessible(true);
        
        $result = $refreshTokenMethod->invoke($this->service, $this->token);

        $this->assertFalse($result['success']);
        $this->assertEquals(CloudStorageErrorType::INVALID_CREDENTIALS, $result['error_type']);
        $this->assertTrue($result['requires_user_intervention']);
        $this->assertFalse($result['is_recoverable']);
        $this->assertStringContainsString('Invalid client credentials', $result['error']);
    }

    #[Test]
    public function it_handles_temporary_server_errors_with_retry()
    {
        // Mock the Google Client to return server_error on first call, then success
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('fetchAccessTokenWithRefreshToken')
            ->willReturnOnConsecutiveCalls(
                [
                    'error' => 'server_error',
                    'error_description' => 'Internal server error.'
                ],
                [
                    'access_token' => 'new_access_token',
                    'expires_in' => 3600
                ]
            );

        // Use reflection to set the mock client
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);

        // Call the private refreshToken method using reflection
        $refreshTokenMethod = $reflection->getMethod('refreshToken');
        $refreshTokenMethod->setAccessible(true);
        
        // Mock sleep to avoid actual delays in tests
        $this->mockSleep();
        
        $result = $refreshTokenMethod->invoke($this->service, $this->token);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['attempt']);
        $this->assertNotNull($result['expires_at']);
    }

    #[Test]
    public function it_handles_rate_limit_exceeded_with_exponential_backoff()
    {
        // Create a mock GoogleServiceException for rate limiting
        $exception = new GoogleServiceException('Rate limit exceeded', 429);

        // Mock the Google Client to throw rate limit exception
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('fetchAccessTokenWithRefreshToken')
            ->willThrowException($exception);

        // Use reflection to set the mock client
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);

        // Call the private refreshToken method using reflection
        $refreshTokenMethod = $reflection->getMethod('refreshToken');
        $refreshTokenMethod->setAccessible(true);
        
        // Mock sleep to avoid actual delays in tests
        $this->mockSleep();
        
        $result = $refreshTokenMethod->invoke($this->service, $this->token);

        $this->assertFalse($result['success']);
        $this->assertEquals(CloudStorageErrorType::API_QUOTA_EXCEEDED, $result['error_type']);
        $this->assertFalse($result['requires_user_intervention']);
        $this->assertTrue($result['is_recoverable']);
        $this->assertEquals(429, $result['http_code']);
        $this->assertEquals(3, $result['attempts_made']);
    }

    #[Test]
    public function it_handles_network_errors_with_retry()
    {
        // Create a mock exception that simulates network error
        $exception = new \Exception('Connection refused');

        // Mock the Google Client to throw network exception
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('fetchAccessTokenWithRefreshToken')
            ->willThrowException($exception);

        // Use reflection to set the mock client
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);

        // Call the private refreshToken method using reflection
        $refreshTokenMethod = $reflection->getMethod('refreshToken');
        $refreshTokenMethod->setAccessible(true);
        
        // Mock sleep to avoid actual delays in tests
        $this->mockSleep();
        
        $result = $refreshTokenMethod->invoke($this->service, $this->token);

        $this->assertFalse($result['success']);
        $this->assertEquals(CloudStorageErrorType::NETWORK_ERROR, $result['error_type']);
        $this->assertFalse($result['requires_user_intervention']);
        $this->assertTrue($result['is_recoverable']);
        $this->assertEquals(3, $result['attempts_made']);
    }

    #[Test]
    public function it_handles_timeout_errors_with_longer_backoff()
    {
        // Create a mock exception that simulates timeout
        $exception = new \Exception('Request timeout');

        // Mock the Google Client to throw timeout exception
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('fetchAccessTokenWithRefreshToken')
            ->willThrowException($exception);

        // Use reflection to set the mock client
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);

        // Call the private refreshToken method using reflection
        $refreshTokenMethod = $reflection->getMethod('refreshToken');
        $refreshTokenMethod->setAccessible(true);
        
        // Mock sleep to avoid actual delays in tests
        $this->mockSleep();
        
        $result = $refreshTokenMethod->invoke($this->service, $this->token);

        $this->assertFalse($result['success']);
        $this->assertEquals(CloudStorageErrorType::TIMEOUT, $result['error_type']);
        $this->assertFalse($result['requires_user_intervention']);
        $this->assertTrue($result['is_recoverable']);
        $this->assertEquals(3, $result['attempts_made']);
    }

    #[Test]
    public function it_handles_quota_exceeded_errors()
    {
        // Create a mock GoogleServiceException for quota exceeded
        $exception = new GoogleServiceException('Quota exceeded', 403);

        // Mock the Google Client to throw quota exception
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('fetchAccessTokenWithRefreshToken')
            ->willThrowException($exception);

        // Use reflection to set the mock client
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);

        // Call the private refreshToken method using reflection
        $refreshTokenMethod = $reflection->getMethod('refreshToken');
        $refreshTokenMethod->setAccessible(true);
        
        $result = $refreshTokenMethod->invoke($this->service, $this->token);

        $this->assertFalse($result['success']);
        $this->assertEquals(CloudStorageErrorType::API_QUOTA_EXCEEDED, $result['error_type']);
        $this->assertFalse($result['requires_user_intervention']);
        $this->assertTrue($result['is_recoverable']);
        $this->assertEquals(403, $result['http_code']);
    }

    #[Test]
    public function it_handles_successful_token_refresh()
    {
        // Mock the Google Client to return successful response
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('fetchAccessTokenWithRefreshToken')
            ->willReturn([
                'access_token' => 'new_access_token',
                'expires_in' => 3600
            ]);

        // Use reflection to set the mock client
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);

        // Call the private refreshToken method using reflection
        $refreshTokenMethod = $reflection->getMethod('refreshToken');
        $refreshTokenMethod->setAccessible(true);
        
        $result = $refreshTokenMethod->invoke($this->service, $this->token);

        $this->assertTrue($result['success']);
        $this->assertEquals('new_access_token', $result['access_token']);
        $this->assertInstanceOf(Carbon::class, $result['expires_at']);
        $this->assertEquals(1, $result['attempt']);

        // Verify token was updated in database
        $this->token->refresh();
        $this->assertEquals('new_access_token', $this->token->access_token);
        $this->assertNotNull($this->token->expires_at);
    }

    #[Test]
    public function it_handles_missing_refresh_token()
    {
        // Create token without refresh token
        $tokenWithoutRefresh = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_token' => null,
        ]);

        // Use reflection to call the private refreshToken method
        $reflection = new \ReflectionClass($this->service);
        $refreshTokenMethod = $reflection->getMethod('refreshToken');
        $refreshTokenMethod->setAccessible(true);
        
        $result = $refreshTokenMethod->invoke($this->service, $tokenWithoutRefresh);

        $this->assertFalse($result['success']);
        $this->assertEquals(CloudStorageErrorType::INVALID_CREDENTIALS, $result['error_type']);
        $this->assertTrue($result['requires_user_intervention']);
        $this->assertFalse($result['is_recoverable']);
        $this->assertEquals('No refresh token available', $result['error']);
    }

    /**
     * Mock the sleep function to avoid delays in tests.
     */
    private function mockSleep(): void
    {
        // In a real implementation, you might want to use a more sophisticated
        // approach to mock sleep, but for this test we'll just let it run
        // since the delays are short
    }
}