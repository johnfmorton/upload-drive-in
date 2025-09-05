# Google Drive Token Auto-Renewal System - Monitoring and Alerting Guide

## Overview

This guide provides comprehensive instructions for setting up monitoring and alerting for the Google Drive Token Auto-Renewal System to ensure high availability and early detection of issues.

## Key Metrics to Monitor

### 1. Token Refresh Metrics

#### Success Rate
- **Metric**: Percentage of successful token refreshes
- **Target**: >95%
- **Alert Threshold**: <90%
- **Critical Threshold**: <80%

#### Refresh Latency
- **Metric**: Average time to complete token refresh
- **Target**: <2 seconds
- **Alert Threshold**: >5 seconds
- **Critical Threshold**: >10 seconds

#### Failure Count
- **Metric**: Number of failed refresh attempts per hour
- **Target**: <5 per hour
- **Alert Threshold**: >10 per hour
- **Critical Threshold**: >25 per hour

### 2. Health Status Metrics

#### Status Accuracy
- **Metric**: Percentage of accurate health status reports
- **Target**: >98%
- **Alert Threshold**: <95%
- **Critical Threshold**: <90%

#### Validation Latency
- **Metric**: Time to complete health validation
- **Target**: <1 second
- **Alert Threshold**: >3 seconds
- **Critical Threshold**: >5 seconds

#### Cache Hit Rate
- **Metric**: Percentage of health checks served from cache
- **Target**: >80%
- **Alert Threshold**: <70%
- **Critical Threshold**: <50%

### 3. Queue Performance Metrics

#### Queue Depth
- **Metric**: Number of pending jobs in token-related queues
- **Target**: <10 jobs
- **Alert Threshold**: >50 jobs
- **Critical Threshold**: >100 jobs

#### Job Processing Time
- **Metric**: Average time to process token maintenance jobs
- **Target**: <30 seconds
- **Alert Threshold**: >60 seconds
- **Critical Threshold**: >120 seconds

#### Failed Job Rate
- **Metric**: Percentage of failed token-related jobs
- **Target**: <2%
- **Alert Threshold**: >5%
- **Critical Threshold**: >10%

### 4. Notification Metrics

#### Delivery Rate
- **Metric**: Percentage of successfully delivered notifications
- **Target**: >98%
- **Alert Threshold**: <95%
- **Critical Threshold**: <90%

#### Throttling Effectiveness
- **Metric**: Percentage of duplicate notifications prevented
- **Target**: >95%
- **Alert Threshold**: <90%
- **Critical Threshold**: <80%

## Monitoring Dashboard Setup

### 1. Laravel Monitoring Dashboard

The system includes a built-in monitoring dashboard accessible at `/admin/token-monitoring`.

#### Dashboard Components

1. **Token Health Overview**
   - Total active tokens
   - Tokens expiring in next 24 hours
   - Failed refresh attempts in last hour
   - Average refresh success rate

2. **Performance Metrics**
   - Token refresh latency trends
   - Health validation performance
   - Cache hit rates
   - Queue processing times

3. **Error Analysis**
   - Recent failed refresh attempts
   - Error type distribution
   - User impact analysis
   - Recovery success rates

4. **System Health**
   - Queue worker status
   - Redis connectivity
   - Google API quota usage
   - Background job execution status

### 2. Custom Metrics Collection

#### Implementing Metrics Collection

1. **Create Metrics Service:**
   ```php
   // app/Services/TokenMetricsService.php
   class TokenMetricsService
   {
       public function recordRefreshAttempt(User $user, bool $success, float $duration): void
       {
           $key = "token_refresh_metrics:" . now()->format('Y-m-d-H');
           
           Redis::hincrby($key, 'total_attempts', 1);
           if ($success) {
               Redis::hincrby($key, 'successful_attempts', 1);
           }
           Redis::hincrbyfloat($key, 'total_duration', $duration);
           Redis::expire($key, 86400 * 7); // Keep for 7 days
       }
       
       public function getRefreshMetrics(int $hours = 24): array
       {
           $metrics = [];
           for ($i = 0; $i < $hours; $i++) {
               $key = "token_refresh_metrics:" . now()->subHours($i)->format('Y-m-d-H');
               $data = Redis::hgetall($key);
               if (!empty($data)) {
                   $metrics[] = [
                       'hour' => now()->subHours($i)->format('Y-m-d H:00'),
                       'total_attempts' => (int)($data['total_attempts'] ?? 0),
                       'successful_attempts' => (int)($data['successful_attempts'] ?? 0),
                       'success_rate' => $this->calculateSuccessRate($data),
                       'average_duration' => $this->calculateAverageDuration($data),
                   ];
               }
           }
           return $metrics;
       }
   }
   ```

