<?php

namespace App\Exceptions;

use App\Models\FileUpload;
use App\Models\User;

class FileAccessException extends FileManagerException
{
    /**
     * Create an exception for unauthorized file access.
     */
    public static function unauthorized(FileUpload $file, ?User $user = null): self
    {
        $userId = $user ? $user->id : 'guest';
        $userRole = $user ? $user->role : 'guest';
        
        return new self(
            message: "Unauthorized access to file {$file->id} by user {$userId} with role {$userRole}",
            userMessage: "You do not have permission to access this file.",
            code: 403,
            context: [
                'file_id' => $file->id,
                'user_id' => $userId,
                'user_role' => $userRole,
                'file_owner' => $file->client_user_id,
                'type' => 'unauthorized_file_access'
            ]
        );
    }

    /**
     * Create an exception for bulk operation with unauthorized files.
     */
    public static function unauthorizedBulkAccess(array $fileIds, ?User $user = null): self
    {
        $userId = $user ? $user->id : 'guest';
        $userRole = $user ? $user->role : 'guest';
        
        return new self(
            message: "Unauthorized bulk access to files by user {$userId} with role {$userRole}",
            userMessage: "You do not have permission to access one or more of the selected files.",
            code: 403,
            context: [
                'file_ids' => $fileIds,
                'user_id' => $userId,
                'user_role' => $userRole,
                'type' => 'unauthorized_bulk_access'
            ]
        );
    }

    /**
     * Create an exception for file download access denied.
     */
    public static function downloadDenied(FileUpload $file, ?User $user = null): self
    {
        $userId = $user ? $user->id : 'guest';
        
        return new self(
            message: "Download access denied for file {$file->id} by user {$userId}",
            userMessage: "You do not have permission to download this file.",
            code: 403,
            context: [
                'file_id' => $file->id,
                'user_id' => $userId,
                'type' => 'download_access_denied'
            ]
        );
    }

    /**
     * Create an exception for file preview access denied.
     */
    public static function previewDenied(FileUpload $file, ?User $user = null): self
    {
        $userId = $user ? $user->id : 'guest';
        
        return new self(
            message: "Preview access denied for file {$file->id} by user {$userId}",
            userMessage: "You do not have permission to preview this file.",
            code: 403,
            context: [
                'file_id' => $file->id,
                'user_id' => $userId,
                'type' => 'preview_access_denied'
            ]
        );
    }

    /**
     * Create an exception for file deletion access denied.
     */
    public static function deletionDenied(FileUpload $file, ?User $user = null): self
    {
        $userId = $user ? $user->id : 'guest';
        
        return new self(
            message: "Deletion access denied for file {$file->id} by user {$userId}",
            userMessage: "You do not have permission to delete this file.",
            code: 403,
            context: [
                'file_id' => $file->id,
                'user_id' => $userId,
                'type' => 'deletion_access_denied'
            ]
        );
    }

    /**
     * Get the appropriate redirect route based on the exception context.
     */
    protected function getRedirectRoute(): string
    {
        // For file access exceptions, redirect to dashboard
        return 'admin.dashboard';
    }
}