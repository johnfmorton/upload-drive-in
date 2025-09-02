<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request as HttpRequest;
use Carbon\Carbon;

/**
 * Service for comprehensive audit logging of cloud storage configuration changes
 * and administrative actions. Provides detailed audit trails for compliance and security.
 */
class CloudStorageAuditService
{
    /**
     * Log provider configuration change.
     */
    public function logProviderConfigurationChange(
        User $user,
        string $provider,
        string $action,
        array $oldConfig = [],
        array $newConfig = [],
        ?string $reason = null,
        array $context = []
    ): void {
        $auditData = [
            'event_type' => 'provider_configuration_change',
            'action' => $action, // 'create', 'update', 'delete', 'enable', 'disable'
            'provider' => $provider,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role?->value ?? 'unknown',
            'timestamp' => now()->toISOString(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'reason' => $reason,
            'context' => $context,
        ];

        // Add configuration diff for update actions
        if ($action === 'update' && !empty($oldConfig) && !empty($newConfig)) {
            $auditData['configuration_changes'] = $this->calculateConfigurationDiff($oldConfig, $newConfig);
        } elseif ($action === 'create' && !empty($newConfig)) {
            $auditData['new_configuration'] = $this->sanitizeConfigurationForLogging($newConfig);
        } elseif ($action === 'delete' && !empty($oldConfig)) {
            $auditData['deleted_configuration'] = $this->sanitizeConfigurationForLogging($oldConfig);
        }

        Log::channel('audit')->info("Provider configuration {$action}: {$provider}", $auditData);

        // Also log to cloud-storage channel for operational visibility
        Log::channel('cloud-storage')->info("Configuration {$action} for {$provider} by {$user->email}", [
            'provider' => $provider,
            'action' => $action,
            'user_id' => $user->id,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log provider authentication event.
     */
    public function logProviderAuthenticationEvent(
        User $user,
        string $provider,
        string $event,
        bool $success,
        ?string $error = null,
        array $context = []
    ): void {
        $auditData = [
            'event_type' => 'provider_authentication',
            'event' => $event, // 'oauth_start', 'oauth_callback', 'token_refresh', 'disconnect'
            'provider' => $provider,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'success' => $success,
            'timestamp' => now()->toISOString(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'context' => $context,
        ];

        if ($error) {
            $auditData['error'] = $error;
        }

        $level = $success ? 'info' : 'warning';
        $message = $success 
            ? "Provider authentication {$event} successful: {$provider}"
            : "Provider authentication {$event} failed: {$provider}";

        Log::channel('audit')->{$level}($message, $auditData);
    }

    /**
     * Log administrative action on cloud storage system.
     */
    public function logAdministrativeAction(
        User $user,
        string $action,
        string $resource,
        ?string $resourceId = null,
        array $details = [],
        ?string $reason = null
    ): void {
        $auditData = [
            'event_type' => 'administrative_action',
            'action' => $action, // 'view', 'create', 'update', 'delete', 'enable', 'disable', 'test'
            'resource' => $resource, // 'provider', 'user_settings', 'health_check', 'configuration'
            'resource_id' => $resourceId,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role?->value ?? 'unknown',
            'timestamp' => now()->toISOString(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'reason' => $reason,
            'details' => $details,
        ];

        Log::channel('audit')->info("Administrative action: {$action} on {$resource}", $auditData);
    }

    /**
     * Log security-related event.
     */
    public function logSecurityEvent(
        ?User $user,
        string $event,
        string $severity,
        string $description,
        array $context = []
    ): void {
        $auditData = [
            'event_type' => 'security_event',
            'event' => $event,
            'severity' => $severity, // 'low', 'medium', 'high', 'critical'
            'description' => $description,
            'timestamp' => now()->toISOString(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'context' => $context,
        ];

        if ($user) {
            $auditData['user_id'] = $user->id;
            $auditData['user_email'] = $user->email;
            $auditData['user_role'] = $user->role?->value ?? 'unknown';
        }

        // Log to both audit and security channels
        Log::channel('audit')->warning("Security event: {$event}", $auditData);
        Log::channel('security')->warning("Security event: {$event}", $auditData);
    }

    /**
     * Log provider health status change.
     */
    public function logProviderHealthStatusChange(
        User $user,
        string $provider,
        string $previousStatus,
        string $newStatus,
        ?string $reason = null,
        array $context = []
    ): void {
        $auditData = [
            'event_type' => 'provider_health_status_change',
            'provider' => $provider,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        Log::channel('audit')->info("Provider health status changed: {$provider} ({$previousStatus} -> {$newStatus})", $auditData);
    }

    /**
     * Log bulk operation on files or configurations.
     */
    public function logBulkOperation(
        User $user,
        string $operation,
        string $resource,
        int $affectedCount,
        array $criteria = [],
        ?string $reason = null
    ): void {
        $auditData = [
            'event_type' => 'bulk_operation',
            'operation' => $operation, // 'delete', 'update', 'migrate', 'cleanup'
            'resource' => $resource, // 'files', 'configurations', 'tokens', 'health_records'
            'affected_count' => $affectedCount,
            'criteria' => $criteria,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role?->value ?? 'unknown',
            'timestamp' => now()->toISOString(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'reason' => $reason,
        ];

        Log::channel('audit')->info("Bulk operation: {$operation} on {$affectedCount} {$resource}", $auditData);
    }

    /**
     * Log system configuration change.
     */
    public function logSystemConfigurationChange(
        User $user,
        string $configKey,
        $oldValue,
        $newValue,
        ?string $reason = null
    ): void {
        $auditData = [
            'event_type' => 'system_configuration_change',
            'config_key' => $configKey,
            'old_value' => $this->sanitizeValueForLogging($oldValue),
            'new_value' => $this->sanitizeValueForLogging($newValue),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role?->value ?? 'unknown',
            'timestamp' => now()->toISOString(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'reason' => $reason,
        ];

        Log::channel('audit')->info("System configuration changed: {$configKey}", $auditData);
    }

    /**
     * Log data access event for compliance.
     */
    public function logDataAccess(
        User $user,
        string $dataType,
        string $action,
        ?string $resourceId = null,
        array $context = []
    ): void {
        $auditData = [
            'event_type' => 'data_access',
            'data_type' => $dataType, // 'user_files', 'configuration', 'tokens', 'health_data'
            'action' => $action, // 'view', 'download', 'export', 'search'
            'resource_id' => $resourceId,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role?->value ?? 'unknown',
            'timestamp' => now()->toISOString(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'context' => $context,
        ];

        Log::channel('audit')->info("Data access: {$action} on {$dataType}", $auditData);
    }

    /**
     * Log provider migration event.
     */
    public function logProviderMigration(
        User $user,
        string $fromProvider,
        string $toProvider,
        int $filesCount,
        bool $success,
        ?string $error = null,
        array $context = []
    ): void {
        $auditData = [
            'event_type' => 'provider_migration',
            'from_provider' => $fromProvider,
            'to_provider' => $toProvider,
            'files_count' => $filesCount,
            'success' => $success,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'timestamp' => now()->toISOString(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'context' => $context,
        ];

        if ($error) {
            $auditData['error'] = $error;
        }

        $level = $success ? 'info' : 'error';
        $message = $success 
            ? "Provider migration successful: {$fromProvider} -> {$toProvider} ({$filesCount} files)"
            : "Provider migration failed: {$fromProvider} -> {$toProvider}";

        Log::channel('audit')->{$level}($message, $auditData);
    }

    /**
     * Get audit trail for a specific resource.
     */
    public function getAuditTrail(
        string $resourceType,
        ?string $resourceId = null,
        ?User $user = null,
        int $days = 30
    ): array {
        // This would typically query a dedicated audit log table
        // For now, we'll return a placeholder structure
        return [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'user_id' => $user?->id,
            'days' => $days,
            'events' => [], // Would contain actual audit events from database
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate compliance report for audit purposes.
     */
    public function generateComplianceReport(
        Carbon $startDate,
        Carbon $endDate,
        ?string $provider = null,
        ?User $user = null
    ): array {
        return [
            'report_type' => 'compliance_audit',
            'period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'provider' => $provider,
            'user_id' => $user?->id,
            'summary' => [
                'total_events' => 0,
                'configuration_changes' => 0,
                'authentication_events' => 0,
                'administrative_actions' => 0,
                'security_events' => 0,
                'data_access_events' => 0,
            ],
            'events' => [], // Would contain filtered audit events
            'generated_at' => now()->toISOString(),
            'generated_by' => auth()->user()?->id,
        ];
    }

    /**
     * Calculate configuration differences for audit logging.
     */
    private function calculateConfigurationDiff(array $oldConfig, array $newConfig): array
    {
        $changes = [];
        
        // Find added keys
        foreach ($newConfig as $key => $value) {
            if (!array_key_exists($key, $oldConfig)) {
                $changes['added'][$key] = $this->sanitizeValueForLogging($value);
            } elseif ($oldConfig[$key] !== $value) {
                $changes['modified'][$key] = [
                    'old' => $this->sanitizeValueForLogging($oldConfig[$key]),
                    'new' => $this->sanitizeValueForLogging($value),
                ];
            }
        }
        
        // Find removed keys
        foreach ($oldConfig as $key => $value) {
            if (!array_key_exists($key, $newConfig)) {
                $changes['removed'][$key] = $this->sanitizeValueForLogging($value);
            }
        }
        
        return $changes;
    }

    /**
     * Sanitize configuration data for logging (remove sensitive information).
     */
    private function sanitizeConfigurationForLogging(array $config): array
    {
        $sanitized = [];
        $sensitiveKeys = [
            'client_secret',
            'secret_access_key',
            'private_key',
            'password',
            'token',
            'refresh_token',
            'access_token',
            'connection_string',
        ];

        foreach ($config as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeConfigurationForLogging($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize individual values for logging.
     */
    private function sanitizeValueForLogging($value)
    {
        if (is_array($value)) {
            return $this->sanitizeConfigurationForLogging($value);
        }

        if (is_string($value)) {
            // Check if it looks like a sensitive value (long random string, etc.)
            if (strlen($value) > 50 && preg_match('/^[a-zA-Z0-9+\/=]+$/', $value)) {
                return '[REDACTED]';
            }
        }

        return $value;
    }
}