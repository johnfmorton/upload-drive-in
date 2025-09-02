<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudStorageHealthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_health_check_endpoint()
    {
        $response = $this->get('/health/cloud-storage/basic');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'providers' => [
                'total',
                'valid',
                'invalid',
            ],
        ]);

        $data = $response->json();
        $this->assertContains($data['status'], ['healthy', 'unhealthy']);
        $this->assertIsString($data['timestamp']);
        $this->assertIsInt($data['providers']['total']);
        $this->assertIsInt($data['providers']['valid']);
        $this->assertIsInt($data['providers']['invalid']);
    }

    public function test_comprehensive_health_check_endpoint()
    {
        $response = $this->get('/health/cloud-storage/comprehensive');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'overall_status',
            'timestamp',
            'duration_ms',
            'configuration_validation',
            'provider_health',
            'user_health',
            'summary',
            'recommendations',
        ]);

        $data = $response->json();
        $this->assertContains($data['overall_status'], ['healthy', 'degraded', 'warning', 'critical', 'error']);
        $this->assertIsString($data['timestamp']);
        $this->assertIsNumeric($data['duration_ms']);
    }

    public function test_comprehensive_health_check_with_cache_parameter()
    {
        // First request without cache
        $response1 = $this->get('/health/cloud-storage/comprehensive?cache=false');
        $response1->assertStatus(200);
        $timestamp1 = $response1->json('timestamp');

        // Second request with cache enabled
        $response2 = $this->get('/health/cloud-storage/comprehensive?cache=true');
        $response2->assertStatus(200);
        $timestamp2 = $response2->json('timestamp');

        // With caching, timestamps might be the same
        $this->assertIsString($timestamp1);
        $this->assertIsString($timestamp2);
    }

    public function test_comprehensive_health_check_with_force_refresh()
    {
        $response = $this->get('/health/cloud-storage/comprehensive?force_refresh=true');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'overall_status',
            'timestamp',
            'duration_ms',
        ]);
    }

    public function test_provider_specific_health_check()
    {
        $response = $this->get('/health/cloud-storage/provider/google-drive');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'provider',
            'health' => [
                'provider_name',
                'status',
                'is_configured',
                'is_enabled',
                'configuration_valid',
                'can_instantiate',
                'errors',
                'warnings',
                'last_checked',
            ],
            'timestamp',
        ]);

        $data = $response->json();
        $this->assertEquals('google-drive', $data['provider']);
        $this->assertEquals('google-drive', $data['health']['provider_name']);
    }

    public function test_provider_health_check_with_invalid_provider()
    {
        $response = $this->get('/health/cloud-storage/provider/invalid-provider');

        // Should still return a response, but with error status
        $response->assertStatus(500);
        $response->assertJsonStructure([
            'provider',
            'status',
            'error',
            'timestamp',
        ]);

        $data = $response->json();
        $this->assertEquals('invalid-provider', $data['provider']);
        $this->assertEquals('error', $data['status']);
    }

    public function test_user_health_check_requires_authentication()
    {
        $response = $this->get('/health/cloud-storage/user');

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Authentication required',
        ]);
    }

    public function test_user_health_check_with_authenticated_user()
    {
        $user = User::factory()->create();
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $response = $this->actingAs($user)->get('/health/cloud-storage/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user_id',
            'health' => [
                'user_id',
                'user_email',
                'providers',
                'overall_status',
                'healthy_providers',
                'unhealthy_providers',
                'last_checked',
            ],
            'timestamp',
        ]);

        $data = $response->json();
        $this->assertEquals($user->id, $data['user_id']);
        $this->assertEquals($user->id, $data['health']['user_id']);
        $this->assertEquals($user->email, $data['health']['user_email']);
    }

    public function test_configuration_validation_endpoint()
    {
        $response = $this->get('/health/cloud-storage/configuration');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'validation' => [
                'valid',
                'invalid',
                'warnings',
                'summary' => [
                    'total_providers',
                    'valid_count',
                    'invalid_count',
                    'warning_count',
                ],
            ],
            'timestamp',
        ]);

        $data = $response->json();
        $this->assertIsArray($data['validation']['valid']);
        $this->assertIsArray($data['validation']['invalid']);
        $this->assertIsArray($data['validation']['warnings']);
        $this->assertIsInt($data['validation']['summary']['total_providers']);
    }

    public function test_configuration_validation_for_specific_provider()
    {
        $response = $this->get('/health/cloud-storage/configuration?provider=google-drive');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'validation' => [
                'provider_name',
                'is_valid',
                'errors',
                'warnings',
                'config_sources',
                'provider_class_valid',
                'interface_compliance',
            ],
            'timestamp',
        ]);

        $data = $response->json();
        $this->assertEquals('google-drive', $data['validation']['provider_name']);
        $this->assertIsBool($data['validation']['is_valid']);
    }

    public function test_readiness_check_endpoint()
    {
        $response = $this->get('/health/cloud-storage/readiness');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ready',
            'timestamp',
        ]);

        $data = $response->json();
        $this->assertIsBool($data['ready']);
        $this->assertIsString($data['timestamp']);
    }

    public function test_liveness_check_endpoint()
    {
        $response = $this->get('/health/cloud-storage/liveness');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'alive',
            'checks' => [
                'database',
                'cache',
                'storage',
            ],
            'timestamp',
        ]);

        $data = $response->json();
        $this->assertIsBool($data['alive']);
        $this->assertIsArray($data['checks']['database']);
        $this->assertIsArray($data['checks']['cache']);
        $this->assertIsArray($data['checks']['storage']);

        // Each check should have healthy and message keys
        foreach (['database', 'cache', 'storage'] as $check) {
            $this->assertArrayHasKey('healthy', $data['checks'][$check]);
            $this->assertArrayHasKey('message', $data['checks'][$check]);
            $this->assertIsBool($data['checks'][$check]['healthy']);
            $this->assertIsString($data['checks'][$check]['message']);
        }
    }

    public function test_health_check_endpoints_return_appropriate_http_status_codes()
    {
        // Test that unhealthy states return appropriate HTTP status codes
        $response = $this->get('/health/cloud-storage/basic');
        
        // Should return 200 for healthy or 503 for unhealthy
        $this->assertContains($response->status(), [200, 503]);
        
        if ($response->status() === 503) {
            $data = $response->json();
            $this->assertEquals('unhealthy', $data['status']);
        }
    }

    public function test_health_check_endpoints_handle_exceptions_gracefully()
    {
        // All endpoints should handle exceptions gracefully and return error responses
        $endpoints = [
            '/health/cloud-storage/basic',
            '/health/cloud-storage/comprehensive',
            '/health/cloud-storage/provider/google-drive',
            '/health/cloud-storage/configuration',
            '/health/cloud-storage/readiness',
            '/health/cloud-storage/liveness',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->get($endpoint);
            
            // Should not return 500 errors under normal circumstances
            // If it does return 500, it should have proper error structure
            if ($response->status() === 500) {
                $response->assertJsonStructure([
                    'error',
                    'timestamp',
                ]);
            }
        }
    }

    public function test_health_check_endpoints_include_timestamps()
    {
        $endpoints = [
            '/health/cloud-storage/basic',
            '/health/cloud-storage/comprehensive',
            '/health/cloud-storage/provider/google-drive',
            '/health/cloud-storage/configuration',
            '/health/cloud-storage/readiness',
            '/health/cloud-storage/liveness',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->get($endpoint);
            
            if ($response->status() < 500) {
                $data = $response->json();
                $this->assertArrayHasKey('timestamp', $data);
                $this->assertIsString($data['timestamp']);
                
                // Verify timestamp is in ISO format
                $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $data['timestamp']);
            }
        }
    }
}