2. **Integrate Metrics Collection:**
   ```php
   // In ProactiveTokenRenewalService
   public function refreshTokenIfNeeded(User $user, string $provider): RefreshResult
   {
       $start = microtime(true);
       
       try {
           $result = $this->performRefresh($user, $provider);
           
           $duration = microtime(true) - $start;
           app(TokenMetricsService::class)->recordRefreshAttempt($user, $result->isSuccessful(), $duration);
           
           return $result;
       } catch (Exception $e) {
           $duration = microtime(true) - $start;
           app(TokenMetricsService::class)->recordRefreshAttempt($user, false, $duration);
           throw $e;
       }
   }
   ```

### 3. External Monitoring Integration

#### Prometheus Metrics Export

1. **Install Prometheus PHP Client:**
   ```bash
   composer require promphp/prometheus_client_php
   ```

2. **Create Metrics Exporter:**
   ```php
   // app/Http/Controllers/MetricsController.php
   class MetricsController extends Controller
   {
       public function export()
       {
           $registry = CollectorRegistry::getDefault();
           
           // Token refresh success rate
           $refreshSuccessGauge = $registry->getOrRegisterGauge(
               'token_refresh_success_rate',
               'Token refresh success rate percentage'
           );
           
           $metrics = app(TokenMetricsService::class)->getRefreshMetrics(1);
           $successRate = $metrics[0]['success_rate'] ?? 0;
           $refreshSuccessGauge->set($successRate);
           
           // Health validation latency
           $healthLatencyHistogram = $registry->getOrRegisterHistogram(
               'health_validation_duration_seconds',
               'Health validation duration in seconds',
               [],
               [0.1, 0.5, 1.0, 2.0, 5.0, 10.0]
           );
           
           $renderer = new RenderTextFormat();
           return response($renderer->render($registry->getMetricFamilySamples()))
               ->header('Content-Type', RenderTextFormat::MIME_TYPE);
       }
   }
   ```

3. **Add Route:**
   ```php
   // routes/web.php
   Route::get('/metrics', [MetricsController::class, 'export'])
       ->middleware('auth:admin');
   ```

## Alerting Configuration

### 1. Laravel-Based Alerting

#### Alert Service Implementation

```php
// app/Services/TokenAlertService.php
class TokenAlertService
{
    public function checkRefreshSuccessRate(): void
    {
        $metrics = app(TokenMetricsService::class)->getRefreshMetrics(1);
        $successRate = $metrics[0]['success_rate'] ?? 100;
        
        if ($successRate < 80) {
            $this->sendCriticalAlert('Token Refresh Success Rate Critical', [
                'success_rate' => $successRate,
                'threshold' => 80,
                'severity' => 'critical'
            ]);
        } elseif ($successRate < 90) {
            $this->sendWarningAlert('Token Refresh Success Rate Low', [
                'success_rate' => $successRate,
                'threshold' => 90,
                'severity' => 'warning'
            ]);
        }
    }
    
    public function checkQueueDepth(): void
    {
        $queueDepth = Redis::llen('queues:high') + Redis::llen('queues:maintenance');
        
        if ($queueDepth > 100) {
            $this->sendCriticalAlert('Queue Depth Critical', [
                'queue_depth' => $queueDepth,
                'threshold' => 100,
                'severity' => 'critical'
            ]);
        } elseif ($queueDepth > 50) {
            $this->sendWarningAlert('Queue Depth High', [
                'queue_depth' => $queueDepth,
                'threshold' => 50,
                'severity' => 'warning'
            ]);
        }
    }
    
    private function sendCriticalAlert(string $title, array $data): void
    {
        // Send to multiple channels for critical alerts
        Mail::to(config('app.admin_email'))->send(new CriticalAlertMail($title, $data));
        
        // Send to Slack if configured
        if (config('services.slack.webhook')) {
            Http::post(config('services.slack.webhook'), [
                'text' => "🚨 CRITICAL: {$title}",
                'attachments' => [
                    [
                        'color' => 'danger',
                        'fields' => collect($data)->map(fn($value, $key) => [
                            'title' => ucwords(str_replace('_', ' ', $key)),
                            'value' => $value,
                            'short' => true
                        ])->values()->toArray()
                    ]
                ]
            ]);
        }
    }
}
```

