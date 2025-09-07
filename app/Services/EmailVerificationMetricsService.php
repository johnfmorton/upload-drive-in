<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmailVerificationMetricsService
{
    private const CACHE_PREFIX = 'email_verification_metrics';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Record an existing user bypass event
     */
    public function recordExistingUserBypass(User $user, array $restrictionsBypassed): void
    {
        $event = [
            'timestamp' => now()->toISOString(),
            'user_id' => $user->id,
            'user_role' => $user->role->value,
            'email_domain' => $this->extractDomain($user->email),
            'restrictions_bypassed' => $restrictionsBypassed,
            'event_type' => 'existing_user_bypass'
        ];

        $this->storeMetricEvent('bypass_events', $event);
        $this->incrementCounter('existing_user_bypasses_total');
        $this->incrementCounter("existing_user_bypasses_by_role.{$user->role->value}");

        Log::info('Email verification bypass event recorded', [
            'metric_event' => $event,
            'context' => 'email_verification_metrics'
        ]);
    }

    /**
     * Record a restriction enforcement event for new users
     */
    public function recordRestrictionEnforcement(string $email, string $restrictionType, array $context = []): void
    {
        $event = [
            'timestamp' => now()->toISOString(),
            'email' => $email,
            'email_domain' => $this->extractDomain($email),
            'restriction_type' => $restrictionType,
            'context' => $context,
            'event_type' => 'restriction_enforcement'
        ];

        $this->storeMetricEvent('restriction_events', $event);
        $this->incrementCounter('restriction_enforcements_total');
        $this->incrementCounter("restriction_enforcements_by_type.{$restrictionType}");

        Log::info('Email verification restriction enforcement recorded', [
            'metric_event' => $event,
            'context' => 'email_verification_metrics'
        ]);
    }

    /**
     * Get bypass pattern analysis
     */
    public function getBypassPatterns(int $hours = 24): array
    {
        $events = $this->getMetricEvents('bypass_events', $hours);
        
        return [
            'total_bypasses' => count($events),
            'bypasses_by_role' => $this->groupEventsByField($events, 'user_role'),
            'bypasses_by_domain' => $this->groupEventsByField($events, 'email_domain'),
            'bypasses_by_restriction_type' => $this->analyzeBypassedRestrictions($events),
            'hourly_distribution' => $this->getHourlyDistribution($events),
            'unusual_patterns' => $this->detectUnusualPatterns($events)
        ];
    }

    /**
     * Get restriction enforcement analysis
     */
    public function getRestrictionPatterns(int $hours = 24): array
    {
        $events = $this->getMetricEvents('restriction_events', $hours);
        
        return [
            'total_restrictions' => count($events),
            'restrictions_by_type' => $this->groupEventsByField($events, 'restriction_type'),
            'restrictions_by_domain' => $this->groupEventsByField($events, 'email_domain'),
            'hourly_distribution' => $this->getHourlyDistribution($events),
            'blocked_domains' => $this->getTopBlockedDomains($events)
        ];
    }

    /**
     * Get dashboard metrics summary
     */
    public function getDashboardMetrics(): array
    {
        $bypassPatterns = $this->getBypassPatterns(24);
        $restrictionPatterns = $this->getRestrictionPatterns(24);
        
        return [
            'last_24_hours' => [
                'existing_user_bypasses' => $bypassPatterns['total_bypasses'],
                'restriction_enforcements' => $restrictionPatterns['total_restrictions'],
                'bypass_to_restriction_ratio' => $this->calculateRatio(
                    $bypassPatterns['total_bypasses'],
                    $restrictionPatterns['total_restrictions']
                )
            ],
            'top_bypassed_restrictions' => $this->getTopBypassedRestrictions($bypassPatterns),
            'most_active_domains' => $this->getMostActiveDomains($bypassPatterns, $restrictionPatterns),
            'unusual_activity_alerts' => $this->getUnusualActivityAlerts()
        ];
    }

    /**
     * Check for unusual bypass patterns that might indicate issues
     */
    public function detectUnusualPatterns(array $events): array
    {
        $alerts = [];
        
        // Check for sudden spike in bypasses
        $recentEvents = array_filter($events, function ($event) {
            return Carbon::parse($event['timestamp'])->isAfter(now()->subHour());
        });
        
        if (count($recentEvents) > 10) {
            $alerts[] = [
                'type' => 'bypass_spike',
                'severity' => 'warning',
                'message' => __('messages.email_verification_bypass_spike_alert'),
                'count' => count($recentEvents),
                'threshold' => 10
            ];
        }

        // Check for repeated bypasses from same user
        $userCounts = $this->groupEventsByField($events, 'user_id');
        foreach ($userCounts as $userId => $count) {
            if ($count > 5) {
                $alerts[] = [
                    'type' => 'repeated_bypass',
                    'severity' => 'info',
                    'message' => __('messages.email_verification_repeated_bypass_alert', [
                        'user_id' => $userId,
                        'count' => $count
                    ]),
                    'user_id' => $userId,
                    'count' => $count
                ];
            }
        }

        // Check for bypasses from unusual domains
        $domainCounts = $this->groupEventsByField($events, 'email_domain');
        $suspiciousDomains = array_filter($domainCounts, function ($count) {
            return $count > 3;
        });
        
        foreach ($suspiciousDomains as $domain => $count) {
            if (!in_array($domain, $this->getKnownSafeDomains())) {
                $alerts[] = [
                    'type' => 'unusual_domain',
                    'severity' => 'info',
                    'message' => __('messages.email_verification_unusual_domain_alert', [
                        'domain' => $domain
                    ]),
                    'domain' => $domain,
                    'count' => $count
                ];
            }
        }

        return $alerts;
    }

    /**
     * Store a metric event in cache with timestamp-based key
     */
    private function storeMetricEvent(string $type, array $event): void
    {
        $key = $this->getCacheKey($type, now());
        $events = Cache::get($key, []);
        $events[] = $event;
        
        // Keep only last 1000 events per hour to prevent memory issues
        if (count($events) > 1000) {
            $events = array_slice($events, -1000);
        }
        
        Cache::put($key, $events, self::CACHE_TTL);
    }

    /**
     * Get metric events from cache for specified time range
     */
    private function getMetricEvents(string $type, int $hours): array
    {
        $events = [];
        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);
        
        // Always include the current hour
        $currentHourKey = $this->getCacheKey($type, $endTime);
        $currentHourEvents = Cache::get($currentHourKey, []);
        $events = array_merge($events, $currentHourEvents);
        
        // Get events from previous hours in the range
        for ($i = 1; $i <= $hours; $i++) {
            $time = $endTime->copy()->subHours($i);
            $key = $this->getCacheKey($type, $time);
            $hourEvents = Cache::get($key, []);
            $events = array_merge($events, $hourEvents);
        }
        
        // Filter events to exact time range
        return array_filter($events, function ($event) use ($startTime, $endTime) {
            $eventTime = Carbon::parse($event['timestamp']);
            return $eventTime->between($startTime, $endTime);
        });
    }

    /**
     * Increment a counter metric
     */
    private function incrementCounter(string $metric): void
    {
        $key = $this->getCacheKey('counters', now(), $metric);
        $currentValue = Cache::get($key, 0) + 1;
        Cache::put($key, $currentValue, self::CACHE_TTL);
    }

    /**
     * Generate cache key for metrics
     */
    private function getCacheKey(string $type, Carbon $time, string $suffix = ''): string
    {
        $timeKey = $time->format('Y-m-d-H');
        $key = self::CACHE_PREFIX . ":{$type}:{$timeKey}";
        
        if ($suffix) {
            $key .= ":{$suffix}";
        }
        
        return $key;
    }

    /**
     * Extract domain from email address
     */
    private function extractDomain(string $email): string
    {
        return strtolower(substr(strrchr($email, '@'), 1));
    }

    /**
     * Group events by a specific field
     */
    private function groupEventsByField(array $events, string $field): array
    {
        $groups = [];
        
        foreach ($events as $event) {
            $value = $event[$field] ?? 'unknown';
            $groups[$value] = ($groups[$value] ?? 0) + 1;
        }
        
        arsort($groups);
        return $groups;
    }

    /**
     * Analyze what restrictions were bypassed
     */
    private function analyzeBypassedRestrictions(array $events): array
    {
        $restrictions = [];
        
        foreach ($events as $event) {
            $bypassed = $event['restrictions_bypassed'] ?? [];
            foreach ($bypassed as $restriction) {
                $restrictions[$restriction] = ($restrictions[$restriction] ?? 0) + 1;
            }
        }
        
        arsort($restrictions);
        return $restrictions;
    }

    /**
     * Get hourly distribution of events
     */
    private function getHourlyDistribution(array $events): array
    {
        $distribution = [];
        
        foreach ($events as $event) {
            $hour = Carbon::parse($event['timestamp'])->format('H:00');
            $distribution[$hour] = ($distribution[$hour] ?? 0) + 1;
        }
        
        ksort($distribution);
        return $distribution;
    }

    /**
     * Get top blocked domains from restriction events
     */
    private function getTopBlockedDomains(array $events, int $limit = 10): array
    {
        $domains = $this->groupEventsByField($events, 'email_domain');
        return array_slice($domains, 0, $limit, true);
    }

    /**
     * Calculate ratio between two numbers
     */
    private function calculateRatio(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return $numerator > 0 ? PHP_FLOAT_MAX : 0;
        }
        
        return round($numerator / $denominator, 2);
    }

    /**
     * Get top bypassed restrictions
     */
    private function getTopBypassedRestrictions(array $bypassPatterns): array
    {
        return array_slice($bypassPatterns['bypasses_by_restriction_type'] ?? [], 0, 5, true);
    }

    /**
     * Get most active domains (combining bypasses and restrictions)
     */
    private function getMostActiveDomains(array $bypassPatterns, array $restrictionPatterns): array
    {
        $bypassDomains = $bypassPatterns['bypasses_by_domain'] ?? [];
        $restrictionDomains = $restrictionPatterns['restrictions_by_domain'] ?? [];
        
        $allDomains = [];
        
        foreach ($bypassDomains as $domain => $count) {
            $allDomains[$domain] = ($allDomains[$domain] ?? 0) + $count;
        }
        
        foreach ($restrictionDomains as $domain => $count) {
            $allDomains[$domain] = ($allDomains[$domain] ?? 0) + $count;
        }
        
        arsort($allDomains);
        return array_slice($allDomains, 0, 10, true);
    }

    /**
     * Get unusual activity alerts
     */
    private function getUnusualActivityAlerts(): array
    {
        $bypassEvents = $this->getMetricEvents('bypass_events', 24);
        return $this->detectUnusualPatterns($bypassEvents);
    }

    /**
     * Get known safe domains to reduce false positives
     */
    private function getKnownSafeDomains(): array
    {
        return [
            'gmail.com',
            'yahoo.com',
            'hotmail.com',
            'outlook.com',
            'icloud.com',
            'protonmail.com'
        ];
    }
}