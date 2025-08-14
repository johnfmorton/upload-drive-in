<?php

namespace App\Http\Controllers;

use App\Services\DiskSpaceMonitorService;
use App\Services\SetupService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    protected DiskSpaceMonitorService $diskSpaceMonitor;
    protected SetupService $setupService;

    public function __construct(DiskSpaceMonitorService $diskSpaceMonitor, SetupService $setupService)
    {
        $this->diskSpaceMonitor = $diskSpaceMonitor;
        $this->setupService = $setupService;
    }

    /**
     * Basic health check endpoint
     */
    public function check(): JsonResponse
    {
        $setupRequired = $this->setupService->isSetupRequired();
        $status = $setupRequired ? 'setup_required' : 'healthy';
        
        return response()->json([
            'status' => $status,
            'setup_required' => $setupRequired,
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
        
        // Check setup status
        $setupRequired = $this->setupService->isSetupRequired();
        $setupProgress = $this->setupService->getSetupProgress();
        $setupStep = $setupRequired ? $this->setupService->getSetupStep() : null;

        $status = 'healthy';
        if ($setupRequired) {
            $status = 'setup_required';
        } elseif ($this->diskSpaceMonitor->isCriticallyLow()) {
            $status = 'critical';
        } elseif ($this->diskSpaceMonitor->isInWarningZone()) {
            $status = 'warning';
        }

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'setup' => [
                'required' => $setupRequired,
                'progress' => $setupProgress,
                'current_step' => $setupStep,
                'completed' => $this->setupService->isSetupComplete(),
            ],
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