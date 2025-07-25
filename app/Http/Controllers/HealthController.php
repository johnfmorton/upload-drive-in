<?php

namespace App\Http\Controllers;

use App\Services\DiskSpaceMonitorService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    protected DiskSpaceMonitorService $diskSpaceMonitor;

    public function __construct(DiskSpaceMonitorService $diskSpaceMonitor)
    {
        $this->diskSpaceMonitor = $diskSpaceMonitor;
    }

    /**
     * Basic health check endpoint
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Detailed health check with disk space information
     */
    public function detailed(): JsonResponse
    {
        $diskStats = $this->diskSpaceMonitor->getDiskUsageStats();
        $uploadDirSize = $this->diskSpaceMonitor->getUploadDirectorySize();

        $status = 'healthy';
        if ($this->diskSpaceMonitor->isCriticallyLow()) {
            $status = 'critical';
        } elseif ($this->diskSpaceMonitor->isInWarningZone()) {
            $status = 'warning';
        }

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'disk_space' => [
                'total' => $diskStats['total_space_formatted'],
                'free' => $diskStats['free_space_formatted'],
                'used' => $diskStats['used_space_formatted'],
                'usage_percentage' => $diskStats['usage_percentage'],
                'upload_directory_size' => format_bytes($uploadDirSize),
                'is_warning' => $this->diskSpaceMonitor->isInWarningZone(),
                'is_critical' => $this->diskSpaceMonitor->isCriticallyLow(),
            ],
        ]);
    }
}