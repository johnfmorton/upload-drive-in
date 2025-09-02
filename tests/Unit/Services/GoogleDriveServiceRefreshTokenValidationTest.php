<?php

namespace Tests\Unit\Services;

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

class GoogleDriveServiceRefreshTokenValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function validateAndRefreshToken_returns_true_for_valid_token()
    {
        // Arrange - Create valid token that hasn't expired
        $futureExpiry = Carbon::now()->addHours(2);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $futureExpiry,
        ]);

        $service = new GoogleDriveService();

        // Act
        $result = $service->validateAndRefreshToken($this->user);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function validateAndRefreshToken_returns_false_for_expired_token_without_refresh_token()
    {
        // Arrange - Create expired access token without refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null,
            'expires_at' => $pastExpiry,
        ]);

        $service = new GoogleDriveService();

        // Act
        $result = $service->validateAndRefreshToken($this->user);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function validateAndRefreshToken_returns_false_for_missing_token()
    {
        // Arrange - No token exists for user
        $service = new GoogleDriveService();

        // Act
        $result = $service->validateAndRefreshToken($this->user);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function testApiConnectivity_returns_false_for_missing_token()
    {
        // Arrange - No token exists for user
        $service = new GoogleDriveService();

        // Act
        $result = $service->testApiConnectivity($this->user);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function testApiConnectivity_returns_false_for_expired_token_without_refresh()
    {
        // Arrange - Create expired token without refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null,
            'expires_at' => $pastExpiry,
        ]);

        $service = new GoogleDriveService();

        // Act
        $result = $service->testApiConnectivity($this->user);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function getValidToken_returns_valid_token_when_available()
    {
        // Arrange - Create valid token
        $futureExpiry = Carbon::now()->addHours(2);
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $futureExpiry,
        ]);

        $service = new GoogleDriveService();

        // Act
        $result = $service->getValidToken($this->user);

        // Assert
        $this->assertInstanceOf(GoogleDriveToken::class, $result);
        $this->assertEquals('valid_access_token', $result->access_token);
        $this->assertEquals($token->id, $result->id);
    }

    #[Test]
    public function getValidToken_throws_exception_when_no_token_exists()
    {
        // Arrange - No token exists
        $service = new GoogleDriveService();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has not connected their Google Drive account.');
        
        $service->getValidToken($this->user);
    }

    #[Test]
    public function getValidToken_throws_exception_when_token_expired_without_refresh()
    {
        // Arrange - Create expired token without refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null,
            'expires_at' => $pastExpiry,
        ]);

        $service = new GoogleDriveService();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No refresh token available for user.');
        
        $service->getValidToken($this->user);
    }
}