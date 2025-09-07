<?php

namespace App\Services;

use App\Mail\AdminVerificationMail;
use App\Mail\ClientVerificationMail;
use App\Mail\EmployeeVerificationMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Factory service for creating role-based email verification mail instances.
 * 
 * This service handles the logic for determining which verification email template
 * to use based on user roles or context strings, with proper fallback behavior
 * and debugging support.
 */
class VerificationMailFactory
{
    /**
     * Create a verification mail instance for a specific user.
     * 
     * This method analyzes the user's role and returns the appropriate
     * verification mail class. If no user is provided or role detection fails,
     * it falls back to the client verification template.
     *
     * @param User|null $user The user to create verification mail for
     * @param string $verificationUrl The verification URL to include in the email
     * @return Mailable The appropriate verification mail instance
     */
    public function createForUser(?User $user, string $verificationUrl): Mailable
    {
        $startTime = microtime(true);
        $userRole = null;
        $mailClass = null;
        $roleDetectionFailed = false;

        try {
            if ($user) {
                if ($user->isAdmin()) {
                    $userRole = 'admin';
                    $mailClass = AdminVerificationMail::class;
                } elseif ($user->isEmployee()) {
                    $userRole = 'employee';
                    $mailClass = EmployeeVerificationMail::class;
                } elseif ($user->isClient()) {
                    $userRole = 'client';
                    $mailClass = ClientVerificationMail::class;
                } else {
                    // User exists but no role detected
                    $roleDetectionFailed = true;
                    Log::warning('Role detection failed for user', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'user_role_column' => $user->role ?? 'null',
                        'method' => 'createForUser',
                        'fallback_used' => true
                    ]);
                }
            }

            // Fallback to client verification for unknown roles or null user
            if (!$mailClass) {
                $userRole = 'client';
                $mailClass = ClientVerificationMail::class;
            }

            // Log structured template selection
            $this->logTemplateSelection([
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'detected_role' => $userRole,
                'mail_class' => $mailClass,
                'verification_url' => $verificationUrl,
                'method' => 'createForUser',
                'role_detection_failed' => $roleDetectionFailed,
                'fallback_used' => !$user || $roleDetectionFailed,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            // Update metrics
            $this->updateMetrics($userRole, 'template_selected');

            return new $mailClass($verificationUrl, $userRole);

        } catch (\Exception $e) {
            // Log role detection errors
            Log::error('Email verification template creation failed', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'method' => 'createForUser',
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            $this->updateMetrics('unknown', 'template_creation_error');

            // Fallback to client verification even on error
            return new ClientVerificationMail($verificationUrl, 'client');
        }
    }

    /**
     * Create a verification mail instance for a specific context string.
     * 
     * This method allows for explicit role selection using string contexts,
     * useful when the user object is not available but the role context is known.
     *
     * @param string $context The role context ('admin', 'employee', 'client')
     * @param string $verificationUrl The verification URL to include in the email
     * @return Mailable The appropriate verification mail instance
     */
    public function createForContext(string $context, string $verificationUrl): Mailable
    {
        $startTime = microtime(true);
        $originalContext = $context;
        $fallbackUsed = false;

        try {
            $mailClass = match (strtolower($context)) {
                'admin' => AdminVerificationMail::class,
                'employee' => EmployeeVerificationMail::class,
                'client' => ClientVerificationMail::class,
                default => ClientVerificationMail::class
            };

            // Check if fallback was used
            if (!in_array(strtolower($context), ['admin', 'employee', 'client'])) {
                $fallbackUsed = true;
                Log::warning('Unknown context provided, using fallback', [
                    'requested_context' => $originalContext,
                    'fallback_role' => 'client',
                    'method' => 'createForContext'
                ]);
            }

            // Normalize context for logging
            $normalizedContext = strtolower($context);
            $selectedRole = match ($mailClass) {
                AdminVerificationMail::class => 'admin',
                EmployeeVerificationMail::class => 'employee',
                default => 'client'
            };

            // Log structured template selection
            $this->logTemplateSelection([
                'requested_context' => $originalContext,
                'normalized_context' => $normalizedContext,
                'selected_role' => $selectedRole,
                'mail_class' => $mailClass,
                'verification_url' => $verificationUrl,
                'method' => 'createForContext',
                'fallback_used' => $fallbackUsed,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            // Update metrics
            $this->updateMetrics($selectedRole, 'template_selected');

            return new $mailClass($verificationUrl, $selectedRole);

        } catch (\Exception $e) {
            // Log context processing errors
            Log::error('Email verification template creation failed for context', [
                'requested_context' => $originalContext,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'method' => 'createForContext',
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            $this->updateMetrics('unknown', 'template_creation_error');

            // Fallback to client verification even on error
            return new ClientVerificationMail($verificationUrl, 'client');
        }
    }

    /**
     * Get the available verification contexts.
     * 
     * This method returns an array of valid context strings that can be used
     * with the createForContext method.
     *
     * @return array<string> Array of valid context strings
     */
    public function getAvailableContexts(): array
    {
        return ['admin', 'employee', 'client'];
    }

    /**
     * Determine the appropriate context for a user.
     * 
     * This is a helper method that returns the string context for a given user,
     * useful for debugging or when you need the context string rather than the mail instance.
     *
     * @param User|null $user The user to determine context for
     * @return string The context string ('admin', 'employee', or 'client')
     */
    public function determineContextForUser(?User $user): string
    {
        if (!$user) {
            return 'client';
        }

        if ($user->isAdmin()) {
            return 'admin';
        }

        if ($user->isEmployee()) {
            return 'employee';
        }

        return 'client';
    }

    /**
     * Log structured template selection information.
     * 
     * This method provides consistent logging format for template selection
     * across all factory methods.
     *
     * @param array $data The data to log
     * @return void
     */
    private function logTemplateSelection(array $data): void
    {
        Log::info('Email verification template selected', array_merge([
            'service' => 'VerificationMailFactory',
            'timestamp' => now()->toISOString(),
        ], $data));
    }

    /**
     * Update metrics for email verification template usage.
     * 
     * This method tracks metrics for monitoring email verification success rates
     * and template usage patterns by role.
     *
     * @param string $role The role for which the metric is being updated
     * @param string $event The event type (template_selected, email_sent, verification_success, etc.)
     * @return void
     */
    private function updateMetrics(string $role, string $event): void
    {
        try {
            $cacheKey = "email_verification_metrics:{$role}:{$event}";
            $dailyCacheKey = "email_verification_metrics_daily:{$role}:{$event}:" . now()->format('Y-m-d');
            
            // Increment total counter
            Cache::increment($cacheKey, 1);
            
            // Increment daily counter (expires after 7 days)
            Cache::increment($dailyCacheKey, 1);
            Cache::put($dailyCacheKey, Cache::get($dailyCacheKey, 0), now()->addDays(7));
            
            // Log metrics update for debugging
            Log::debug('Email verification metrics updated', [
                'role' => $role,
                'event' => $event,
                'cache_key' => $cacheKey,
                'daily_cache_key' => $dailyCacheKey,
                'service' => 'VerificationMailFactory'
            ]);
            
        } catch (\Exception $e) {
            // Don't let metrics failures break the main functionality
            Log::warning('Failed to update email verification metrics', [
                'role' => $role,
                'event' => $event,
                'error' => $e->getMessage(),
                'service' => 'VerificationMailFactory'
            ]);
        }
    }

    /**
     * Get email verification metrics for monitoring.
     * 
     * This method retrieves cached metrics for email verification template usage
     * and success rates by role.
     *
     * @param string|null $role Optional role filter
     * @param string|null $event Optional event filter
     * @param bool $dailyOnly Whether to return only daily metrics
     * @return array The metrics data
     */
    public function getMetrics(?string $role = null, ?string $event = null, bool $dailyOnly = false): array
    {
        $roles = $role ? [$role] : ['admin', 'employee', 'client', 'unknown'];
        $events = $event ? [$event] : [
            'template_selected', 
            'template_creation_error', 
            'email_sent', 
            'email_send_error',
            'verification_success', 
            'verification_failure'
        ];
        
        $metrics = [];
        $datePrefix = $dailyOnly ? now()->format('Y-m-d') : null;
        
        foreach ($roles as $roleKey) {
            $metrics[$roleKey] = [];
            
            foreach ($events as $eventKey) {
                if ($dailyOnly) {
                    $cacheKey = "email_verification_metrics_daily:{$roleKey}:{$eventKey}:{$datePrefix}";
                } else {
                    $cacheKey = "email_verification_metrics:{$roleKey}:{$eventKey}";
                }
                
                $metrics[$roleKey][$eventKey] = Cache::get($cacheKey, 0);
            }
        }
        
        return $metrics;
    }

    /**
     * Log email verification success for metrics tracking.
     * 
     * This method should be called when an email verification is successfully completed
     * to track success rates by role.
     *
     * @param string $role The role of the user who completed verification
     * @param string|null $userEmail The email address (for logging, not stored in metrics)
     * @return void
     */
    public function logVerificationSuccess(string $role, ?string $userEmail = null): void
    {
        $this->updateMetrics($role, 'verification_success');
        
        Log::info('Email verification completed successfully', [
            'role' => $role,
            'user_email' => $userEmail,
            'service' => 'VerificationMailFactory',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log email verification failure for metrics tracking.
     * 
     * This method should be called when an email verification fails
     * to track failure rates by role.
     *
     * @param string $role The role of the user whose verification failed
     * @param string $reason The reason for failure
     * @param string|null $userEmail The email address (for logging, not stored in metrics)
     * @return void
     */
    public function logVerificationFailure(string $role, string $reason, ?string $userEmail = null): void
    {
        $this->updateMetrics($role, 'verification_failure');
        
        Log::warning('Email verification failed', [
            'role' => $role,
            'reason' => $reason,
            'user_email' => $userEmail,
            'service' => 'VerificationMailFactory',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log email sending success for metrics tracking.
     * 
     * This method should be called when a verification email is successfully sent
     * to track email delivery success rates by role.
     *
     * @param string $role The role for which the email was sent
     * @param string|null $userEmail The email address (for logging, not stored in metrics)
     * @return void
     */
    public function logEmailSent(string $role, ?string $userEmail = null): void
    {
        $this->updateMetrics($role, 'email_sent');
        
        Log::info('Email verification email sent successfully', [
            'role' => $role,
            'user_email' => $userEmail,
            'service' => 'VerificationMailFactory',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log email sending failure for metrics tracking.
     * 
     * This method should be called when a verification email fails to send
     * to track email delivery failure rates by role.
     *
     * @param string $role The role for which the email failed to send
     * @param string $reason The reason for failure
     * @param string|null $userEmail The email address (for logging, not stored in metrics)
     * @return void
     */
    public function logEmailSendError(string $role, string $reason, ?string $userEmail = null): void
    {
        $this->updateMetrics($role, 'email_send_error');
        
        Log::error('Email verification email failed to send', [
            'role' => $role,
            'reason' => $reason,
            'user_email' => $userEmail,
            'service' => 'VerificationMailFactory',
            'timestamp' => now()->toISOString()
        ]);
    }
}