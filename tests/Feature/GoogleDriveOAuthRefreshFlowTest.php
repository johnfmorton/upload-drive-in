<?php

namespace Tests\Feature;

use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleDriveOAuthRefreshFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private GoogleDriveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = new GoogleDriveService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function complete_oauth_refresh_flow_works_with_expired_access_token()
    {
        // Arrange - Create expired access token with valid refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        $originalToken = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
            'token_type' => 'Bearer',
            'scopes' => json_encode(['https://www.googleapis.com/auth/drive.file']),
        ]);

        // Mock complete OAuth refresh flow
        $mockClient = Mockery::mock(GoogleClient::class);
        
        // Step 1: Set expired access token
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        
        // Step 2: Check if token is expired
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        
        // Step 3: Get refresh token
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('valid_refresh_token')
                   ->once();
        
        // Step 4: Fetch new access token using refresh token
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('valid_refresh_token')
                   ->andReturn([
                       'access_token' => 'new_access_token_from_oauth',
                       'expires_in' => 3600,
                       'refresh_token' => 'valid_refresh_token', // Refresh token may be renewed
                       'token_type' => 'Bearer',
                       'scope' => 'https://www.googleapis.com/auth/drive.file'
                   ])
                   ->once();
        
        // Step 5: Get the new access token
        $mockClient->shouldReceive('getAccessToken')
                   ->andReturn('new_access_token_from_oauth')
                   ->once();

        // Replace the service's client with our mock
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);

        // Act - Trigger the complete OAuth refresh flow
        $result = $this->service->validateAndRefreshToken($this->user);

        // Assert - Verify the complete flow worked
        $this->assertTrue($result);
        
        // Verify token was updated in database with new values
        $updatedToken = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token_from_oauth', $updatedToken->access_token);
        $this->assertEquals('valid_refresh_token', $updatedToken->refresh_token);
        $this->assertTrue($updatedToken->expires_at->isFuture());
        $this->assertEquals('Bearer', $updatedToken->token_type);
        
        // Verify expiration time is approximately 1 hour from now
        $expectedExpiry = Carbon::now()->addSeconds(3600);
        $this->assertTrue($updatedToken->expires_at->between(
            $expectedExpiry->subMinutes(1),
            $expectedExpiry->addMinutes(1)
        ));
    }

    #[Test]
    public function oauth_refresh_flow_handles_refresh_token_renewal()
    {
        // Arrange - Create expired access token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'old_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock OAuth flow where refresh token is also renewed
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('old_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('old_refresh_token')
                   ->andReturn([
                       'access_token' => 'new_access_token',
                       'expires_in' => 3600,
                       'refresh_token' => 'new_refresh_token', // Refresh token renewed
                       'token_type' => 'Bearer',
                   ])
                   ->once();
        $mockClient->shouldReceive('getAccessToken')
                   ->andReturn('new_access_token')
                   ->once();

        $this->replaceServiceClient($mockClient);

        // Act
        $result = $this->service->validateAndRefreshToken($this->user);

        // Assert
        $this->assertTrue($result);
        
        $updatedToken = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $updatedToken->access_token);
        $this->assertEquals('new_refresh_token', $updatedToken->refresh_token); // Verify refresh token was updated
    }

    #[Test]
    public function oauth_refresh_flow_fails_with_invalid_grant_error()
    {
        // Arrange - Create token with expired refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'expired_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock OAuth flow with invalid_grant error (common when refresh token is expired)
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('expired_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('expired_refresh_token')
                   ->andThrow(new \Exception('invalid_grant: Token has been expired or revoked.'))
                   ->once();

        $this->replaceServiceClient($mockClient);

        // Act
        $result = $this->service->validateAndRefreshToken($this->user);

        // Assert
        $this->assertFalse($result);
        
        // Verify original token remains unchanged
        $originalToken = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('expired_access_token', $originalToken->access_token);
        $this->assertEquals('expired_refresh_token', $originalToken->refresh_token);
    }

    #[Test]
    public function oauth_refresh_flow_handles_network_errors_gracefully()
    {
        // Arrange
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock network error during OAuth refresh
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('valid_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('valid_refresh_token')
                   ->andThrow(new \Exception('cURL error 28: Connection timed out'))
                   ->once();

        $this->replaceServiceClient($mockClient);

        // Act
        $result = $this->service->validateAndRefreshToken($this->user);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function oauth_refresh_flow_works_during_actual_api_operations()
    {
        // Arrange - Create expired access token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock OAuth refresh flow
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('valid_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('valid_refresh_token')
                   ->andReturn([
                       'access_token' => 'refreshed_access_token',
                       'expires_in' => 3600,
                       'refresh_token' => 'valid_refresh_token',
                   ])
                   ->once();
        $mockClient->shouldReceive('getAccessToken')
                   ->andReturn('refreshed_access_token')
                   ->once();

        // Mock Drive API operation after token refresh
        $mockDrive = Mockery::mock(Drive::class);
        $mockAbout = Mockery::mock();
        $mockAbout->shouldReceive('get')
                  ->with(['fields' => 'user'])
                  ->andReturn((object)['user' => (object)['displayName' => 'Test User']])
                  ->once();
        $mockDrive->about = $mockAbout;

        $this->replaceServiceClient($mockClient);
        $this->replaceServiceDrive($mockDrive);

        // Act - Perform API operation that should trigger token refresh
        $result = $this->service->testApiConnectivity($this->user);

        // Assert
        $this->assertTrue($result);
        
        // Verify token was refreshed during the operation
        $updatedToken = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('refreshed_access_token', $updatedToken->access_token);
    }

    #[Test]
    public function oauth_refresh_flow_preserves_token_scopes_and_metadata()
    {
        // Arrange - Create token with specific scopes
        $pastExpiry = Carbon::now()->subMinutes(30);
        $originalScopes = [
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.metadata.readonly'
        ];
        
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
            'token_type' => 'Bearer',
            'scopes' => json_encode($originalScopes),
        ]);

        // Mock OAuth refresh that returns same scopes
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('valid_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('valid_refresh_token')
                   ->andReturn([
                       'access_token' => 'new_access_token',
                       'expires_in' => 3600,
                       'refresh_token' => 'valid_refresh_token',
                       'token_type' => 'Bearer',
                       'scope' => implode(' ', $originalScopes),
                   ])
                   ->once();
        $mockClient->shouldReceive('getAccessToken')
                   ->andReturn('new_access_token')
                   ->once();

        $this->replaceServiceClient($mockClient);

        // Act
        $result = $this->service->validateAndRefreshToken($this->user);

        // Assert
        $this->assertTrue($result);
        
        $updatedToken = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $updatedToken->access_token);
        $this->assertEquals('Bearer', $updatedToken->token_type);
        
        // Verify scopes are preserved
        $updatedScopes = json_decode($updatedToken->scopes, true);
        $this->assertEquals($originalScopes, $updatedScopes);
    }

    #[Test]
    public function oauth_refresh_flow_handles_partial_response_data()
    {
        // Arrange
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock OAuth refresh with minimal response (some providers may not return all fields)
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('valid_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('valid_refresh_token')
                   ->andReturn([
                       'access_token' => 'minimal_response_token',
                       'expires_in' => 3600,
                       // Note: No refresh_token in response (should keep existing)
                       // Note: No token_type in response (should default)
                   ])
                   ->once();
        $mockClient->shouldReceive('getAccessToken')
                   ->andReturn('minimal_response_token')
                   ->once();

        $this->replaceServiceClient($mockClient);

        // Act
        $result = $this->service->validateAndRefreshToken($this->user);

        // Assert
        $this->assertTrue($result);
        
        $updatedToken = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('minimal_response_token', $updatedToken->access_token);
        $this->assertEquals('valid_refresh_token', $updatedToken->refresh_token); // Should keep existing
        $this->assertTrue($updatedToken->expires_at->isFuture());
    }

    private function replaceServiceClient(GoogleClient $mockClient): void
    {
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, $mockClient);
    }

    private function replaceServiceDrive(Drive $mockDrive): void
    {
        $reflection = new \ReflectionClass($this->service);
        $driveProperty = $reflection->getProperty('drive');
        $driveProperty->setAccessible(true);
        $driveProperty->setValue($this->service, $mockDrive);
    }
}