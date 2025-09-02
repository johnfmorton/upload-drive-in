<?php

namespace Tests\Feature;

use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CloudStorageControllerConsolidatedStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;
    private CloudStorageHealthService $healthService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->employeeUser = User::factory()->create(['role' => 'employee']);
        $this->healthService = app(CloudStorageHealthService::class);
    }

    /** @test */
    public function admin_get_status_returns_consolidated_status()
    {
        // Create a health status with consolidated status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'last_successful_operation_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.cloud-storage.status'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'providers' => [
                    '*' => [
                        'provider',
                        'status',
                        'consolidated_status',
                        'status_message',
                        'is_healthy',
                    ]
                ],
                'pending_uploads',
                'failed_uploads',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $providers = $response->json('providers');
        $googleDriveProvider = collect($providers)->firstWhere('provider', 'google-drive');
        
        $this->assertNotNull($googleDriveProvider);
        $this->assertEquals('healthy', $googleDriveProvider['consolidated_status']);
        $this->assertTrue($googleDriveProvider['is_healthy']);
    }

    /** @test */
    public function admin_test_connection_uses_proactive_validation()
    {
        // Create a health status that will be updated by the test
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.cloud-storage.test'), [
                'provider' => 'google-drive'
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'status',
                'consolidated_status',
                'status_message',
                'requires_reconnection',
                'last_successful_operation',
            ]);

        // Verify the response includes consolidated status information
        $this->assertArrayHasKey('consolidated_status', $response->json());
        $this->assertArrayHasKey('status_message', $response->json());
    }

    /** @test */
    public function employee_get_status_returns_consolidated_status()
    {
        // Create a health status with consolidated status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->employeeUser->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'last_successful_operation_at' => now(),
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->getJson(route('employee.cloud-storage.status', ['username' => $this->employeeUser->username]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'providers' => [
                    '*' => [
                        'provider',
                        'status',
                        'consolidated_status',
                        'status_message',
                        'is_healthy',
                    ]
                ],
                'pending_uploads',
                'failed_uploads',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $providers = $response->json('providers');
        $googleDriveProvider = collect($providers)->firstWhere('provider', 'google-drive');
        
        $this->assertNotNull($googleDriveProvider);
        $this->assertEquals('healthy', $googleDriveProvider['consolidated_status']);
        $this->assertTrue($googleDriveProvider['is_healthy']);
    }

    /** @test */
    public function employee_test_connection_uses_proactive_validation()
    {
        // Create a health status that will be updated by the test
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->employeeUser->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'connection_issues',
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->postJson(route('employee.cloud-storage.test', ['username' => $this->employeeUser->username]), [
                'provider' => 'google-drive'
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'status',
                'consolidated_status',
                'status_message',
                'requires_reconnection',
                'last_successful_operation',
            ]);

        // Verify the response includes consolidated status information
        $this->assertArrayHasKey('consolidated_status', $response->json());
        $this->assertArrayHasKey('status_message', $response->json());
    }

    /** @test */
    public function dashboard_get_status_returns_consolidated_status()
    {
        // Create a health status with consolidated status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'last_successful_operation_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.dashboard.cloud-storage-status'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'provider',
                        'status',
                        'consolidated_status',
                        'status_message',
                        'is_healthy',
                        'token_refresh_working',
                        'operational_test_result',
                    ]
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $googleDriveProvider = collect($data)->firstWhere('provider', 'google-drive');
        
        $this->assertNotNull($googleDriveProvider);
        $this->assertEquals('healthy', $googleDriveProvider['consolidated_status']);
        $this->assertTrue($googleDriveProvider['is_healthy']);
    }

    /** @test */
    public function dashboard_health_check_uses_comprehensive_validation()
    {
        // Create a health status that will be updated by the health check
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.dashboard.cloud-storage.health-check', ['provider' => 'google-drive']));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'provider',
                    'status',
                    'consolidated_status',
                    'status_message',
                    'is_healthy',
                    'requires_reconnection',
                    'token_refresh_working',
                    'operational_test_result',
                ],
                'message',
            ]);

        // Verify the response includes comprehensive status information
        $data = $response->json('data');
        $this->assertArrayHasKey('consolidated_status', $data);
        $this->assertArrayHasKey('status_message', $data);
        $this->assertArrayHasKey('token_refresh_working', $data);
        $this->assertArrayHasKey('operational_test_result', $data);
    }

    /** @test */
    public function error_responses_include_user_friendly_messages()
    {
        // Test admin controller error handling
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.cloud-storage.test'), [
                'provider' => 'invalid-provider'
            ]);

        $response->assertStatus(422); // Validation error

        // Test with valid provider but simulate service error
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.cloud-storage.test'), [
                'provider' => 'google-drive'
            ]);

        // Should not return 500 error, should handle gracefully
        $this->assertNotEquals(500, $response->status());
    }

    /** @test */
    public function consolidated_status_messages_are_consistent()
    {
        $testCases = [
            'healthy' => 'Connection is working properly',
            'authentication_required' => 'Please reconnect your account',
            'connection_issues' => 'Experiencing connectivity problems',
            'not_connected' => 'Account not connected',
        ];

        foreach ($testCases as $status => $expectedMessage) {
            CloudStorageHealthStatus::factory()->create([
                'user_id' => $this->adminUser->id,
                'provider' => 'google-drive',
                'status' => $status === 'healthy' ? 'healthy' : 'unhealthy',
                'consolidated_status' => $status,
            ]);

            $response = $this->actingAs($this->adminUser)
                ->getJson(route('admin.cloud-storage.status'));

            $response->assertOk();
            
            $providers = $response->json('providers');
            $googleDriveProvider = collect($providers)->firstWhere('provider', 'google-drive');
            
            $this->assertEquals($status, $googleDriveProvider['consolidated_status']);
            $this->assertEquals($expectedMessage, $googleDriveProvider['status_message']);

            // Clean up for next iteration
            CloudStorageHealthStatus::where('user_id', $this->adminUser->id)->delete();
        }
    }
}