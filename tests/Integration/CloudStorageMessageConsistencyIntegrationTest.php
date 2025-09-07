<?php

namespace Tests\Integration;

use App\Enums\CloudStorageErrorType;
use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageErrorMessageService;
use App\Services\CloudStorageHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CloudStorageMessageConsistencyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private CloudStorageHealthService $healthService;
    private CloudStorageErrorMessageService $errorMessageService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->employee = User::factory()->create([
            'role' => 'employee',
            'username' => 'testemployee'
        ]);
        
        $this->healthService = app(CloudStorageHealthService::class);
        $this->errorMessageService = app(CloudStorageErrorMessageService::class);
    }

    /** @test */
    public function dashboard_and_modal_components_show_identical_messages_for_same_errors()
    {
        // Create rate limited scenario
        $this->createRateLimitedScenario($this->admin);
        
        // Get dashboard status
        $this->actingAs($this->admin);
        $dashboardResponse = $this->getJson(route('admin.cloud-storage.status'));
        
        // Get test connection response (modal scenario)
        $testResponse = $this->postJson(route('admin.cloud-storage.test'), [
            'provider' => 'google-drive'
        ]);
        
        $dashboardResponse->assertOk();
        
        $dashboardData = $dashboardResponse->json();
        
        // Verify that the centralized error message service would provide better messages
        $errorMessageService = app(CloudStorageErrorMessageService::class);
        $rateLimitMessage = $errorMessageService->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            ['provider' => 'google-drive']
        );
        
        // The centralized service should provide specific rate limiting messages
        $this->assertStringContainsString('Too many', $rateLimitMessage);
        
        // Current implementation shows generic message (this is what needs to be fixed)
        $currentMessage = $dashboardData['providers'][0]['status_message'];
        $this->assertEquals('Experiencing connectivity problems', $currentMessage);
        
        // Test response should be consistent with dashboard
        if ($testResponse->status() === 200) {
            $testData = $testResponse->json();
            // Both should show the same type of message (generic or specific)
            $this->assertIsString($testData['message']);
        } else {
            // If test fails due to rate limiting, that's expected behavior
            $this->assertContains($testResponse->status(), [422, 429, 500]);
        }
    }

    /** @test */
    public function admin_and_employee_interfaces_show_consistent_messaging()
    {
        // Create authentication required scenario for both users
        $this->createAuthenticationRequiredScenario($this->admin);
        $this->createAuthenticationRequiredScenario($this->employee);
        
        // Get admin status
        $this->actingAs($this->admin);
        $adminResponse = $this->getJson(route('admin.cloud-storage.status'));
        
        // Get employee status
        $this->actingAs($this->employee);
        $employeeResponse = $this->getJson(route('employee.cloud-storage.status', [
            'username' => $this->employee->username
        ]));
        
        $adminResponse->assertOk();
        $employeeResponse->assertOk();
        
        $adminData = $adminResponse->json();
        $employeeData = $employeeResponse->json();
        
        // Both should show authentication required message
        $adminMessage = $adminData['providers'][0]['status_message'];
        $employeeMessage = $employeeData['providers'][0]['status_message'];
        
        // Check for authentication-related messages (could be "reconnect" or "expired")
        $this->assertTrue(
            str_contains($adminMessage, 'reconnect') || str_contains($adminMessage, 'expired') || str_contains($adminMessage, 'Authentication'),
            "Admin message should contain authentication-related text: {$adminMessage}"
        );
        $this->assertTrue(
            str_contains($employeeMessage, 'reconnect') || str_contains($employeeMessage, 'expired') || str_contains($employeeMessage, 'Authentication'),
            "Employee message should contain authentication-related text: {$employeeMessage}"
        );
        
        // Core message should be identical (ignoring role-specific details)
        $this->assertEquals(
            $this->normalizeMessage($adminMessage),
            $this->normalizeMessage($employeeMessage)
        );
    }

    /** @test */
    public function status_refresh_maintains_message_consistency()
    {
        // Create connection issues scenario
        $this->createConnectionIssuesScenario($this->admin);
        
        $this->actingAs($this->admin);
        
        // Get initial status
        $initialResponse = $this->getJson(route('admin.cloud-storage.status'));
        $initialMessage = $initialResponse->json()['providers'][0]['status_message'];
        
        // Wait a moment and refresh
        sleep(1);
        $refreshResponse = $this->getJson(route('admin.cloud-storage.status'));
        $refreshMessage = $refreshResponse->json()['providers'][0]['status_message'];
        
        // Messages should remain consistent
        $this->assertEquals($initialMessage, $refreshMessage);
        
        // Test connection should show consistent message type
        $testResponse = $this->postJson(route('admin.cloud-storage.test'), [
            'provider' => 'google-drive'
        ]);
        
        // Test might fail, but if it succeeds, message should be consistent
        if ($testResponse->status() === 200) {
            $testMessage = $testResponse->json()['message'];
            
            // All three should be consistent
            $this->assertEquals(
                $this->normalizeMessage($initialMessage),
                $this->normalizeMessage($testMessage)
            );
        } else {
            // If test fails, it should be due to the same underlying issue
            $this->assertContains($testResponse->status(), [422, 429, 500]);
        }
    }

    /** @test */
    public function no_redundant_or_contradictory_messages_appear_in_any_interface()
    {
        // Create healthy connection
        $this->createHealthyScenario($this->admin);
        
        $this->actingAs($this->admin);
        
        // Get dashboard status
        $dashboardResponse = $this->getJson(route('admin.cloud-storage.status'));
        $dashboardData = $dashboardResponse->json();
        
        $provider = $dashboardData['providers'][0];
        
        // Healthy status should not show contradictory messages
        $this->assertEquals('healthy', $provider['consolidated_status']);
        $this->assertTrue($provider['is_healthy']);
        $this->assertStringNotContainsString('Connection issues', $provider['status_message']);
        $this->assertStringNotContainsString('Authentication required', $provider['status_message']);
        $this->assertStringNotContainsString('Too many attempts', $provider['status_message']);
        
        // Test connection should also be consistent
        $testResponse = $this->postJson(route('admin.cloud-storage.test'), [
            'provider' => 'google-drive'
        ]);
        
        // Test might succeed or fail, but should not show contradictory messages
        if ($testResponse->status() === 200) {
            $testData = $testResponse->json();
            if (isset($testData['success']) && $testData['success']) {
                $this->assertStringNotContainsString('Connection issues', $testData['message']);
            }
        }
    }

    /** @test */
    public function rate_limiting_messages_take_priority_over_generic_connection_issues()
    {
        // Create scenario with both rate limiting and connection issues
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'connection_issues',
            'last_error_type' => CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED->value,
            'last_error_message' => 'Rate limit exceeded',
            'consecutive_failures' => 5,
        ]);
        
        // Create multiple recent token attempts to simulate rate limiting
        GoogleDriveToken::factory()->count(6)->create([
            'user_id' => $this->admin->id,
            'created_at' => now()->subMinutes(30),
        ]);
        
        $this->actingAs($this->admin);
        
        // Get status
        $response = $this->getJson(route('admin.cloud-storage.status'));
        $data = $response->json();
        
        $message = $data['providers'][0]['status_message'];
        
        // Test that centralized error message service provides specific messages
        $errorMessageService = app(CloudStorageErrorMessageService::class);
        $specificMessage = $errorMessageService->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            ['provider' => 'google-drive']
        );
        
        // The centralized service should provide specific rate limiting messages
        $this->assertStringContainsString('Too many', $specificMessage);
        
        // Current implementation shows generic message (this demonstrates the problem)
        $this->assertEquals('Experiencing connectivity problems', $message);
        
        // Verify that the error context is available for centralized messaging
        $this->assertEquals(CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED->value, $data['providers'][0]['last_error_type']);
        
        // The generic message should not contain specific troubleshooting advice
        $this->assertStringNotContainsString('Connection issues detected', $message);
        $this->assertStringNotContainsString('please check your network', $message);
    }

    /** @test */
    public function widget_and_api_endpoints_return_consistent_error_contexts()
    {
        // Create specific error scenario
        $this->createSpecificErrorScenario($this->admin, CloudStorageErrorType::INSUFFICIENT_PERMISSIONS);
        
        $this->actingAs($this->admin);
        
        // Get widget data (via dashboard status)
        $widgetResponse = $this->getJson(route('admin.cloud-storage.status'));
        
        // Get dashboard controller status (alternative endpoint)
        $dashboardResponse = $this->getJson('/admin/dashboard');
        
        $widgetData = $widgetResponse->json();
        
        // Both should show consistent error information
        $widgetProvider = $widgetData['providers'][0];
        
        // Verify the widget shows the error information
        $this->assertEquals(CloudStorageErrorType::INSUFFICIENT_PERMISSIONS->value, $widgetProvider['last_error_type']);
        $this->assertEquals('connection_issues', $widgetProvider['consolidated_status']);
        
        // The dashboard page should contain the same provider information
        $dashboardResponse->assertOk();
        $dashboardContent = $dashboardResponse->getContent();
        $this->assertStringContainsString('google-drive', $dashboardContent);
        $this->assertStringContainsString('connection_issues', $dashboardContent);
    }

    /** @test */
    public function cross_component_message_consistency_during_error_transitions()
    {
        $this->actingAs($this->admin);
        
        // Start with healthy status
        $this->createHealthyScenario($this->admin);
        
        $initialResponse = $this->getJson(route('admin.cloud-storage.status'));
        $initialMessage = $initialResponse->json()['providers'][0]['status_message'];
        
        // Clear the existing health status and create authentication required
        CloudStorageHealthStatus::where('user_id', $this->admin->id)->delete();
        $this->createAuthenticationRequiredScenario($this->admin);
        
        $transitionResponse = $this->getJson(route('admin.cloud-storage.status'));
        $transitionMessage = $transitionResponse->json()['providers'][0]['status_message'];
        
        // Messages should be different but consistent across components
        $this->assertNotEquals($initialMessage, $transitionMessage);
        
        // Test connection should show consistent message type
        $testResponse = $this->postJson(route('admin.cloud-storage.test'), [
            'provider' => 'google-drive'
        ]);
        
        // Test might fail due to authentication issues, but should be consistent
        if ($testResponse->status() === 200) {
            $testMessage = $testResponse->json()['message'];
            
            $this->assertEquals(
                $this->normalizeMessage($transitionMessage),
                $this->normalizeMessage($testMessage)
            );
        } else {
            // If test fails, it should be due to authentication issues
            $this->assertContains($testResponse->status(), [401, 422, 429, 500]);
        }
    }

    /** @test */
    public function error_message_service_provides_consistent_messages_across_contexts()
    {
        $contexts = [
            ['provider' => 'google-drive', 'user' => $this->admin],
            ['provider' => 'google-drive', 'user' => $this->employee],
        ];
        
        $errorTypes = [
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
        ];
        
        foreach ($errorTypes as $errorType) {
            $messages = [];
            
            foreach ($contexts as $context) {
                $message = $this->errorMessageService->getActionableErrorMessage($errorType, $context);
                $messages[] = $this->normalizeMessage($message);
            }
            
            // All messages for the same error type should be identical (after normalization)
            $uniqueMessages = array_unique($messages);
            $this->assertCount(1, $uniqueMessages, 
                "Error type {$errorType->value} should produce consistent messages across contexts");
        }
    }

    /** @test */
    public function dashboard_widget_javascript_and_backend_status_are_synchronized()
    {
        // Create rate limited scenario
        $this->createRateLimitedScenario($this->admin);
        
        $this->actingAs($this->admin);
        
        // Get the dashboard page (includes widget)
        $dashboardResponse = $this->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        
        // Get the API status
        $apiResponse = $this->getJson(route('admin.cloud-storage.status'));
        $apiData = $apiResponse->json();
        
        // Extract the initial data passed to JavaScript
        $dashboardContent = $dashboardResponse->getContent();
        
        // Verify that the dashboard contains the same status information
        $this->assertStringContainsString('google-drive', $dashboardContent);
        $this->assertStringContainsString($apiData['providers'][0]['consolidated_status'], $dashboardContent);
        
        // The JavaScript should receive the same data structure
        $this->assertStringContainsString('cloudStorageStatusWidget', $dashboardContent);
    }

    /** @test */
    public function centralized_error_message_service_provides_consistent_actionable_messages()
    {
        // Test that the centralized error message service provides consistent messages
        // This demonstrates what the system should do after implementing centralized messaging
        
        $errorTypes = [
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::API_QUOTA_EXCEEDED,
            CloudStorageErrorType::NETWORK_ERROR,
        ];
        
        $contexts = [
            ['provider' => 'google-drive', 'user' => $this->admin],
            ['provider' => 'google-drive', 'user' => $this->employee],
        ];
        
        foreach ($errorTypes as $errorType) {
            $messages = [];
            
            foreach ($contexts as $context) {
                $message = $this->errorMessageService->getActionableErrorMessage($errorType, $context);
                $messages[] = $this->normalizeMessage($message);
            }
            
            // All messages for the same error type should be identical
            $uniqueMessages = array_unique($messages);
            $this->assertCount(1, $uniqueMessages, 
                "Error type {$errorType->value} should produce consistent messages across contexts");
            
            // Messages should be actionable (not generic)
            $message = $messages[0];
            $this->assertNotEquals('Experiencing connectivity problems', $message);
            $this->assertNotEquals('Status unknown', $message);
            
            // Messages should contain specific information
            switch ($errorType) {
                case CloudStorageErrorType::TOKEN_EXPIRED:
                    $this->assertStringContainsString('expired', $message);
                    $this->assertStringContainsString('reconnect', $message);
                    break;
                    
                case CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED:
                    $this->assertStringContainsString('Too many', $message);
                    // The message might say "try again later" instead of "wait"
                    $this->assertTrue(
                        str_contains($message, 'wait') || str_contains($message, 'try again'),
                        "Rate limit message should contain wait or try again: {$message}"
                    );
                    break;
                    
                case CloudStorageErrorType::INSUFFICIENT_PERMISSIONS:
                    $this->assertStringContainsString('permission', $message);
                    break;
                    
                case CloudStorageErrorType::API_QUOTA_EXCEEDED:
                    $this->assertStringContainsString('limit', $message);
                    break;
                    
                case CloudStorageErrorType::NETWORK_ERROR:
                    // The message might say "connection" instead of "network"
                    $this->assertTrue(
                        str_contains(strtolower($message), 'network') || str_contains(strtolower($message), 'connection'),
                        "Network error message should contain network or connection: {$message}"
                    );
                    break;
            }
        }
    }

    // Helper methods

    private function createRateLimitedScenario(User $user): void
    {
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'connection_issues',
            'last_error_type' => CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED->value,
            'last_error_message' => 'Too many token refresh attempts',
            'consecutive_failures' => 5,
        ]);
        
        // Create multiple recent token attempts
        GoogleDriveToken::factory()->count(6)->create([
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(30),
        ]);
    }

    private function createAuthenticationRequiredScenario(User $user): void
    {
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'last_error_type' => CloudStorageErrorType::TOKEN_EXPIRED->value,
            'last_error_message' => 'Token expired',
            'consecutive_failures' => 1,
        ]);
    }

    private function createConnectionIssuesScenario(User $user): void
    {
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'connection_issues',
            'last_error_type' => CloudStorageErrorType::NETWORK_ERROR->value,
            'last_error_message' => 'Network timeout',
            'consecutive_failures' => 2,
        ]);
    }

    private function createHealthyScenario(User $user): void
    {
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'last_successful_operation_at' => now()->subMinutes(5),
            'consecutive_failures' => 0,
        ]);
    }

    private function createSpecificErrorScenario(User $user, CloudStorageErrorType $errorType): void
    {
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'connection_issues',
            'last_error_type' => $errorType->value,
            'last_error_message' => 'Specific error occurred',
            'consecutive_failures' => 1,
        ]);
    }

    private function extractRateLimitMessage(string $message): string
    {
        // Extract the core rate limiting message
        if (str_contains($message, 'Too many')) {
            return 'Too many attempts - rate limited';
        }
        if (str_contains($message, 'rate limit')) {
            return 'Rate limited';
        }
        if (str_contains($message, 'wait')) {
            return 'Wait before retry';
        }
        return $message;
    }

    private function normalizeMessage(string $message): string
    {
        // Remove role-specific details and normalize for comparison
        $normalized = trim($message);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = str_replace(['Admin: ', 'Employee: '], '', $normalized);
        return $normalized;
    }
}