<?php

namespace App\Exceptions;

class FileAccessException extends FileManagerException
{
    public static function fileNotFound(string $filename, int $fileId = null): self
    {
        return new self(
            message: "File not found: {$filename}" . ($fileId ? " (ID: {$fileId})" : ''),
            userMessage: "The file '{$filename}' could not be found. It may have been deleted or moved.",
            code: 404,
            context: [
                'filename' => $filename,
                'file_id' => $fileId,
                'type' => 'file_not_found'
            ]
        );
    }

    public static function permissionDenied(string $filename, string $userRole, int $userId): self
    {
        return new self(
            message: "Permission denied for user {$userId} ({$userRole}) to access file: {$filename}",
            userMessage: "You don't have permission to access this file. Please contact your administrator if you believe this is an error.",
            code: 403,
            context: [
                'filename' => $filename,
                'user_id' => $userId,
                'user_role' => $userRole,
                'type' => 'permission_denied'
            ]
        );
    }

    public static function fileCorrupted(string $filename, string $reason = null): self
    {
        return new self(
            message: "File corrupted: {$filename}" . ($reason ? " - {$reason}" : ''),
            userMessage: "The file '{$filename}' appears to be corrupted and cannot be accessed.",
            code: 422,
            context: [
                'filename' => $filename,
                'reason' => $reason,
                'type' => 'file_corrupted'
            ]
        );
    }

    public static function fileTooLarge(string $filename, int $fileSize, int $maxSize): self
    {
        return new self(
            message: "File too large: {$filename} ({$fileSize} bytes, max: {$maxSize} bytes)",
            userMessage: "The file '{$filename}' is too large to process. Maximum allowed size is " . format_bytes($maxSize) . ".",
            code: 422,
            context: [
                'filename' => $filename,
                'file_size' => $fileSize,
                'max_size' => $maxSize,
                'type' => 'file_too_large'
            ]
        );
    }
}