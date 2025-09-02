<?php

namespace Tests\Feature;

use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleDriveTokenRefreshIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function validateAndRefreshToken_works_with_valid_tokens()
    {
        // Arrange
        $user = User::factory()->create();
        $service = new GoogleDriveService();
        
        // Create a valid token
        $futureExpiry = Carbon::now()->addHours(2);
        GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'refresh_token',
            'expires_at' => $futureExpiry,
        ]);

        // Act
        $result = $service->validateAndRefreshToken($user);
        
        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function testApiConnectivity_returns_false_without_valid_token()
    {
        // Arrange
        $user = User::factory()->create();
        $service = new GoogleDriveService();
        
        // Act
        $result = $service->testApiConnectivity($user);
        
        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function service_methods_handle_missing_tokens_gracefully()
    {
        // Arrange
        $user = User::factory()->create();
        $service = new GoogleDriveService();
        
        // Act & Assert - validateAndRefreshToken
        $validateResult = $service->validateAndRefreshToken($user);
        $this->assertFalse($validateResult);
        
        // Act & Assert - testApiConnectivity
        $connectivityResult = $service->testApiConnectivity($user);
        $this->assertFalse($connectivityResult);
        
        // Act & Assert - getValidToken should throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has not connected their Google Drive account.');
        $service->getValidToken($user);
    }

    #[Test]
    public function service_methods_handle_expired_tokens_without_refresh_token()
    {
        // Arrange
        $user = User::factory()->create();
        $service = new GoogleDriveService();
        
        $pastExpiry = Carbon::now()->subHours(1);
        GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null, // No refresh token
            'expires_at' => $pastExpiry,
        ]);
        
        // Act & Assert - validateAndRefreshToken
        $validateResult = $service->validateAndRefreshToken($user);
        $this->assertFalse($validateResult);
        
        // Act & Assert - testApiConnectivity
        $connectivityResult = $service->testApiConnectivity($user);
        $this->assertFalse($connectivityResult);
        
        // Act & Assert - getValidToken should throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No refresh token available for user.');
        $service->getValidToken($user);
    }
}