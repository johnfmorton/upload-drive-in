<?php

namespace App\Http\Middleware;

use App\Services\DiskSpaceMonitorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class CheckDiskSpace
{
    protected DiskSpaceMonitorService $diskSpaceMonitor;

    public function __construct(DiskSpaceMonitorService $diskSpaceMonitor)
    {
        $this->diskSpaceMonitor = $diskSpaceMonitor;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        // Only check for file upload requests
        if (!$request->hasFile('file') && !$request->has('dztotalfilesize')) {
            return $next($request);
        }

        // Get file size from request
        $fileSize = 0;
        if ($request->hasFile('file')) {
            $fileSize = $request->file('file')->getSize();
        } elseif ($request->has('dztotalfilesize')) {
            $fileSize = (int) $request->input('dztotalfilesize');
        }

        // Check if there's enough disk space
        if (!$this->diskSpaceMonitor->hasEnoughSpaceForUpload($fileSize)) {
            $freeSpace = $this->diskSpaceMonitor->getFreeSpaceFormatted();
            $requiredSpace = format_bytes($fileSize);

            return response()->json([
                'error' => 'Insufficient disk space',
                'message' => "Not enough disk space available. Required: {$requiredSpace}, Available: {$freeSpace}",
                'free_space' => $freeSpace,
                'required_space' => $requiredSpace,
            ], Response::HTTP_INSUFFICIENT_STORAGE);
        }

        // Perform emergency cleanup if in warning zone
        if ($this->diskSpaceMonitor->isInWarningZone()) {
            $this->diskSpaceMonitor->emergencyCleanup();
        }

        return $next($request);
    }
}