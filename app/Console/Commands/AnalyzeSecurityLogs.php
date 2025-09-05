<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class AnalyzeSecurityLogs extends Command
{
    protected $signature = 'security:analyze-logs 
                           {--days=7 : Number of days to analyze}
                           {--type= : Filter by event type}
                           {--user= : Filter by user ID}
                           {--ip= : Filter by IP address}';

    protected $description = 'Analyze security logs for token refresh patterns and anomalies';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $eventType = $this->option('type');
        $userId = $this->option('user');
        $ipAddress = $this->option('ip');

        $this->info("Analyzing security logs for the last {$days} days...");

        $logFiles = $this->getSecurityLogFiles($days);
        
        if (empty($logFiles)) {
            $this->warn('No security log files found for the specified period.');
            return 0;
        }

        $events = $this->parseLogFiles($logFiles, $eventType, $userId, $ipAddress);
        
        if (empty($events)) {
            $this->warn('No matching security events found.');
            return 0;
        }

        $this->displayAnalysis($events);
        
        return 0;
    }

    private function getSecurityLogFiles(int $days): array
    {
        $logPath = storage_path('logs');
        $files = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $filename = "security-{$date}.log";
            $filepath = "{$logPath}/{$filename}";
            
            if (File::exists($filepath)) {
                $files[] = $filepath;
            }
        }
        
        return $files;
    }

    private function parseLogFiles(array $files, ?string $eventType, ?string $userId, ?string $ipAddress): array
    {
        $events = [];
        
        foreach ($files as $file) {
            $content = File::get($file);
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                if (empty(trim($line))) {
                    continue;
                }
                
                $event = $this->parseLogLine($line);
                if (!$event) {
                    continue;
                }
                
                // Apply filters
                if ($eventType && $event['event'] !== $eventType) {
                    continue;
                }
                
                if ($userId && $event['user_id'] != $userId) {
                    continue;
                }
                
                if ($ipAddress && $event['ip_address'] !== $ipAddress) {
                    continue;
                }
                
                $events[] = $event;
            }
        }
        
        return $events;
    }

    private function parseLogLine(string $line): ?array
    {
        // Parse Laravel log format: [timestamp] environment.level: message context
        if (!preg_match('/\[(.*?)\] .*?: Token Security Event (.*)/', $line, $matches)) {
            return null;
        }
        
        $timestamp = $matches[1];
        $jsonData = $matches[2];
        
        try {
            $data = json_decode($jsonData, true);
            if (!$data || !isset($data['event'])) {
                return null;
            }
            
            return [
                'timestamp' => Carbon::parse($timestamp),
                'event' => $data['event'],
                'user_id' => $data['data']['user_id'] ?? null,
                'ip_address' => $data['data']['ip_address'] ?? null,
                'data' => $data['data'] ?? [],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function displayAnalysis(array $events): void
    {
        $this->info("\n=== Security Log Analysis ===");
        $this->info("Total events: " . count($events));
        
        // Event type breakdown
        $eventTypes = [];
        $userEvents = [];
        $ipEvents = [];
        $hourlyDistribution = [];
        
        foreach ($events as $event) {
            // Count by event type
            $eventTypes[$event['event']] = ($eventTypes[$event['event']] ?? 0) + 1;
            
            // Count by user
            if ($event['user_id']) {
                $userEvents[$event['user_id']] = ($userEvents[$event['user_id']] ?? 0) + 1;
            }
            
            // Count by IP
            if ($event['ip_address']) {
                $ipEvents[$event['ip_address']] = ($ipEvents[$event['ip_address']] ?? 0) + 1;
            }
            
            // Hourly distribution
            $hour = $event['timestamp']->format('H');
            $hourlyDistribution[$hour] = ($hourlyDistribution[$hour] ?? 0) + 1;
        }
        
        // Display event types
        $this->info("\n--- Event Types ---");
        arsort($eventTypes);
        foreach ($eventTypes as $type => $count) {
            $this->line("  {$type}: {$count}");
        }
        
        // Display top users
        $this->info("\n--- Top Users by Event Count ---");
        arsort($userEvents);
        $topUsers = array_slice($userEvents, 0, 10, true);
        foreach ($topUsers as $userId => $count) {
            $this->line("  User {$userId}: {$count} events");
        }
        
        // Display top IPs
        $this->info("\n--- Top IP Addresses by Event Count ---");
        arsort($ipEvents);
        $topIps = array_slice($ipEvents, 0, 10, true);
        foreach ($topIps as $ip => $count) {
            $this->line("  {$ip}: {$count} events");
        }
        
        // Display hourly distribution
        $this->info("\n--- Hourly Distribution ---");
        ksort($hourlyDistribution);
        foreach ($hourlyDistribution as $hour => $count) {
            $bar = str_repeat('█', min(50, $count));
            $this->line("  {$hour}:00 {$bar} ({$count})");
        }
        
        // Security alerts
        $this->displaySecurityAlerts($events, $userEvents, $ipEvents);
    }

    private function displaySecurityAlerts(array $events, array $userEvents, array $ipEvents): void
    {
        $this->info("\n--- Security Alerts ---");
        
        $alerts = [];
        
        // Check for users with excessive failures
        foreach ($userEvents as $userId => $count) {
            if ($count > 20) {
                $alerts[] = "⚠️  User {$userId} has {$count} security events (potential abuse)";
            }
        }
        
        // Check for IPs with excessive requests
        foreach ($ipEvents as $ip => $count) {
            if ($count > 50) {
                $alerts[] = "🚨 IP {$ip} has {$count} security events (potential attack)";
            }
        }
        
        // Check for rate limit violations
        $rateLimitEvents = array_filter($events, fn($e) => 
            $e['event'] === 'rate_limit_exceeded' || $e['event'] === 'ip_rate_limit_exceeded'
        );
        
        if (count($rateLimitEvents) > 10) {
            $alerts[] = "⚠️  " . count($rateLimitEvents) . " rate limit violations detected";
        }
        
        // Check for authentication failures
        $authFailures = array_filter($events, fn($e) => 
            $e['event'] === 'token_refresh_failure'
        );
        
        if (count($authFailures) > count($events) * 0.5) {
            $alerts[] = "🚨 High authentication failure rate: " . count($authFailures) . "/" . count($events) . " events";
        }
        
        if (empty($alerts)) {
            $this->info("  ✅ No security alerts detected");
        } else {
            foreach ($alerts as $alert) {
                $this->warn("  {$alert}");
            }
        }
    }
}