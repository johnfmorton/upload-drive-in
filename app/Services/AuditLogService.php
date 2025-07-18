<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * Service for audit logging of file access operations.
 * Provides comprehensive logging for security and compliance.
 */
class AuditLogService
{
    /**
     * Log file access operation.
     */
    public function logFileAccess(
        string $action,
        FileUpload $file,
        User $user,
        Request $request,
        array $additionalData = []
    ): void {
        $logData = [
            'action' => $action,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role->value,
            'file_id' => $file->id,
            'file_name' => $file->original_filename,
            'file_size' => $file->file_size,
            'file_type' => $file->mime_type,
            'file_owner_email' => $file->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
        ];

        // Add additional context data
        if (!empty($additionalData)) {
            $logData['additional_data'] = $additionalData;
        }

        // Log to dedicated audit channel
        Log::channel('audit')->info("File {$action}", $logData);

        // Also log to main log for immediate visibility
        Log::info("Audit: File {$action}", [
            'user' => $user->email,
            'file' => $file->original_filename,
            'action' => $action,
            'ip' => $request->ip()
        ]);
    }

    /**
     * Log bulk file operation.
     */
    public function logBulkFileOperation(
        string $action,
        array $fileIds,
        User $user,
        Request $request,
        array $results = []
    ): void {
        $logData = [
            'action' => "bulk_{$action}",
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role->value,
            'file_count' => count($fileIds),
            'file_ids' => $fileIds,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
        ];

        // Add operation results if provided
        if (!empty($results)) {
            $logData['results'] = $results;
        }

        // Log to dedicated audit channel
        Log::channel('audit')->info("Bulk file {$action}", $logData);

        // Also log to main log for immediate visibility
        Log::info("Audit: Bulk file {$action}", [
            'user' => $user->email,
            'file_count' => count($fileIds),
            'action' => $action,
            'ip' => $request->ip()
        ]);
    }

    /**
     * Log security violation or suspicious activity.
     */
    public function logSecurityViolation(
        string $violation,
        User $user,
        Request $request,
        array $context = []
    ): void {
        $logData = [
            'violation_type' => $violation,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role->value,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
            'context' => $context,
        ];

        // Log to security channel with high priority
        Log::channel('security')->warning("Security violation: {$violation}", $logData);

        // Also log to main log for immediate visibility
        Log::warning("Security: {$violation}", [
            'user' => $user->email,
            'ip' => $request->ip(),
            'violation' => $violation
        ]);
    }

    /**
     * Log failed access attempt.
     */
    public function logAccessDenied(
        string $resource,
        User $user,
        Request $request,
        string $reason = ''
    ): void {
        $logData = [
            'event' => 'access_denied',
            'resource' => $resource,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role->value,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
        ];

        // Log to security channel
        Log::channel('security')->info("Access denied: {$resource}", $logData);

        // Also log to main log
        Log::info("Access denied: {$resource}", [
            'user' => $user->email,
            'resource' => $resource,
            'reason' => $reason,
            'ip' => $request->ip()
        ]);
    }
}