<?php

namespace App\Services;

use App\Models\DomainAccessRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DomainRulesCacheService
{
    private const CACHE_KEY = 'domain_access_rules';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get domain rules with caching
     *
     * @return DomainAccessRule|null
     */
    public function getDomainRules(): ?DomainAccessRule
    {
        $startTime = microtime(true);
        
        try {
            $rules = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                $queryStartTime = microtime(true);
                $rules = DomainAccessRule::first();
                $queryTime = (microtime(true) - $queryStartTime) * 1000;
                
                Log::debug('Domain rules database query executed', [
                    'query_time_ms' => round($queryTime, 2),
                    'rules_found' => (bool)$rules,
                    'cache_miss' => true,
                    'context' => 'domain_rules_cache_service'
                ]);
                
                return $rules;
            });
            
            $totalTime = (microtime(true) - $startTime) * 1000;
            
            // Log cache performance
            $cacheHit = Cache::has(self::CACHE_KEY);
            Log::debug('Domain rules retrieved', [
                'total_time_ms' => round($totalTime, 2),
                'cache_hit' => $cacheHit,
                'rules_found' => (bool)$rules,
                'context' => 'domain_rules_cache_service'
            ]);
            
            return $rules;
            
        } catch (\Exception $e) {
            Log::error(__('messages.domain_rules_cache_failed'), [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'fallback_action' => 'return_null',
                'context' => 'domain_rules_cache_service'
            ]);
            
            return null;
        }
    }

    /**
     * Clear domain rules cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        
        Log::info(__('messages.domain_rules_cache_cleared'), [
            'cache_key' => self::CACHE_KEY,
            'context' => 'domain_rules_cache_service'
        ]);
    }

    /**
     * Warm up the cache by loading domain rules
     *
     * @return void
     */
    public function warmCache(): void
    {
        $startTime = microtime(true);
        
        $this->getDomainRules();
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        Log::info(__('messages.domain_rules_cache_warmed'), [
            'warm_up_time_ms' => round($totalTime, 2),
            'context' => 'domain_rules_cache_service'
        ]);
    }

    /**
     * Check if an email is allowed using cached domain rules
     *
     * @param string $email
     * @return bool
     */
    public function isEmailAllowed(string $email): bool
    {
        $startTime = microtime(true);
        
        $domainRules = $this->getDomainRules();
        
        if (!$domainRules) {
            // No rules configured - allow by default
            Log::debug(__('messages.domain_rules_not_configured'), [
                'email' => $email,
                'default_action' => 'allow',
                'context' => 'domain_rules_cache_service'
            ]);
            return true;
        }
        
        $isAllowed = $domainRules->isEmailAllowed($email);
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        Log::debug(__('messages.domain_rules_email_check_completed'), [
            'email' => $email,
            'is_allowed' => $isAllowed,
            'check_time_ms' => round($totalTime, 2),
            'rules_mode' => $domainRules->mode,
            'context' => 'domain_rules_cache_service'
        ]);
        
        return $isAllowed;
    }

    /**
     * Check if public registration is allowed using cached domain rules
     *
     * @return bool
     */
    public function isPublicRegistrationAllowed(): bool
    {
        $domainRules = $this->getDomainRules();
        
        if (!$domainRules) {
            // No rules configured - allow by default
            return true;
        }
        
        return $domainRules->allow_public_registration;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        $cacheHit = Cache::has(self::CACHE_KEY);
        $rules = $this->getDomainRules();
        
        return [
            'cache_key' => self::CACHE_KEY,
            'cache_ttl' => self::CACHE_TTL,
            'cache_hit' => $cacheHit,
            'rules_loaded' => (bool)$rules,
            'rules_config' => $rules ? [
                'mode' => $rules->mode,
                'allow_public_registration' => $rules->allow_public_registration,
                'rules_count' => count($rules->rules ?? [])
            ] : null
        ];
    }

    /**
     * Get user-friendly cache statistics for admin interfaces
     *
     * @return array
     */
    public function getUserFriendlyCacheStats(): array
    {
        $stats = $this->getCacheStats();
        
        return [
            __('messages.cache_key') => $stats['cache_key'],
            __('messages.cache_ttl') => $stats['cache_ttl'] . ' ' . __('messages.domain_rules_cache_command_seconds'),
            __('messages.cache_hit') => $stats['cache_hit'] ? __('messages.domain_rules_cache_command_yes') : __('messages.domain_rules_cache_command_no'),
            __('messages.rules_loaded') => $stats['rules_loaded'] ? __('messages.domain_rules_cache_command_yes') : __('messages.domain_rules_cache_command_no'),
            __('messages.rules_mode') => $stats['rules_config']['mode'] ?? 'N/A',
            __('messages.rules_count') => $stats['rules_config']['rules_count'] ?? 0,
        ];
    }
}