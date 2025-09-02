<?php

namespace Tests\Unit\Services;

use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleDriveServiceTokenValidationSimpleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function validateAndRefreshToken_returns_false_when_no_token_exists()
    {
        // Arrange
        $service = new GoogleDriveService();
        
        // Act
        $result = $service->validateAndRefreshToken($this->user);
        
        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function validateAndRefreshToken_returns_true_when_token_is_still_valid()
    {
        // Arrange
        $service = new GoogleDriveService();
        $futureExpiry = Carbon::now()->addHours(2);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'refresh_token',
            'expires_at' => $futureExpiry,
        ]);

        // Act
        $result = $service->validateAndRefreshToken($this->user);
        
        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function validateAndRefreshToken_returns_false_when_token_expired_and_no_refresh_token()
    {
        // Arrange
        $service = new GoogleDriveService();
        $pastExpiry = Carbon::now()->subHours(1);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null, // No refresh token
            'expires_at' => $pastExpiry,
        ]);

        // Act
        $result = $service->validateAndRefreshToken($this->user);
        
        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function testApiConnectivity_returns_false_when_no_token_exists()
    {
        // Arrange
        $service = new GoogleDriveService();
        
        // Act
        $result = $service->testApiConnectivity($this->user);
        
        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function getValidToken_throws_exception_when_no_token_exists()
    {
        // Arrange
        $service = new GoogleDriveService();
        
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has not connected their Google Drive account.');
        
        $service->getValidToken($this->user);
    }

    #[Test]
    public function getValidToken_returns_token_when_still_valid()
    {
        // Arrange
        $service = new GoogleDriveService();
        $futureExpiry = Carbon::now()->addHours(2);
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'refresh_token',
            'expires_at' => $futureExpiry,
        ]);

        // Act
        $result = $service->getValidToken($this->user);
        
        // Assert
        $this->assertInstanceOf(GoogleDriveToken::class, $result);
        $this->assertEquals($token->id, $result->id);
        $this->assertEquals('valid_access_token', $result->access_token);
    }

    #[Test]
    public function getValidToken_throws_exception_when_token_expired_and_no_refresh_token()
    {
        // Arrange
        $service = new GoogleDriveService();
        $pastExpiry = Carbon::now()->subHours(1);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null,
            'expires_at' => $pastExpiry,
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No refresh token available for user.');
        
        $service->getValidToken($this->user);
    }
}