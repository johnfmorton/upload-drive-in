<?php

namespace App\Services;

use App\Models\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service for file content security validation.
 * Provides malware scanning, content validation, and security checks.
 */
class FileSecurityService
{
    /**
     * Dangerous file extensions that should be blocked.
     */
    private const DANGEROUS_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
        'php', 'asp', 'aspx', 'jsp', 'pl', 'py', 'rb', 'sh', 'ps1',
        'msi', 'deb', 'rpm', 'dmg', 'pkg', 'app'
    ];

    /**
     * Suspicious file signatures (magic bytes).
     */
    private const SUSPICIOUS_SIGNATURES = [
        'MZ' => 'Executable file',
        '4D5A' => 'DOS executable',
        '504B0304' => 'ZIP archive (potential executable)',
        'D0CF11E0' => 'Microsoft Office document (potential macro)',
        '25504446' => 'PDF (potential malicious script)',
    ];

    /**
     * Maximum file size for content scanning (in bytes).
     */
    private const MAX_SCAN_SIZE = 50 * 1024 * 1024; // 50MB

    /**
     * Validate file security before processing.
     */
    public function validateFileUpload(UploadedFile $file): array
    {
        $violations = [];

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            $violations[] = [
                'type' => 'dangerous_extension',
                'message' => "File extension '{$extension}' is not allowed for security reasons",
                'severity' => 'high'
            ];
        }

        // Check MIME type consistency
        $declaredMime = $file->getMimeType();
        $detectedMime = $this->detectMimeType($file->getRealPath());
        
        if ($declaredMime !== $detectedMime) {
            $violations[] = [
                'type' => 'mime_mismatch',
                'message' => "File MIME type mismatch: declared '{$declaredMime}', detected '{$detectedMime}'",
                'severity' => 'medium'
            ];
        }

        // Check file signature
        $signature = $this->getFileSignature($file->getRealPath());
        if ($this->isSuspiciousSignature($signature)) {
            $violations[] = [
                'type' => 'suspicious_signature',
                'message' => "File contains suspicious signature: {$signature}",
                'severity' => 'high'
            ];
        }

        // Check file size limits
        if ($file->getSize() > config('filesystems.max_file_size', 100 * 1024 * 1024)) {
            $violations[] = [
                'type' => 'size_exceeded',
                'message' => 'File size exceeds maximum allowed limit',
                'severity' => 'medium'
            ];
        }

        // Scan file content if small enough
        if ($file->getSize() <= self::MAX_SCAN_SIZE) {
            $contentViolations = $this->scanFileContent($file->getRealPath());
            $violations = array_merge($violations, $contentViolations);
        }

        return $violations;
    }

    /**
     * Validate existing file for security issues.
     */
    public function validateExistingFile(FileUpload $file): array
    {
        $violations = [];

        // Get file path
        $filePath = $this->getFilePath($file);
        if (!$filePath || !file_exists($filePath)) {
            return [
                [
                    'type' => 'file_not_found',
                    'message' => 'File not found for security validation',
                    'severity' => 'low'
                ]
            ];
        }

        // Check file extension
        $extension = strtolower(pathinfo($file->original_filename, PATHINFO_EXTENSION));
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            $violations[] = [
                'type' => 'dangerous_extension',
                'message' => "File extension '{$extension}' is potentially dangerous",
                'severity' => 'high'
            ];
        }

        // Check file signature
        $signature = $this->getFileSignature($filePath);
        if ($this->isSuspiciousSignature($signature)) {
            $violations[] = [
                'type' => 'suspicious_signature',
                'message' => "File contains suspicious signature: {$signature}",
                'severity' => 'high'
            ];
        }

        // Scan file content if small enough
        if ($file->file_size <= self::MAX_SCAN_SIZE) {
            $contentViolations = $this->scanFileContent($filePath);
            $violations = array_merge($violations, $contentViolations);
        }

        return $violations;
    }

    /**
     * Detect MIME type using file content.
     */
    private function detectMimeType(string $filePath): string
    {
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mimeType ?: 'application/octet-stream';
        }

        return mime_content_type($filePath) ?: 'application/octet-stream';
    }

    /**
     * Get file signature (magic bytes).
     */
    private function getFileSignature(string $filePath): string
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return '';
        }

        $signature = bin2hex(fread($handle, 8));
        fclose($handle);

        return strtoupper($signature);
    }

    /**
     * Check if file signature is suspicious.
     */
    private function isSuspiciousSignature(string $signature): bool
    {
        foreach (self::SUSPICIOUS_SIGNATURES as $suspiciousSignature => $description) {
            if (str_starts_with($signature, $suspiciousSignature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scan file content for suspicious patterns.
     */
    private function scanFileContent(string $filePath): array
    {
        $violations = [];

        // Read file content (limited to prevent memory issues)
        $content = file_get_contents($filePath, false, null, 0, 1024 * 1024); // 1MB max
        if ($content === false) {
            return [];
        }

        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/eval\s*\(/i' => 'Potential code injection (eval)',
            '/exec\s*\(/i' => 'Potential command execution',
            '/system\s*\(/i' => 'Potential system command',
            '/shell_exec\s*\(/i' => 'Potential shell execution',
            '/<script[^>]*>/i' => 'Potential XSS script tag',
            '/javascript:/i' => 'Potential JavaScript injection',
            '/vbscript:/i' => 'Potential VBScript injection',
            '/data:text\/html/i' => 'Potential HTML data URI',
        ];

        foreach ($suspiciousPatterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                $violations[] = [
                    'type' => 'suspicious_content',
                    'message' => $description,
                    'severity' => 'medium'
                ];
            }
        }

        // Check for embedded executables
        if (strpos($content, 'MZ') === 0 || strpos($content, "\x4D\x5A") === 0) {
            $violations[] = [
                'type' => 'embedded_executable',
                'message' => 'File contains embedded executable code',
                'severity' => 'high'
            ];
        }

        return $violations;
    }

    /**
     * Get file path for existing file.
     */
    private function getFilePath(FileUpload $file): ?string
    {
        // Check local storage first
        $localPath = storage_path('app/public/uploads/' . $file->filename);
        if (file_exists($localPath)) {
            return $localPath;
        }

        // Check private storage
        $privatePath = storage_path('app/private/uploads/' . $file->filename);
        if (file_exists($privatePath)) {
            return $privatePath;
        }

        return null;
    }

    /**
     * Sanitize filename for security.
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit filename length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250 - strlen($extension)) . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Check if file type is allowed for preview.
     */
    public function isPreviewSafe(string $mimeType): bool
    {
        $safeMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'text/plain',
            'text/csv',
            'text/html',
            'text/css',
            'text/javascript',
            'application/json',
            'application/xml',
        ];

        return in_array($mimeType, $safeMimeTypes);
    }
}