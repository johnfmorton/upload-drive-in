<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

/**
 * Console command for filtering and analyzing cloud storage logs
 */
class CloudStorageLogFilter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-storage:logs 
                            {--provider= : Filter by provider (google-drive, etc.)}
                            {--operation= : Filter by operation (upload, delete, etc.)}
                            {--user= : Filter by user ID or email}
                            {--error-type= : Filter by error type}
                            {--level= : Filter by log level (info, warning, error)}
                            {--since= : Show logs since date (Y-m-d H:i:s or relative like "1 hour ago")}
                            {--until= : Show logs until date (Y-m-d H:i:s or relative like "now")}
                            {--tail=50 : Number of recent lines to show}
                            {--follow : Follow log file (like tail -f)}
                            {--stats : Show statistics summary}
                            {--export= : Export filtered logs to file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Filter and analyze cloud storage logs with various criteria';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logPath = storage_path('logs/cloud-storage.log');
        $currentLogPath = $this->getCurrentLogPath();

        if (!File::exists($currentLogPath)) {
            $this->error("Cloud storage log file not found: {$currentLogPath}");
            return 1;
        }

        if ($this->option('follow')) {
            return $this->followLogs($currentLogPath);
        }

        if ($this->option('stats')) {
            return $this->showStatistics($currentLogPath);
        }

        return $this->filterLogs($currentLogPath);
    }

    /**
     * Get the current log file path (handles daily rotation)
     */
    private function getCurrentLogPath(): string
    {
        $baseLogPath = storage_path('logs/cloud-storage');
        $todayLog = $baseLogPath . '-' . now()->format('Y-m-d') . '.log';
        
        if (File::exists($todayLog)) {
            return $todayLog;
        }
        
        // Fallback to base log file
        return $baseLogPath . '.log';
    }

    /**
     * Filter and display logs based on criteria
     */
    private function filterLogs(string $logPath): int
    {
        $lines = $this->readLogLines($logPath);
        $filteredLines = $this->applyFilters($lines);
        
        if ($this->option('export')) {
            return $this->exportLogs($filteredLines, $this->option('export'));
        }

        $tail = (int) $this->option('tail');
        if ($tail > 0 && count($filteredLines) > $tail) {
            $filteredLines = array_slice($filteredLines, -$tail);
        }

        foreach ($filteredLines as $line) {
            $this->line($this->formatLogLine($line));
        }

        $this->info(sprintf('Showing %d log entries', count($filteredLines)));
        return 0;
    }

    /**
     * Follow logs in real-time
     */
    private function followLogs(string $logPath): int
    {
        $this->info("Following cloud storage logs... (Press Ctrl+C to stop)");
        
        $handle = fopen($logPath, 'r');
        if (!$handle) {
            $this->error("Cannot open log file for reading");
            return 1;
        }

        // Seek to end of file
        fseek($handle, 0, SEEK_END);

        while (true) {
            $line = fgets($handle);
            if ($line !== false) {
                $line = trim($line);
                if (!empty($line) && $this->matchesFilters($line)) {
                    $this->line($this->formatLogLine($line));
                }
            } else {
                usleep(100000); // Sleep for 0.1 seconds
            }
        }

        fclose($handle);
        return 0;
    }

    /**
     * Show log statistics
     */
    private function showStatistics(string $logPath): int
    {
        $lines = $this->readLogLines($logPath);
        $stats = $this->calculateStatistics($lines);

        $this->info('Cloud Storage Log Statistics');
        $this->line('================================');
        
        $this->table(['Metric', 'Count'], [
            ['Total Log Entries', $stats['total']],
            ['Error Entries', $stats['errors']],
            ['Warning Entries', $stats['warnings']],
            ['Info Entries', $stats['info']],
            ['Upload Operations', $stats['uploads']],
            ['Delete Operations', $stats['deletes']],
            ['OAuth Events', $stats['oauth']],
            ['Health Status Changes', $stats['health_changes']],
        ]);

        if (!empty($stats['error_types'])) {
            $this->line('');
            $this->info('Error Types:');
            $errorTypeTable = [];
            foreach ($stats['error_types'] as $type => $count) {
                $errorTypeTable[] = [$type, $count];
            }
            $this->table(['Error Type', 'Count'], $errorTypeTable);
        }

        if (!empty($stats['providers'])) {
            $this->line('');
            $this->info('Providers:');
            $providerTable = [];
            foreach ($stats['providers'] as $provider => $count) {
                $providerTable[] = [$provider, $count];
            }
            $this->table(['Provider', 'Count'], $providerTable);
        }

        return 0;
    }

    /**
     * Read log lines from file
     */
    private function readLogLines(string $logPath): array
    {
        if (!File::exists($logPath)) {
            return [];
        }

        $content = File::get($logPath);
        return array_filter(explode("\n", $content), fn($line) => !empty(trim($line)));
    }

    /**
     * Apply filters to log lines
     */
    private function applyFilters(array $lines): array
    {
        return array_filter($lines, [$this, 'matchesFilters']);
    }

    /**
     * Check if a log line matches the current filters
     */
    private function matchesFilters(string $line): bool
    {
        // Parse log line to extract structured data
        $logData = $this->parseLogLine($line);
        
        if (!$logData) {
            return false;
        }

        // Apply provider filter
        if ($provider = $this->option('provider')) {
            if (!isset($logData['provider']) || $logData['provider'] !== $provider) {
                return false;
            }
        }

        // Apply operation filter
        if ($operation = $this->option('operation')) {
            if (!isset($logData['operation']) || $logData['operation'] !== $operation) {
                return false;
            }
        }

        // Apply user filter
        if ($user = $this->option('user')) {
            $userMatch = false;
            if (isset($logData['user_id']) && $logData['user_id'] == $user) {
                $userMatch = true;
            }
            if (isset($logData['user_email']) && stripos($logData['user_email'], $user) !== false) {
                $userMatch = true;
            }
            if (!$userMatch) {
                return false;
            }
        }

        // Apply error type filter
        if ($errorType = $this->option('error-type')) {
            if (!isset($logData['error_type']) || $logData['error_type'] !== $errorType) {
                return false;
            }
        }

        // Apply level filter
        if ($level = $this->option('level')) {
            if (!isset($logData['level']) || strtolower($logData['level']) !== strtolower($level)) {
                return false;
            }
        }

        // Apply date filters
        if ($since = $this->option('since')) {
            $sinceDate = $this->parseDate($since);
            if ($sinceDate && isset($logData['timestamp'])) {
                $logDate = Carbon::parse($logData['timestamp']);
                if ($logDate->lt($sinceDate)) {
                    return false;
                }
            }
        }

        if ($until = $this->option('until')) {
            $untilDate = $this->parseDate($until);
            if ($untilDate && isset($logData['timestamp'])) {
                $logDate = Carbon::parse($logData['timestamp']);
                if ($logDate->gt($untilDate)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Parse a log line to extract structured data
     */
    private function parseLogLine(string $line): ?array
    {
        // Laravel log format: [timestamp] environment.level: message context
        if (!preg_match('/^\[([^\]]+)\]\s+\w+\.(\w+):\s+(.+)$/', $line, $matches)) {
            return null;
        }

        $timestamp = $matches[1];
        $level = $matches[2];
        $messageAndContext = $matches[3];

        $data = [
            'timestamp' => $timestamp,
            'level' => $level,
            'raw_line' => $line,
        ];

        // Try to extract JSON context
        if (preg_match('/^(.+?)\s+(\{.+\})$/', $messageAndContext, $contextMatches)) {
            $message = $contextMatches[1];
            $jsonContext = $contextMatches[2];
            
            $context = json_decode($jsonContext, true);
            if ($context) {
                $data = array_merge($data, $context);
            }
            
            $data['message'] = $message;
        } else {
            $data['message'] = $messageAndContext;
        }

        return $data;
    }

    /**
     * Parse date string (supports relative dates)
     */
    private function parseDate(string $dateString): ?Carbon
    {
        try {
            // Try parsing as absolute date first
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            // Try parsing as relative date
            try {
                return Carbon::parse($dateString);
            } catch (\Exception $e) {
                $this->warn("Could not parse date: {$dateString}");
                return null;
            }
        }
    }

    /**
     * Format log line for display
     */
    private function formatLogLine(string $line): string
    {
        $logData = $this->parseLogLine($line);
        
        if (!$logData) {
            return $line;
        }

        $level = strtoupper($logData['level'] ?? 'INFO');
        $timestamp = $logData['timestamp'] ?? '';
        $message = $logData['message'] ?? '';
        
        // Color code by level
        $levelColor = match (strtolower($level)) {
            'error' => 'red',
            'warning' => 'yellow',
            'info' => 'green',
            'debug' => 'blue',
            default => 'white',
        };

        $formattedLine = sprintf(
            '<fg=%s>[%s]</> <fg=cyan>%s</> %s',
            $levelColor,
            $level,
            $timestamp,
            $message
        );

        // Add key context information
        $contextParts = [];
        if (isset($logData['provider'])) {
            $contextParts[] = "provider:{$logData['provider']}";
        }
        if (isset($logData['operation'])) {
            $contextParts[] = "op:{$logData['operation']}";
        }
        if (isset($logData['user_email'])) {
            $contextParts[] = "user:{$logData['user_email']}";
        }
        if (isset($logData['error_type'])) {
            $contextParts[] = "error:{$logData['error_type']}";
        }

        if (!empty($contextParts)) {
            $formattedLine .= ' <fg=gray>(' . implode(', ', $contextParts) . ')</>';
        }

        return $formattedLine;
    }

    /**
     * Calculate statistics from log lines
     */
    private function calculateStatistics(array $lines): array
    {
        $stats = [
            'total' => count($lines),
            'errors' => 0,
            'warnings' => 0,
            'info' => 0,
            'uploads' => 0,
            'deletes' => 0,
            'oauth' => 0,
            'health_changes' => 0,
            'error_types' => [],
            'providers' => [],
        ];

        foreach ($lines as $line) {
            $logData = $this->parseLogLine($line);
            if (!$logData) {
                continue;
            }

            // Count by level
            $level = strtolower($logData['level'] ?? 'info');
            if (isset($stats[$level])) {
                $stats[$level]++;
            }

            // Count by operation
            if (isset($logData['operation'])) {
                $operation = $logData['operation'];
                if (str_contains($operation, 'upload')) {
                    $stats['uploads']++;
                } elseif (str_contains($operation, 'delete')) {
                    $stats['deletes']++;
                } elseif (str_contains($operation, 'oauth')) {
                    $stats['oauth']++;
                }
            }

            // Count health status changes
            if (isset($logData['event_type']) && $logData['event_type'] === 'health_status_change') {
                $stats['health_changes']++;
            }

            // Count error types
            if (isset($logData['error_type'])) {
                $errorType = $logData['error_type'];
                $stats['error_types'][$errorType] = ($stats['error_types'][$errorType] ?? 0) + 1;
            }

            // Count providers
            if (isset($logData['provider'])) {
                $provider = $logData['provider'];
                $stats['providers'][$provider] = ($stats['providers'][$provider] ?? 0) + 1;
            }
        }

        return $stats;
    }

    /**
     * Export filtered logs to file
     */
    private function exportLogs(array $lines, string $exportPath): int
    {
        try {
            $content = implode("\n", $lines);
            File::put($exportPath, $content);
            $this->info("Exported " . count($lines) . " log entries to: {$exportPath}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to export logs: " . $e->getMessage());
            return 1;
        }
    }
}