<?php

namespace App\Console\Commands;

use App\Services\DiskSpaceMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorDiskSpace extends Command
{
    protected $signature = 'uploads:monitor-disk-space {--cleanup : Perform cleanup if needed}';
    protected $description = 'Monitor disk space and optionally perform cleanup';

    protected DiskSpaceMonitorService $diskSpaceMonitor;

    public function __construct(DiskSpaceMonitorService $diskSpaceMonitor)
    {
        parent::__construct();
        $this->diskSpaceMonitor = $diskSpaceMonitor;
    }

    public function handle(): int
    {
        $stats = $this->diskSpaceMonitor->getDiskUsageStats();
        $uploadDirSize = $this->diskSpaceMonitor->getUploadDirectorySize();

        $this->info('=== Disk Space Report ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Space', $stats['total_space_formatted']],
                ['Used Space', $stats['used_space_formatted']],
                ['Free Space', $stats['free_space_formatted']],
                ['Usage Percentage', $stats['usage_percentage'] . '%'],
                ['Upload Directory Size', format_bytes($uploadDirSize)],
            ]
        );

        // Check status
        if ($this->diskSpaceMonitor->isCriticallyLow()) {
            $this->error('⚠️  CRITICAL: Disk space is critically low!');
            
            if ($this->option('cleanup')) {
                $this->warn('Performing emergency cleanup...');
                $deletedCount = $this->diskSpaceMonitor->emergencyCleanup();
                $this->info("Emergency cleanup completed. Deleted {$deletedCount} files.");
            } else {
                $this->warn('Run with --cleanup flag to perform emergency cleanup.');
            }
            
            return 1;
        } elseif ($this->diskSpaceMonitor->isInWarningZone()) {
            $this->warn('⚠️  WARNING: Disk space is in warning zone.');
            
            if ($this->option('cleanup')) {
                $this->info('Performing preventive cleanup...');
                $this->call('uploads:clear-old', ['--hours' => 12]);
            }
            
            return 0;
        } else {
            $this->info('✅ Disk space is healthy.');
            return 0;
        }
    }
}