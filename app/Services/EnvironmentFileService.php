<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Service for secure environment file management during setup.
 * 
 * Provides backup, validation, and secure updating of environment files
 * with rollback capabilities and integrity checking.
 */
class EnvironmentFileService
{
    private SetupSecurityService $securityService;
    private string $envPath;
    private string $backupDirectory;
    private const MAX_BACKUP_FILES = 10;

    public function __construct(SetupSecurityService $securityService)
    {
        $this->securityService = $securityService;
        $this->envPath = base_path('.env');
        $this->backupDirectory = storage_path('app/setup/env-backups');
    }

    /**
     * Create backup of current environment file.
     */
    public function createBackup(): array
    {
        $result = [
            'success' => false,
            'backup_path' => '',
            'message' => ''
        ];

        try {
            // Ensure backup directory exists
            if (!File::exists($this->backupDirectory)) {
                if (!File::makeDirectory($this->backupDirectory, 0755, true)) {
                    $result['message'] = 'Cannot create backup directory';
                    return $result;
                }
            }

            // Check if .env file exists
            if (!File::exists($this->envPath)) {
                $result['message'] = 'Environment file does not exist';
                return $result;
            }

            // Create backup filename with timestamp
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupFilename = ".env.backup.{$timestamp}";
            $backupPath = $this->backupDirectory . '/' . $backupFilename;

            // Copy environment file to backup
            if (!File::copy($this->envPath, $backupPath)) {
                $result['message'] = 'Failed to create backup file';
                return $result;
            }

            // Set secure permissions on backup
            chmod($backupPath, 0600);

            // Clean up old backups
            $this->cleanupOldBackups();

            $result['success'] = true;
            $result['backup_path'] = $backupPath;
            $result['message'] = 'Backup created successfully';

            Log::info('Environment file backup created', [
                'backup_path' => $backupPath,
                'original_size' => File::size($this->envPath),
                'backup_size' => File::size($backupPath)
            ]);

        } catch (\Exception $e) {
            $result['message'] = 'Backup failed: ' . $e->getMessage();
            
            Log::error('Environment file backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * Update environment file with new values.
     */
    public function updateEnvironmentFile(array $updates): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'backup_created' => false,
            'backup_path' => '',
            'violations' => []
        ];

        try {
            // Validate all environment variables first
            foreach ($updates as $key => $value) {
                $validation = $this->securityService->validateEnvironmentVariable($key, $value);
                if (!$validation['valid']) {
                    $result['violations'] = array_merge($result['violations'], $validation['violations']);
                }
            }

            if (!empty($result['violations'])) {
                $result['message'] = 'Environment variable validation failed';
                return $result;
            }

            // Create backup before making changes
            $backupResult = $this->createBackup();
            if (!$backupResult['success']) {
                $result['message'] = 'Cannot create backup: ' . $backupResult['message'];
                return $result;
            }

            $result['backup_created'] = true;
            $result['backup_path'] = $backupResult['backup_path'];

            // Read current environment file
            $envContent = File::exists($this->envPath) ? File::get($this->envPath) : '';
            
            // Parse existing environment variables
            $existingVars = $this->parseEnvironmentFile($envContent);

            // Merge with updates
            $updatedVars = array_merge($existingVars, $updates);

            // Generate new environment file content
            $newContent = $this->generateEnvironmentFileContent($updatedVars);

            // Write updated content
            if (!File::put($this->envPath, $newContent)) {
                $result['message'] = 'Failed to write environment file';
                return $result;
            }

            // Set secure permissions
            chmod($this->envPath, 0600);

            // Validate the updated file
            $validationResult = $this->validateEnvironmentFile();
            if (!$validationResult['valid']) {
                // Restore from backup if validation fails
                $this->restoreFromBackup($result['backup_path']);
                $result['message'] = 'Environment file validation failed after update';
                $result['violations'] = $validationResult['violations'];
                return $result;
            }

            $result['success'] = true;
            $result['message'] = 'Environment file updated successfully';

            Log::info('Environment file updated', [
                'updated_keys' => array_keys($updates),
                'backup_path' => $result['backup_path']
            ]);

            // Log security event
            $this->securityService->logSecurityEvent('environment_file_updated', [
                'updated_variables' => array_keys($updates),
                'backup_created' => true
            ]);

        } catch (\Exception $e) {
            $result['message'] = 'Environment file update failed: ' . $e->getMessage();
            
            // Try to restore from backup if it was created
            if ($result['backup_created'] && !empty($result['backup_path'])) {
                $this->restoreFromBackup($result['backup_path']);
            }

            Log::error('Environment file update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'updates' => array_keys($updates)
            ]);
        }

        return $result;
    }