#### Scheduled Alert Checks

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Check metrics every 5 minutes
    $schedule->call(function () {
        $alertService = app(TokenAlertService::class);
        $alertService->checkRefreshSuccessRate();
        $alertService->checkQueueDepth();
        $alertService->checkHealthValidationLatency();
        $alertService->checkNotificationDeliveryRate();
    })->everyFiveMinutes();
    
    // Comprehensive health check every hour
    $schedule->call(function () {
        app(TokenAlertService::class)->performComprehensiveHealthCheck();
    })->hourly();
}
```

### 2. External Alerting Systems

#### Grafana Alerting Rules

```yaml
# grafana-alerts.yml
groups:
  - name: token_refresh_alerts
    rules:
      - alert: TokenRefreshSuccessRateLow
        expr: token_refresh_success_rate < 90
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Token refresh success rate is below 90%"
          description: "Token refresh success rate has been {{ $value }}% for more than 5 minutes"
      
      - alert: TokenRefreshSuccessRateCritical
        expr: token_refresh_success_rate < 80
        for: 2m
        labels:
          severity: critical
        annotations:
          summary: "Token refresh success rate is critically low"
          description: "Token refresh success rate has been {{ $value }}% for more than 2 minutes"
      
      - alert: HealthValidationLatencyHigh
        expr: health_validation_duration_seconds > 5
        for: 3m
        labels:
          severity: warning
        annotations:
          summary: "Health validation latency is high"
          description: "Health validation is taking {{ $value }} seconds"
      
      - alert: QueueDepthHigh
        expr: queue_depth > 50
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Queue depth is high"
          description: "Queue has {{ $value }} pending jobs"
