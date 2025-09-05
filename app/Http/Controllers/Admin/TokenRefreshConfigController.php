<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TokenRefreshConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for managing token refresh configuration through admin interface.
 */
class TokenRefreshConfigController extends Controller
{
    public function __construct(
        private TokenRefreshConfigService $configService
    ) {}

    /**
     * Display the token refresh configuration dashboard.
     */
    public function index()
    {
        if (!$this->configService->getAdminConfig('enabled')) {
            abort(404, 'Token refresh admin interface is disabled');
        }

        $configuration = $this->configService->getConfigurationSummary();
        $validationErrors = $this->configService->validateConfiguration();
        $modifiableSettings = $this->configService->getAdminConfig('modifiable_settings') ?? [];
        $requireConfirmation = $this->configService->getAdminConfig('require_confirmation') ?? [];
        $allowRuntimeChanges = $this->configService->getAdminConfig('allow_runtime_changes');

        return view('admin.token-refresh.config', compact(
            'configuration',
            'validationErrors',
            'modifiableSettings',
            'requireConfirmation',
            'allowRuntimeChanges'
        ));
    }

    /**
     * Update a configuration setting.
     */
    public function updateSetting(Request $request)
    {
        if (!$this->configService->getAdminConfig('enabled')) {
            return response()->json(['error' => __('messages.token_config_admin_interface_disabled')], 403);
        }

        if (!$this->configService->getAdminConfig('allow_runtime_changes')) {
            return response()->json(['error' => __('messages.token_config_runtime_changes_disabled')], 403);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'value' => 'required',
            'confirmed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $key = $request->input('key');
        $value = $request->input('value');
        $confirmed = $request->input('confirmed', false);

        // Check if this setting requires confirmation
        $requireConfirmation = $this->configService->getAdminConfig('require_confirmation') ?? [];
        if (in_array($key, $requireConfirmation) && !$confirmed) {
            return response()->json([
                'requires_confirmation' => true,
                'message' => __('messages.token_config_change_requires_confirmation', ['key' => $key]),
            ]);
        }

        // Validate the specific setting
        $validationError = $this->validateSetting($key, $value);
        if ($validationError) {
            return response()->json(['error' => $validationError], 422);
        }

        // Convert value to appropriate type
        $value = $this->convertValue($key, $value);

        // Update the configuration
        $success = $this->configService->updateConfig($key, $value);

        if ($success) {
            Log::info('Token refresh configuration updated via admin interface', [
                'key' => $key,
                'value' => $value,
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.token_config_setting_updated', ['key' => $key]),
                'new_value' => $value,
            ]);
        } else {
            return response()->json(['error' => __('messages.token_config_update_failed')], 500);
        }
    }

    /**
     * Toggle a feature flag.
     */
    public function toggleFeature(Request $request)
    {
        if (!$this->configService->getAdminConfig('enabled')) {
            return response()->json(['error' => __('messages.token_config_admin_interface_disabled')], 403);
        }

        if (!$this->configService->getAdminConfig('allow_runtime_changes')) {
            return response()->json(['error' => __('messages.token_config_runtime_changes_disabled')], 403);
        }

        $validator = Validator::make($request->all(), [
            'feature' => 'required|string',
            'enabled' => 'required|boolean',
            'confirmed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $feature = $request->input('feature');
        $enabled = $request->input('enabled');
        $confirmed = $request->input('confirmed', false);
        $key = "features.{$feature}";

        // Check if this feature requires confirmation
        $requireConfirmation = $this->configService->getAdminConfig('require_confirmation') ?? [];
        if (in_array($key, $requireConfirmation) && !$confirmed) {
            return response()->json([
                'requires_confirmation' => true,
                'message' => __('messages.token_config_toggle_requires_confirmation', ['feature' => $feature]),
            ]);
        }

        // Update the feature flag
        $success = $this->configService->updateConfig($key, $enabled);

        if ($success) {
            Log::info('Token refresh feature flag toggled via admin interface', [
                'feature' => $feature,
                'enabled' => $enabled,
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $enabled 
                    ? __('messages.token_config_feature_enabled', ['feature' => $feature])
                    : __('messages.token_config_feature_disabled', ['feature' => $feature]),
                'enabled' => $enabled,
            ]);
        } else {
            return response()->json(['error' => __('messages.token_config_toggle_failed')], 500);
        }
    }

    /**
     * Clear configuration cache.
     */
    public function clearCache(Request $request)
    {
        if (!$this->configService->getAdminConfig('enabled')) {
            return response()->json(['error' => __('messages.token_config_admin_interface_disabled')], 403);
        }

        try {
            $this->configService->clearConfigCache();

            Log::info('Token refresh configuration cache cleared via admin interface', [
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.token_config_cache_cleared'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear token refresh configuration cache', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json(['error' => __('messages.token_config_cache_clear_failed')], 500);
        }
    }

    /**
     * Get current configuration status.
     */
    public function getStatus()
    {
        if (!$this->configService->getAdminConfig('enabled')) {
            return response()->json(['error' => __('messages.token_config_admin_interface_disabled')], 403);
        }

        $configuration = $this->configService->getConfigurationSummary();
        $validationErrors = $this->configService->validateConfiguration();

        return response()->json([
            'configuration' => $configuration,
            'validation_errors' => $validationErrors,
            'environment' => app()->environment(),
            'allow_runtime_changes' => $this->configService->getAdminConfig('allow_runtime_changes'),
        ]);
    }

    /**
     * Validate a specific setting value.
     */
    private function validateSetting(string $key, mixed $value): ?string
    {
        switch ($key) {
            case 'timing.proactive_refresh_minutes':
                if (!is_numeric($value) || $value < 1 || $value > 60) {
                    return __('messages.token_config_proactive_refresh_range');
                }
                break;

            case 'timing.background_refresh_minutes':
                if (!is_numeric($value) || $value < 5 || $value > 120) {
                    return __('messages.token_config_background_refresh_range');
                }
                break;

            case 'timing.max_retry_attempts':
                if (!is_numeric($value) || $value < 1 || $value > 10) {
                    return __('messages.token_config_max_retry_attempts_range');
                }
                break;

            case 'notifications.throttle_hours':
                if (!is_numeric($value) || $value < 1 || $value > 168) {
                    return __('messages.token_config_notification_throttle_range');
                }
                break;

            case 'rate_limiting.max_attempts_per_hour':
                if (!is_numeric($value) || $value < 1 || $value > 100) {
                    return __('messages.token_config_max_attempts_per_hour_range');
                }
                break;

            default:
                // No specific validation for this key
                break;
        }

        return null;
    }

    /**
     * Convert value to appropriate type based on key.
     */
    private function convertValue(string $key, mixed $value): mixed
    {
        // Boolean values
        if (str_starts_with($key, 'features.')) {
            return (bool) $value;
        }

        // Integer values
        if (str_starts_with($key, 'timing.') || str_starts_with($key, 'rate_limiting.') || str_starts_with($key, 'notifications.')) {
            if (in_array($key, ['notifications.enabled', 'notifications.escalate_to_admin'])) {
                return (bool) $value;
            }
            return (int) $value;
        }

        // String values (default)
        return (string) $value;
    }
}