    /**
     * Restore environment file from backup.
     */
    public function restoreFromBackup(string $backupPath): array
    {
        $result = [
            'success' => false,
            'message' => ''
        ];

        try {
            if (!File::exists($backupPath)) {
                $result['message'] = 'Backup file does not exist';
                return $result;
            }

            if (!File::copy($backupPath, $this->envPath)) {
                $result['message'] = 'Failed to restore from backup';
                return $result;
            }

            // Set secure permissions
            chmod($this->envPath, 0600);

            $result['success'] = true;
            $result['message'] = 'Environment file restored from backup';

            Log::info('Environment file restored from backup', [
                'backup_path' => $backupPath
            ]);

            // Log security event
            $this->securityService->logSecurityEvent('environment_file_restored', [
                'backup_path' => basename($backupPath)
            ]);

        } catch (\Exception $e) {
            $result['message'] = 'Restore failed: ' . $e->getMessage();
            
            Log::error('Environment file restore failed', [
                'backup_path' => $backupPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * Validate environment file integrity and format.
     */
    public function validateEnvironmentFile(): array
    {
        $result = [
            'valid' => false,
            'violations' => []
        ];

        try {
            if (!File::exists($this->envPath)) {
                $result['violations'][] = 'Environment file does not exist';
                return $result;
            }

            $content = File::get($this->envPath);
            if ($content === false) {
                $result['violations'][] = 'Cannot read environment file';
                return $result;
            }

            // Parse and validate each line
            $lines = explode("\n", $content);
            $lineNumber = 0;

            foreach ($lines as $line) {
                $lineNumber++;
                $line = trim($line);

                // Skip empty lines and comments
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                // Validate line format
                if (!str_contains($line, '=')) {
                    $result['violations'][] = "Invalid format on line {$lineNumber}: missing '='";
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Validate key format
                if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                    $result['violations'][] = "Invalid variable name on line {$lineNumber}: {$key}";
                }

                // Check for dangerous patterns in value
                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
                    $result['violations'][] = "Invalid characters in value on line {$lineNumber}";
                }
            }

            // Check for required variables
            $requiredVars = ['APP_NAME', 'APP_ENV', 'APP_KEY'];
            $existingVars = $this->parseEnvironmentFile($content);

            foreach ($requiredVars as $requiredVar) {
                if (!isset($existingVars[$requiredVar])) {
                    $result['violations'][] = "Missing required variable: {$requiredVar}";
                }
            }

            $result['valid'] = empty($result['violations']);

        } catch (\Exception $e) {
            $result['violations'][] = 'Validation failed: ' . $e->getMessage();
            
            Log::error('Environment file validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * Get list of available backup files.
     */
    public function getAvailableBackups(): array
    {
        $backups = [];

        try {
            if (!File::exists($this->backupDirectory)) {
                return $backups;
            }

            $files = File::files($this->backupDirectory);

            foreach ($files as $file) {
                if (str_starts_with($file->getFilename(), '.env.backup.')) {
                    $backups[] = [
                        'filename' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'created_at' => \Carbon\Carbon::createFromTimestamp($file->getMTime()),
                        'size' => $file->getSize()
                    ];
                }
            }

            // Sort by creation time (newest first)
            usort($backups, fn($a, $b) => $b['created_at']->timestamp - $a['created_at']->timestamp);

        } catch (\Exception $e) {
            Log::error('Failed to get available backups', [
                'error' => $e->getMessage()
            ]);
        }

        return $backups;
    }

    /**
     * Parse environment file content into key-value pairs.
     */
    private function parseEnvironmentFile(string $content): array
    {
        $variables = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse key=value pairs
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                $variables[$key] = $value;
            }
        }

        return $variables;
    }

    /**
     * Generate environment file content from variables array.
     */
    private function generateEnvironmentFileContent(array $variables): string
    {
        $content = [];

        // Add header comment
        $content[] = '# Environment Configuration';
        $content[] = '# Generated by Setup Wizard on ' . now()->toDateTimeString();
        $content[] = '';

        // Group variables by category for better organization
        $categories = [
            'Application' => ['APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL'],
            'Database' => ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
            'Google Drive' => ['GOOGLE_DRIVE_CLIENT_ID', 'GOOGLE_DRIVE_CLIENT_SECRET', 'GOOGLE_DRIVE_REDIRECT_URI'],
            'Mail' => ['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD'],
            'Cache' => ['CACHE_DRIVER', 'SESSION_DRIVER', 'QUEUE_CONNECTION'],
        ];

        foreach ($categories as $categoryName => $categoryVars) {
            $hasVarsInCategory = false;
            $categoryContent = [];

            foreach ($categoryVars as $var) {
                if (isset($variables[$var])) {
                    $value = $variables[$var];
                    
                    // Quote values that contain spaces or special characters
                    if (preg_match('/[\s#"\'\\\\]/', $value)) {
                        $value = '"' . addslashes($value) . '"';
                    }
                    
                    $categoryContent[] = "{$var}={$value}";
                    $hasVarsInCategory = true;
                    unset($variables[$var]);
                }
            }

            if ($hasVarsInCategory) {
                $content[] = "# {$categoryName}";
                $content = array_merge($content, $categoryContent);
                $content[] = '';
            }
        }

        // Add any remaining variables
        if (!empty($variables)) {
            $content[] = '# Other Configuration';
            foreach ($variables as $key => $value) {
                // Quote values that contain spaces or special characters
                if (preg_match('/[\s#"\'\\\\]/', $value)) {
                    $value = '"' . addslashes($value) . '"';
                }
                $content[] = "{$key}={$value}";
            }
        }

        return implode("\n", $content) . "\n";
    }

    /**
     * Clean up old backup files.
     */
    private function cleanupOldBackups(): void
    {
        try {
            $backups = $this->getAvailableBackups();

            if (count($backups) > self::MAX_BACKUP_FILES) {
                $backupsToDelete = array_slice($backups, self::MAX_BACKUP_FILES);

                foreach ($backupsToDelete as $backup) {
                    File::delete($backup['path']);
                    Log::info('Old environment backup deleted', [
                        'filename' => $backup['filename']
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::warning('Failed to cleanup old environment backups', [
                'error' => $e->getMessage()
            ]);
        }
    }
}