```

#### PagerDuty Integration

```php
// app/Services/PagerDutyAlertService.php
class PagerDutyAlertService
{
    public function sendAlert(string $severity, string $summary, array $details): void
    {
        $payload = [
            'routing_key' => config('services.pagerduty.integration_key'),
            'event_action' => 'trigger',
            'payload' => [
                'summary' => $summary,
                'severity' => $severity,
                'source' => config('app.name'),
                'component' => 'token-refresh-system',
                'custom_details' => $details
            ]
        ];
        
        Http::post('https://events.pagerduty.com/v2/enqueue', $payload);
    }
}
```

## Log Monitoring

### 1. Structured Logging Setup

#### Log Configuration

```php
// config/logging.php
'channels' => [
    'token_refresh' => [
        'driver' => 'daily',
        'path' => storage_path('logs/token-refresh.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],
    
    'health_validation' => [
        'driver' => 'daily',
        'path' => storage_path('logs/health-validation.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
    ],
],
```

#### Structured Log Messages

```php
// In ProactiveTokenRenewalService
Log::channel('token_refresh')->info('Token refresh initiated', [
    'user_id' => $user->id,
    'provider' => $provider,
    'token_expires_at' => $token->expires_at,
    'proactive' => true,
    'operation_id' => $operationId,
    'timestamp' => now()->toISOString()
]);

Log::channel('token_refresh')->info('Token refresh completed', [
    'user_id' => $user->id,
    'provider' => $provider,
    'success' => true,
    'new_expires_at' => $newToken->expires_at,
    'duration_ms' => $duration,
    'operation_id' => $operationId,
    'timestamp' => now()->toISOString()
]);
```

### 2. Log Analysis and Alerting

#### ELK Stack Integration

1. **Filebeat Configuration:**
   ```yaml
   # filebeat.yml
   filebeat.inputs:
   - type: log
     enabled: true
     paths:
       - /var/www/storage/logs/token-refresh.log
       - /var/www/storage/logs/health-validation.log
     json.keys_under_root: true
     json.add_error_key: true
     fields:
       service: token-refresh-system
       environment: production
   
   output.elasticsearch:
     hosts: ["elasticsearch:9200"]
   
   setup.kibana:
     host: "kibana:5601"
   ```

2. **Elasticsearch Index Template:**
   ```json
   {
     "index_patterns": ["token-refresh-*"],
     "mappings": {
       "properties": {
         "timestamp": { "type": "date" },
         "user_id": { "type": "integer" },
         "provider": { "type": "keyword" },
         "success": { "type": "boolean" },
         "duration_ms": { "type": "float" },
         "operation_id": { "type": "keyword" },
         "error_type": { "type": "keyword" }
       }
     }
   }
   ```

3. **Kibana Dashboards:**
   - Token refresh success rate over time
   - Error distribution by type
   - Performance metrics (latency, throughput)
   - User impact analysis

#### Log-Based Alerting

```bash
# Logstash alerting configuration
filter {
  if [fields][service] == "token-refresh-system" {
    if [success] == false {
      mutate {
        add_tag => ["token_refresh_failure"]
      }
    }
    
    if [duration_ms] > 5000 {
      mutate {
        add_tag => ["slow_token_refresh"]
      }
    }
  }
}

output {
  if "token_refresh_failure" in [tags] {
    http {
      url => "https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"
      http_method => "post"
      format => "json"
      mapping => {
        "text" => "Token refresh failed for user %{user_id}: %{error_message}"
      }
    }
  }
}
```

## Performance Monitoring

### 1. Application Performance Monitoring (APM)

#### New Relic Integration

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if (extension_loaded('newrelic')) {
        // Custom metrics for token operations
        $this->app->bind('newrelic.token_refresh', function () {
            return function (User $user, bool $success, float $duration) {
                newrelic_record_metric('Custom/TokenRefresh/Duration', $duration);
                newrelic_record_metric('Custom/TokenRefresh/Success', $success ? 1 : 0);
                
                if (!$success) {
                    newrelic_notice_error('Token refresh failed', [
                        'user_id' => $user->id,
                        'duration' => $duration
                    ]);
                }
            };
        });
    }
}
```

#### Custom Performance Tracking

```php
// app/Services/PerformanceTracker.php
class PerformanceTracker
{
    public function trackTokenRefresh(callable $operation, User $user): mixed
    {
        $start = microtime(true);
        $memoryStart = memory_get_usage(true);
        
        try {
            $result = $operation();
            
            $this->recordMetrics('token_refresh_success', [
                'duration' => microtime(true) - $start,
                'memory_usage' => memory_get_usage(true) - $memoryStart,
                'user_id' => $user->id
            ]);
            
            return $result;
        } catch (Exception $e) {
            $this->recordMetrics('token_refresh_failure', [
                'duration' => microtime(true) - $start,
                'memory_usage' => memory_get_usage(true) - $memoryStart,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

### 2. Database Performance Monitoring

#### Query Performance Tracking

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if (app()->environment('production')) {
        DB::listen(function ($query) {
            if ($query->time > 1000) { // Queries taking more than 1 second
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time
                ]);
            }
        });
    }
}
```

#### Index Usage Monitoring

```sql
-- Monitor index usage for token-related tables
SELECT 
    table_name,
    index_name,
    cardinality,
    sub_part,
    packed,
    null_allowed,
    index_type
FROM information_schema.statistics 
WHERE table_schema = 'your_database' 
AND table_name IN ('google_drive_tokens', 'cloud_storage_health_statuses')
ORDER BY table_name, seq_in_index;
```

## Health Checks

### 1. Application Health Endpoints

```php
// app/Http/Controllers/HealthController.php
class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'token_refresh' => $this->checkTokenRefreshService(),
            'google_api' => $this->checkGoogleApiConnectivity()
        ];
        
        $healthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');
        
        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toISOString()
        ], $healthy ? 200 : 503);
    }
    
    private function checkTokenRefreshService(): array
    {
        try {
            $service = app(ProactiveTokenRenewalService::class);
            // Perform a lightweight check
            return ['status' => 'ok', 'message' => 'Service available'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
```

### 2. External Health Monitoring

#### Uptime Monitoring

Configure external services (Pingdom, UptimeRobot, etc.) to monitor:
- Application health endpoint: `/health`
- Token monitoring dashboard: `/admin/token-monitoring`
- Metrics endpoint: `/metrics`

#### Synthetic Monitoring

```php
// tests/Monitoring/SyntheticTest.php
class SyntheticTest extends TestCase
{
    public function test_token_refresh_end_to_end()
    {
        // Create test user with expiring token
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->expiring()->create(['user_id' => $user->id]);
        
        // Trigger refresh
        $service = app(ProactiveTokenRenewalService::class);
        $result = $service->refreshTokenIfNeeded($user, 'google-drive');
        
        // Verify success
        $this->assertTrue($result->isSuccessful());
        
        // Record metrics for monitoring
        app(TokenMetricsService::class)->recordSyntheticTest('token_refresh', $result->isSuccessful());
    }
}
```

## Alerting Runbooks

### 1. Token Refresh Failure Runbook

**Alert**: Token refresh success rate below 90%

**Immediate Actions**:
1. Check Google API status: https://status.cloud.google.com/
2. Verify queue workers are running: `ps aux | grep queue:work`
3. Check Redis connectivity: `redis-cli ping`
4. Review recent error logs: `tail -100 storage/logs/token-refresh.log`

**Investigation Steps**:
1. Identify affected users: `php artisan analyze:token-refresh-logs --failed-only`
2. Check error patterns: Group errors by type and frequency
3. Verify Google API quotas: Check API console for quota usage
4. Test manual refresh: `php artisan tinker` → test refresh for sample user

**Resolution Actions**:
- If API quota exceeded: Wait for quota reset or request increase
- If network issues: Check connectivity and DNS resolution
- If invalid tokens: Force user re-authentication
- If service issues: Restart queue workers and clear cache

### 2. Queue Depth Alert Runbook

**Alert**: Queue depth above 50 jobs

**Immediate Actions**:
1. Check queue worker status: `php artisan queue:monitor`
2. Identify job types: `php artisan queue:failed`
3. Check system resources: CPU, memory, disk space
4. Review worker logs for errors

**Investigation Steps**:
1. Analyze job failure patterns
2. Check for stuck or long-running jobs
3. Verify database connectivity
4. Review Google API rate limits

**Resolution Actions**:
- Scale up queue workers if needed
- Clear failed jobs if appropriate: `php artisan queue:retry all`
- Restart workers if stuck: `php artisan queue:restart`
- Optimize job processing if performance issues identified

## Maintenance Procedures

### 1. Regular Maintenance Tasks

#### Daily Tasks
- Review token refresh success rates
- Check queue performance metrics
- Verify notification delivery
- Monitor error logs for new patterns

#### Weekly Tasks
- Analyze performance trends
- Review and clean up old logs
- Update alerting thresholds if needed
- Test backup and recovery procedures

#### Monthly Tasks
- Review Google API quota usage
- Analyze user behavior patterns
- Update monitoring dashboards
- Conduct security review of token handling

### 2. Capacity Planning

#### Metrics to Track
- Token refresh volume trends
- Queue processing capacity
- Database query performance
- Cache hit rates and memory usage

#### Scaling Indicators
- Queue depth consistently >25% of capacity
- Token refresh latency >3 seconds average
- Cache hit rate <70%
- Database query time >500ms average

## Troubleshooting Integration

### 1. Automated Diagnostics

```php
// app/Console/Commands/DiagnoseTokenIssues.php
class DiagnoseTokenIssues extends Command
{
    public function handle()
    {
        $this->info('Running token system diagnostics...');
        
        // Check service availability
        $this->checkServices();
        
        // Analyze recent failures
        $this->analyzeFailures();
        
        // Test sample operations
        $this->runSampleTests();
        
        // Generate recommendations
        $this->generateRecommendations();
    }
}
```

### 2. Self-Healing Mechanisms

```php
// app/Services/SelfHealingService.php
class SelfHealingService
{
    public function attemptAutoRecovery(): void
    {
        // Clear problematic cache entries
        if ($this->detectCacheIssues()) {
            $this->clearHealthStatusCache();
        }
        
        // Restart stuck queue workers
        if ($this->detectStuckWorkers()) {
            $this->restartQueueWorkers();
        }
        
        // Reset rate limits if appropriate
        if ($this->detectRateLimitIssues()) {
            $this->resetRateLimits();
        }
    }
}
```

This comprehensive monitoring and alerting setup ensures early detection of issues, provides detailed visibility into system performance, and enables rapid response to problems in the Google Drive Token Auto-Renewal System.