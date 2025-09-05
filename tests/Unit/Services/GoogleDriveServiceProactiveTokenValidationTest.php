<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\GoogleDriveService;
use App\Services\TokenRefreshCoordinator;
use App\Services\RefreshResult;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Enums\TokenRefreshErrorType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use Exception;

class GoogleDriveServiceProactiveTokenValidationTest extends TestCase
{
    use RefreshDatabase;
    private GoogleDriveService $service;
    private User $user;
    private GoogleDriveToken $token;
    private $mockCoordinator;
    private $mockHealthService;
    private $mockLogService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN
        ]);
        
        // Create mock services
        $this->mockCoordinator = Mockery::mock(TokenRefreshCoordinator::class);
        $this->mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        $this->mockLogService = Mockery::mock(CloudStorageLogService::class);
        
        // Create service with mocked dependencies
        $this->service = new GoogleDriveService($this->mockHealthService, $this->mockLogService);
        
        // Bind the mock coordinator to the container
        $this->app->instance(TokenRefreshCoordinator::class, $this->mockCoordinator);
    }

    public function test_validate_and_refresh_token_returns_true_for_valid_token()
    {
        // Create a token that is not expiring soon (expires in 30 minutes)
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addMinutes(30),
            'scopes' => ['https://www.googleapis.com/auth/drive']
        ]);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', false, false);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertTrue($result);
    }

    public function test_validate_and_refresh_token_returns_false_for_no_token()
    {
        // No token exists for this user
        
        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertFalse($result);
    }

    public function test_validate_and_refresh_token_proactively_refreshes_expiring_token()
    {
        // Create a token that is expiring soon (expires in 10 minutes - within 15 minute threshold)
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expiring_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false
        ]);

        // Mock successful refresh result
        $refreshResult = RefreshResult::success(['access_token' => 'new_token'], 'Token refreshed successfully');

        // Mock coordinator expectations
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($refreshResult);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logTokenRefreshSuccess')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, true, true);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertTrue($result);
    }

    public function test_validate_and_refresh_token_handles_expired_token()
    {
        // Create an expired token
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->subMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false
        ]);

        // Mock successful refresh result
        $refreshResult = RefreshResult::success(['access_token' => 'new_token'], 'Token refreshed successfully');

        // Mock coordinator expectations
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($refreshResult);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logTokenRefreshSuccess')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, true, true);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertTrue($result);
    }

    public function test_validate_and_refresh_token_returns_false_for_unrefreshable_token()
    {
        // Create a token that cannot be refreshed (no refresh token)
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->subMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false
        ]);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, false);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertFalse($result);
    }

    public function test_validate_and_refresh_token_returns_false_for_user_intervention_required()
    {
        // Create a token that requires user intervention
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->subMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 5,
            'requires_user_intervention' => true
        ]);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, false);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertFalse($result);
    }

    public function test_validate_and_refresh_token_handles_coordinator_failure()
    {
        // Create an expired token
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->subMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false
        ]);

        // Mock failed refresh result
        $refreshResult = RefreshResult::failure(
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            new Exception('Network timeout'),
            'Network timeout during refresh'
        );

        // Mock coordinator expectations
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($refreshResult);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logTokenRefreshFailure')
            ->once()
            ->with($this->user, 'google-drive', 'Network timeout during refresh', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, true, false);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertFalse($result);
    }

    public function test_validate_and_refresh_token_handles_already_valid_result()
    {
        // Create an expired token
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->subMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false
        ]);

        // Mock already valid result (another process refreshed it)
        $refreshResult = RefreshResult::alreadyValid('Token was already refreshed by another process');

        // Mock coordinator expectations
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($refreshResult);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logTokenRefreshSuccess')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, true, true);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertTrue($result);
    }

    public function test_validate_and_refresh_token_handles_refreshed_by_another_process_result()
    {
        // Create an expired token
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->subMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false
        ]);

        // Mock refreshed by another process result
        $refreshResult = RefreshResult::refreshedByAnotherProcess('Token was already refreshed by another process');

        // Mock coordinator expectations
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($refreshResult);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logTokenRefreshSuccess')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, true, true);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertTrue($result);
    }

    public function test_validate_and_refresh_token_handles_exception()
    {
        // Create an expired token
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->subMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false
        ]);

        // Mock coordinator throwing exception
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andThrow(new Exception('Coordinator exception'));

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logTokenRefreshFailure')
            ->once()
            ->with($this->user, 'google-drive', 'Coordinator exception', Mockery::type('array'));

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertFalse($result);
    }

    public function test_validate_and_refresh_token_logs_detailed_operation_info()
    {
        // Create a token that is expiring soon
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expiring_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false
        ]);

        // Mock successful refresh result
        $refreshResult = RefreshResult::success(['access_token' => 'new_token'], 'Token refreshed successfully');

        // Mock coordinator expectations
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($refreshResult);

        // Mock detailed log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::on(function ($data) {
                return isset($data['trigger']) && $data['trigger'] === 'proactive_validation' &&
                       isset($data['operation_id']) &&
                       isset($data['is_proactive']) && $data['is_proactive'] === true;
            }));

        $this->mockLogService->shouldReceive('logTokenRefreshSuccess')
            ->once()
            ->with($this->user, 'google-drive', Mockery::on(function ($data) {
                return isset($data['trigger']) && $data['trigger'] === 'proactive_validation' &&
                       isset($data['operation_id']) &&
                       isset($data['was_already_valid']) &&
                       isset($data['was_refreshed_by_another_process']) &&
                       isset($data['was_token_refreshed']);
            }));

        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, true, true);

        // Capture log messages to verify operation ID is included
        Log::shouldReceive('info')
            ->with(Mockery::pattern('/Token validation initiated/'), Mockery::on(function ($context) {
                return isset($context['operation_id']) && 
                       isset($context['proactive_refresh_threshold']) && 
                       $context['proactive_refresh_threshold'] === 15;
            }))
            ->once();

        Log::shouldReceive('info')
            ->with(Mockery::pattern('/Attempting coordinated Google Drive token refresh/'), Mockery::on(function ($context) {
                return isset($context['operation_id']) && 
                       isset($context['is_proactive_refresh']);
            }))
            ->once();

        Log::shouldReceive('info')
            ->with(Mockery::pattern('/Google Drive token validation successful/'), Mockery::on(function ($context) {
                return isset($context['operation_id']) && 
                       isset($context['result_type']);
            }))
            ->once();

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}