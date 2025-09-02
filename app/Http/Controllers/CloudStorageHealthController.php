<?php

namespace App\Http\Controllers;

use App\Services\CloudStorageProviderHealthCheckService;
use App\Services\CloudStorageConfigurationValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CloudStorageHealthController extends Controller
{
    public function __construct(
        private readonly CloudStorageProviderHealthCheckService $healthCheckService,
        private readonly CloudStorageConfigurationValidationService $configValidationService
    ) {}

    /**
     * Basic health check endpoint
     */
    public function basic(): JsonResponse
    {
        try {
            $summary = $this->configValidationService->getValidationSummary();
            
            $status = $summary['overall_status'] === 'valid' ? 'healthy' : 'unhealthy';
            $httpStatus = $status === 'healthy' ? 200 : 503;

            return response()->json([
                'status' => $status,
                'timestamp' => now()->toISOString(),
                'providers' => [
                    'total' => $summary['total_providers'],
                    'valid' => count($summary['valid_providers']),
                    'invalid' => count($summary['invalid_providers']),
                ],
            ], $httpStatus);

        } catch (\Exception $e) {
            Log::error('Basic health check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'error' => 'Health check failed',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Comprehensive health check endpoint
     */
    public function comprehensive(Request $request): JsonResponse
    {
        try {
            $useCache = $request->boolean('cache', true);
            $forceRefresh = $request->boolean('force_refresh', false);
            
            $results = $this->healthCheckService->performHealthCheckWithCaching($forceRefresh || !$useCache);
            
            $httpStatus = match ($results['overall_status']) {
                'healthy' => 200,
                'degraded', 'warning' => 200, // Still operational
                'critical' => 503, // Service unavailable
                'error' => 500,
                default => 200,
            };

            return response()->json($results, $httpStatus);

        } catch (\Exception $e) {
            Log::error('Comprehensive health check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'overall_status' => 'error',
                'error' => 'Health check failed',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Provider-specific health check
     */
    public function provider(Request $request, string $provider): JsonResponse
    {
        try {
            $results = $this->healthCheckService->checkProviderHealth($provider);
            
            $httpStatus = match ($results['status']) {
                'healthy' => 200,
                'not_configured', 'disabled' => 200, // Expected states
                'configuration_invalid', 'instantiation_failed', 'connectivity_failed' => 503,
                'error' => 500,
                default => 200,
            };

            return response()->json([
                'provider' => $provider,
                'health' => $results,
                'timestamp' => now()->toISOString(),
            ], $httpStatus);

        } catch (\Exception $e) {
            Log::error('Provider health check failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'provider' => $provider,
                'status' => 'error',
                'error' => 'Health check failed',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * User-specific health check (requires authentication)
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Authentication required',
                ], 401);
            }

            $results = $this->healthCheckService->checkUserHealth($user);
            
            $httpStatus = match ($results['overall_status']) {
                'healthy' => 200,
                'partially_healthy' => 200, // Some providers working
                'unhealthy' => 503,
                default => 200,
            };

            return response()->json([
                'user_id' => $user->id,
                'health' => $results,
                'timestamp' => now()->toISOString(),
            ], $httpStatus);

        } catch (\Exception $e) {
            Log::error('User health check failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Health check failed',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Configuration validation endpoint
     */
    public function configuration(Request $request): JsonResponse
    {
        try {
            $provider = $request->query('provider');
            
            if ($provider) {
                $results = $this->configValidationService->validateProviderConfiguration($provider);
                $httpStatus = $results['is_valid'] ? 200 : 400;
            } else {
                $results = $this->configValidationService->validateAllProviderConfigurations();
                $httpStatus = $results['summary']['invalid_count'] === 0 ? 200 : 400;
            }

            return response()->json([
                'validation' => $results,
                'timestamp' => now()->toISOString(),
            ], $httpStatus);

        } catch (\Exception $e) {
            Log::error('Configuration validation failed', [
                'provider' => $request->query('provider'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Configuration validation failed',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * System readiness check (for load balancers)
     */
    public function readiness(): JsonResponse
    {
        try {
            // Check if at least one provider is healthy
            $hasValidProviders = $this->configValidationService->hasValidProviders();
            
            if (!$hasValidProviders) {
                return response()->json([
                    'ready' => false,
                    'reason' => 'No valid cloud storage providers configured',
                    'timestamp' => now()->toISOString(),
                ], 503);
            }

            // Check if we can perform basic operations
            $firstValidProvider = $this->configValidationService->getFirstValidProvider();
            
            if (!$firstValidProvider) {
                return response()->json([
                    'ready' => false,
                    'reason' => 'No operational cloud storage providers available',
                    'timestamp' => now()->toISOString(),
                ], 503);
            }

            return response()->json([
                'ready' => true,
                'providers_available' => true,
                'primary_provider' => $firstValidProvider,
                'timestamp' => now()->toISOString(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Readiness check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ready' => false,
                'reason' => 'System error during readiness check',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * System liveness check (for container orchestration)
     */
    public function liveness(): JsonResponse
    {
        try {
            // Basic liveness check - just verify the application is responding
            // and core services are available
            
            $checks = [
                'database' => $this->checkDatabaseConnection(),
                'cache' => $this->checkCacheConnection(),
                'storage' => $this->checkStorageAccess(),
            ];

            $allHealthy = array_reduce($checks, function ($carry, $check) {
                return $carry && $check['healthy'];
            }, true);

            $httpStatus = $allHealthy ? 200 : 503;

            return response()->json([
                'alive' => $allHealthy,
                'checks' => $checks,
                'timestamp' => now()->toISOString(),
            ], $httpStatus);

        } catch (\Exception $e) {
            Log::error('Liveness check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'alive' => false,
                'error' => 'Liveness check failed',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['healthy' => true, 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check cache connection
     */
    private function checkCacheConnection(): array
    {
        try {
            \Cache::put('health_check', 'ok', 10);
            $value = \Cache::get('health_check');
            return ['healthy' => $value === 'ok', 'message' => 'Cache connection OK'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => 'Cache connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check storage access
     */
    private function checkStorageAccess(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            \Storage::put($testFile, 'health check');
            $exists = \Storage::exists($testFile);
            \Storage::delete($testFile);
            
            return ['healthy' => $exists, 'message' => 'Storage access OK'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => 'Storage access failed: ' . $e->getMessage()];
        }
    }
}