<?php

namespace App\Observers;

use App\Models\DomainAccessRule;
use App\Services\DomainRulesCacheService;
use Illuminate\Support\Facades\Log;

class DomainAccessRuleObserver
{
    /**
     * Handle the DomainAccessRule "created" event.
     */
    public function created(DomainAccessRule $domainAccessRule): void
    {
        $this->clearCache('created', $domainAccessRule);
    }

    /**
     * Handle the DomainAccessRule "updated" event.
     */
    public function updated(DomainAccessRule $domainAccessRule): void
    {
        $this->clearCache('updated', $domainAccessRule);
    }

    /**
     * Handle the DomainAccessRule "deleted" event.
     */
    public function deleted(DomainAccessRule $domainAccessRule): void
    {
        $this->clearCache('deleted', $domainAccessRule);
    }

    /**
     * Handle the DomainAccessRule "restored" event.
     */
    public function restored(DomainAccessRule $domainAccessRule): void
    {
        $this->clearCache('restored', $domainAccessRule);
    }

    /**
     * Handle the DomainAccessRule "force deleted" event.
     */
    public function forceDeleted(DomainAccessRule $domainAccessRule): void
    {
        $this->clearCache('force_deleted', $domainAccessRule);
    }

    /**
     * Clear domain rules cache and log the action
     *
     * @param string $action
     * @param DomainAccessRule $domainAccessRule
     * @return void
     */
    private function clearCache(string $action, DomainAccessRule $domainAccessRule): void
    {
        try {
            $cacheService = app(DomainRulesCacheService::class);
            $cacheService->clearCache();
            
            Log::info('Domain rules cache cleared due to model change', [
                'action' => $action,
                'rule_id' => $domainAccessRule->id,
                'new_config' => [
                    'mode' => $domainAccessRule->mode,
                    'allow_public_registration' => $domainAccessRule->allow_public_registration,
                    'rules_count' => count($domainAccessRule->rules ?? [])
                ],
                'context' => 'domain_access_rule_observer'
            ]);
            
            // Warm up the cache with new data
            $cacheService->warmCache();
            
        } catch (\Exception $e) {
            Log::error('Failed to clear domain rules cache', [
                'action' => $action,
                'rule_id' => $domainAccessRule->id,
                'error' => $e->getMessage(),
                'context' => 'domain_access_rule_observer'
            ]);
        }
    }
}