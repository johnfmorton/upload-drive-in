<?php

namespace App\Console\Commands;

use App\Services\TokenRefreshConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Console command for managing token refresh configuration.
 */
class TokenRefreshConfig extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'token-refresh:config 
                            {action : The action to perform (show|set|toggle|validate|clear-cache)}
                            {--key= : Configuration key for set action}
                            {--value= : Configuration value for set action}
                            {--feature= : Feature name for toggle action}
                            {--enabled= : Enable/disable feature (true/false)}
                            {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     */
    protected $description = 'Manage token refresh configuration and feature flags';

    public function __construct(
        private TokenRefreshConfigService $configService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'show' => $this->showConfiguration(),
            'set' => $this->setSetting(),
            'toggle' => $this->toggleFeature(),
            'validate' => $this->validateConfiguration(),
            'clear-cache' => $this->clearCache(),
            default => $this->error(__('messages.token_config_cmd_unknown_action', ['action' => $action])) ?: self::FAILURE,
        };
    }

    /**
     * Show current configuration.
     */
    private function showConfiguration(): int
    {
        $configuration = $this->configService->getConfigurationSummary();
        $format = $this->option('format');

        if ($format === 'json') {
            $this->line(json_encode($configuration, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info('Token Refresh Configuration');
        $this->line('Environment: ' . $configuration['environment']);
        $this->newLine();

        // Feature Flags
        $this->info('Feature Flags:');
        $featureRows = [];
        foreach ($configuration['features'] as $feature => $enabled) {
            $featureRows[] = [
                ucwords(str_replace('_', ' ', $feature)),
                $enabled ? '✓ Enabled' : '✗ Disabled',
            ];
        }
        $this->table(['Feature', 'Status'], $featureRows);
        $this->newLine();

        // Timing Configuration
        $this->info('Timing Configuration:');
        $timingRows = [];
        foreach ($configuration['timing'] as $key => $value) {
            $timingRows[] = [
                ucwords(str_replace('_', ' ', $key)),
                $value,
            ];
        }
        $this->table(['Setting', 'Value'], $timingRows);
        $this->newLine();

        // Notifications
        $this->info('Notification Configuration:');
        $notificationRows = [];
        foreach ($configuration['notifications'] as $key => $value) {
            $notificationRows[] = [
                ucwords(str_replace('_', ' ', $key)),
                is_bool($value) ? ($value ? 'Yes' : 'No') : $value,
            ];
        }
        $this->table(['Setting', 'Value'], $notificationRows);
        $this->newLine();

        // Rate Limiting
        $this->info('Rate Limiting Configuration:');
        $rateLimitRows = [];
        foreach ($configuration['rate_limiting'] as $key => $value) {
            $rateLimitRows[] = [
                ucwords(str_replace('_', ' ', $key)),
                is_bool($value) ? ($value ? 'Yes' : 'No') : $value,
            ];
        }
        $this->table(['Setting', 'Value'], $rateLimitRows);

        return self::SUCCESS;
    }

    /**
     * Set a configuration value.
     */
    private function setSetting(): int
    {
        $key = $this->option('key');
        $value = $this->option('value');

        if (!$key || $value === null) {
            $this->error(__('messages.token_config_cmd_key_value_required'));
            return self::FAILURE;
        }

        if (!$this->configService->getAdminConfig('allow_runtime_changes')) {
            $this->error(__('messages.token_config_runtime_changes_disabled'));
            return self::FAILURE;
        }

        // Validate the setting
        $validator = Validator::make(['key' => $key, 'value' => $value], [
            'key' => 'required|string',
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            $this->error(__('messages.token_config_cmd_validation_failed', ['errors' => implode(', ', $validator->errors()->all())]));
            return self::FAILURE;
        }

        // Convert value to appropriate type
        $value = $this->convertValue($key, $value);

        // Check if confirmation is required
        $requireConfirmation = $this->configService->getAdminConfig('require_confirmation') ?? [];
        if (in_array($key, $requireConfirmation)) {
            if (!$this->confirm(__('messages.token_config_cmd_change_confirmation', ['key' => $key]))) {
                $this->info(__('messages.token_config_cmd_operation_cancelled'));
                return self::SUCCESS;
            }
        }

        // Update the configuration
        $success = $this->configService->updateConfig($key, $value);

        if ($success) {
            $this->info(__('messages.token_config_cmd_setting_updated', ['key' => $key, 'value' => $value]));
            return self::SUCCESS;
        } else {
            $this->error(__('messages.token_config_cmd_setting_update_failed', ['key' => $key]));
            return self::FAILURE;
        }
    }

    /**
     * Toggle a feature flag.
     */
    private function toggleFeature(): int
    {
        $feature = $this->option('feature');
        $enabled = $this->option('enabled');

        if (!$feature || $enabled === null) {
            $this->error(__('messages.token_config_cmd_feature_enabled_required'));
            return self::FAILURE;
        }

        if (!$this->configService->getAdminConfig('allow_runtime_changes')) {
            $this->error(__('messages.token_config_runtime_changes_disabled'));
            return self::FAILURE;
        }

        $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        $key = "features.{$feature}";

        // Check if confirmation is required
        $requireConfirmation = $this->configService->getAdminConfig('require_confirmation') ?? [];
        if (in_array($key, $requireConfirmation)) {
            if (!$this->confirm(__('messages.token_config_cmd_toggle_confirmation', ['feature' => $feature]))) {
                $this->info(__('messages.token_config_cmd_operation_cancelled'));
                return self::SUCCESS;
            }
        }

        // Update the feature flag
        $success = $this->configService->updateConfig($key, $enabled);

        if ($success) {
            $message = $enabled 
                ? __('messages.token_config_cmd_feature_enabled', ['feature' => $feature])
                : __('messages.token_config_cmd_feature_disabled', ['feature' => $feature]);
            $this->info($message);
            return self::SUCCESS;
        } else {
            $this->error(__('messages.token_config_cmd_feature_toggle_failed', ['feature' => $feature]));
            return self::FAILURE;
        }
    }

    /**
     * Validate configuration.
     */
    private function validateConfiguration(): int
    {
        $errors = $this->configService->validateConfiguration();

        if (empty($errors)) {
            $this->info(__('messages.token_config_cmd_validation_success'));
            return self::SUCCESS;
        }

        $this->error(__('messages.token_config_cmd_validation_failed_title'));
        foreach ($errors as $error) {
            $this->line("  • {$error}");
        }

        return self::FAILURE;
    }

    /**
     * Clear configuration cache.
     */
    private function clearCache(): int
    {
        try {
            $this->configService->clearConfigCache();
            $this->info(__('messages.token_config_cmd_cache_cleared'));
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error(__('messages.token_config_cmd_cache_clear_failed', ['error' => $e->getMessage()]));
            return self::FAILURE;
        }
    }

    /**
     * Convert value to appropriate type based on key.
     */
    private function convertValue(string $key, mixed $value): mixed
    {
        // Boolean values
        if (str_starts_with($key, 'features.')) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        // Integer values
        if (str_starts_with($key, 'timing.') || str_starts_with($key, 'rate_limiting.')) {
            if (in_array($key, ['notifications.enabled', 'notifications.escalate_to_admin'])) {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
            return (int) $value;
        }

        // String values (default)
        return (string) $value;
    }
}