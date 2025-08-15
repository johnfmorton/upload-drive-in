<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for handling security aspects of the setup process.
 * 
 * Provides secure file system operations, input sanitization,
 * and security validation for setup operations.
 */
class SetupSecurityService
{
    /**
     * Allowed characters for setup input fields.
     */
    private const ALLOWED_FILENAME_CHARS = 'a-zA-Z0-9._-';
    private const ALLOWED_PATH_CHARS = 'a-zA-Z0-9._/-';
    private const ALLOWED_DATABASE_NAME_CHARS = 'a-zA-Z0-9_';
    private const ALLOWED_USERNAME_CHARS = 'a-zA-Z0-9._@-';

    /**
     * Maximum lengths for various input fields.
     */
    private const MAX_FILENAME_LENGTH = 255;
    private const MAX_PATH_LENGTH = 4096;
    private const MAX_DATABASE_NAME_LENGTH = 64;
    private const MAX_USERNAME_LENGTH = 255;
    private const MAX_PASSWORD_LENGTH = 255;

    /**
     * Dangerous path patterns that should be blocked.
     */
    private const DANGEROUS_PATH_PATTERNS = [
        '../',
        '..\\',
        '/etc/',
        '/var/',
        '/usr/',
        '/bin/',
        '/sbin/',
        '/root/',
        '/home/',
        'C:\\',
        'D:\\',
        '\\Windows\\',
        '\\System32\\',
    ];

    /**
     * Validate and sanitize file path for secure operations.
     */
    public function validateAndSanitizePath(string $path, string $baseDirectory = null): array
    {
        $result = [
            'valid' => false,
            'sanitized_path' => '',
            'violations' => []
        ];

        // Basic validation
        if (empty($path)) {
            $result['violations'][] = 'Path cannot be empty';
            return $result;
        }

        if (strlen($path) > self::MAX_PATH_LENGTH) {
            $result['violations'][] = 'Path exceeds maximum length';
            return $result;
        }

        // Check for dangerous patterns
        foreach (self::DANGEROUS_PATH_PATTERNS as $pattern) {
            if (stripos($path, $pattern) !== false) {
                $result['violations'][] = "Path contains dangerous pattern: {$pattern}";
                return $result;
            }
        }

        // Normalize path separators
        $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        
        // Remove any path traversal attempts
        $pathParts = explode(DIRECTORY_SEPARATOR, $normalizedPath);
        $safeParts = [];
        
        foreach ($pathParts as $part) {
            if ($part === '.' || $part === '..') {
                $result['violations'][] = 'Path traversal attempts are not allowed';
                return $result;
            }
            
            if (!empty($part)) {
                // Sanitize each path component
                $allowedChars = str_replace(['/', '-'], ['\/', '\-'], self::ALLOWED_PATH_CHARS);
                $sanitizedPart = preg_replace('/[^' . $allowedChars . ']/', '_', $part);
                $safeParts[] = $sanitizedPart;
            }
        }

        $sanitizedPath = implode(DIRECTORY_SEPARATOR, $safeParts);

        // If base directory is specified, ensure path is within it
        if ($baseDirectory) {
            $baseDirectory = realpath($baseDirectory);
            if ($baseDirectory === false) {
                $result['violations'][] = 'Base directory does not exist';
                return $result;
            }

            $fullPath = $baseDirectory . DIRECTORY_SEPARATOR . $sanitizedPath;
            $directory = dirname($fullPath);
            
            // Try to get real path, but if directory doesn't exist, validate the constructed path
            $realPath = realpath($directory);
            if ($realPath === false) {
                // Directory doesn't exist, validate the constructed path instead
                $normalizedDirectory = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $directory);
                if (!str_starts_with($normalizedDirectory, $baseDirectory)) {
                    $result['violations'][] = 'Path is outside allowed directory';
                    return $result;
                }
            } else {
                // Directory exists, use realpath validation
                if (!str_starts_with($realPath, $baseDirectory)) {
                    $result['violations'][] = 'Path is outside allowed directory';
                    return $result;
                }
            }
        }

        $result['valid'] = true;
        $result['sanitized_path'] = $sanitizedPath;
        
        return $result;
    }

    /**
     * Sanitize database configuration input.
     */
    public function sanitizeDatabaseConfig(array $config): array
    {
        $sanitized = [];
        $violations = [];

        // Sanitize database name
        if (isset($config['database'])) {
            $dbName = $config['database'];
            if (strlen($dbName) > self::MAX_DATABASE_NAME_LENGTH) {
                $violations[] = 'Database name exceeds maximum length';
            } else {
                $sanitized['database'] = preg_replace('/[^' . str_replace('-', '\-', self::ALLOWED_DATABASE_NAME_CHARS) . ']/', '_', $dbName);
            }
        }

        // Sanitize username
        if (isset($config['username'])) {
            $username = $config['username'];
            if (strlen($username) > self::MAX_USERNAME_LENGTH) {
                $violations[] = 'Username exceeds maximum length';
            } else {
                $sanitized['username'] = preg_replace('/[^' . str_replace('-', '\-', self::ALLOWED_USERNAME_CHARS) . ']/', '_', $username);
            }
        }

        // Validate password length (don't sanitize content)
        if (isset($config['password'])) {
            if (strlen($config['password']) > self::MAX_PASSWORD_LENGTH) {
                $violations[] = 'Password exceeds maximum length';
            } else {
                $sanitized['password'] = $config['password'];
            }
        }

        // Sanitize host (allow dots and hyphens for domains)
        if (isset($config['host'])) {
            $host = $config['host'];
            $sanitized['host'] = preg_replace('/[^a-zA-Z0-9.\-]/', '', $host);
        }

        // Validate port
        if (isset($config['port'])) {
            $port = (int) $config['port'];
            if ($port < 1 || $port > 65535) {
                $violations[] = 'Port must be between 1 and 65535';
            } else {
                $sanitized['port'] = $port;
            }
        }

        return [
            'sanitized' => $sanitized,
            'violations' => $violations
        ];
    }

    /**
     * Sanitize admin user input.
     */
    public function sanitizeAdminUserInput(array $input): array
    {
        $sanitized = [];
        $violations = [];

        // Sanitize name
        if (isset($input['name'])) {
            $name = trim($input['name']);
            if (strlen($name) > 255) {
                $violations[] = 'Name exceeds maximum length';
            } else {
                // Allow letters, numbers, spaces, and common name characters
                $sanitized['name'] = preg_replace('/[^a-zA-Z0-9\s\'-.]/', '', $name);
            }
        }

        // Validate email format
        if (isset($input['email'])) {
            $email = trim(strtolower($input['email']));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $violations[] = 'Invalid email format';
            } elseif (strlen($email) > 255) {
                $violations[] = 'Email exceeds maximum length';
            } else {
                $sanitized['email'] = $email;
            }
        }

        // Validate password (don't sanitize content, just check length)
        if (isset($input['password'])) {
            if (strlen($input['password']) > self::MAX_PASSWORD_LENGTH) {
                $violations[] = 'Password exceeds maximum length';
            } else {
                $sanitized['password'] = $input['password'];
            }
        }

        return [
            'sanitized' => $sanitized,
            'violations' => $violations
        ];
    }

    /**
     * Sanitize cloud storage configuration input.
     */
    public function sanitizeStorageConfig(array $config): array
    {
        $sanitized = [];
        $violations = [];

        // Sanitize client ID (alphanumeric and common OAuth characters)
        if (isset($config['client_id'])) {
            $clientId = trim($config['client_id']);
            if (strlen($clientId) > 255) {
                $violations[] = 'Client ID exceeds maximum length';
            } else {
                $sanitized['client_id'] = preg_replace('/[^a-zA-Z0-9._\-]/', '', $clientId);
            }
        }

        // Sanitize client secret (alphanumeric and common OAuth characters)
        if (isset($config['client_secret'])) {
            $clientSecret = trim($config['client_secret']);
            if (strlen($clientSecret) > 255) {
                $violations[] = 'Client secret exceeds maximum length';
            } else {
                $sanitized['client_secret'] = preg_replace('/[^a-zA-Z0-9._\-]/', '', $clientSecret);
            }
        }

        return [
            'sanitized' => $sanitized,
            'violations' => $violations
        ];
    }

    /**
     * Perform secure file operations with validation.
     */
    public function secureFileWrite(string $path, string $content, int $permissions = 0644): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'violations' => []
        ];

        try {
            // Validate path
            $pathValidation = $this->validateAndSanitizePath($path, storage_path('app'));
            if (!$pathValidation['valid']) {
                $result['violations'] = $pathValidation['violations'];
                $result['message'] = 'Invalid file path';
                return $result;
            }

            $fullPath = storage_path('app/' . $pathValidation['sanitized_path']);
            $directory = dirname($fullPath);

            // Ensure directory exists and is writable
            if (!File::exists($directory)) {
                if (!File::makeDirectory($directory, 0755, true)) {
                    $result['message'] = 'Cannot create directory';
                    $result['violations'][] = 'Directory creation failed';
                    return $result;
                }
            }

            if (!is_writable($directory)) {
                $result['message'] = 'Directory is not writable';
                $result['violations'][] = 'Permission denied';
                return $result;
            }

            // Write file securely
            if (File::put($fullPath, $content) === false) {
                $result['message'] = 'Failed to write file';
                $result['violations'][] = 'File write operation failed';
                return $result;
            }

            // Set secure permissions
            if (!chmod($fullPath, $permissions)) {
                Log::warning('Could not set file permissions', [
                    'path' => $fullPath,
                    'permissions' => decoct($permissions)
                ]);
            }

            $result['success'] = true;
            $result['message'] = 'File written successfully';

        } catch (\Exception $e) {
            $result['message'] = 'File operation failed: ' . $e->getMessage();
            $result['violations'][] = 'Exception during file operation';
            
            Log::error('Secure file write failed', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * Perform secure file read with validation.
     */
    public function secureFileRead(string $path): array
    {
        $result = [
            'success' => false,
            'content' => '',
            'message' => '',
            'violations' => []
        ];

        try {
            // Validate path
            $pathValidation = $this->validateAndSanitizePath($path, storage_path('app'));
            if (!$pathValidation['valid']) {
                $result['violations'] = $pathValidation['violations'];
                $result['message'] = 'Invalid file path';
                return $result;
            }

            $fullPath = storage_path('app/' . $pathValidation['sanitized_path']);

            // Check if file exists and is readable
            if (!File::exists($fullPath)) {
                $result['message'] = 'File does not exist';
                $result['violations'][] = 'File not found';
                return $result;
            }

            if (!is_readable($fullPath)) {
                $result['message'] = 'File is not readable';
                $result['violations'][] = 'Permission denied';
                return $result;
            }

            // Read file content
            $content = File::get($fullPath);
            if ($content === false) {
                $result['message'] = 'Failed to read file';
                $result['violations'][] = 'File read operation failed';
                return $result;
            }

            $result['success'] = true;
            $result['content'] = $content;
            $result['message'] = 'File read successfully';

        } catch (\Exception $e) {
            $result['message'] = 'File operation failed: ' . $e->getMessage();
            $result['violations'][] = 'Exception during file operation';
            
            Log::error('Secure file read failed', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * Validate environment variable name and value.
     */
    public function validateEnvironmentVariable(string $name, string $value): array
    {
        $violations = [];

        // Validate variable name
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) {
            $violations[] = 'Environment variable name must contain only uppercase letters, numbers, and underscores';
        }

        if (strlen($name) > 255) {
            $violations[] = 'Environment variable name exceeds maximum length';
        }

        // Validate value length
        if (strlen($value) > 4096) {
            $violations[] = 'Environment variable value exceeds maximum length';
        }

        // Check for dangerous patterns in value
        $dangerousPatterns = [
            '/\$\(.*\)/',  // Command substitution
            '/`.*`/',      // Backtick execution
            '/\${.*}/',    // Variable expansion
            '/\|\|/',      // Command chaining
            '/&&/',        // Command chaining
            '/;/',         // Command separator
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $violations[] = 'Environment variable value contains potentially dangerous patterns';
                break;
            }
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations
        ];
    }

    /**
     * Generate secure random token for setup operations.
     */
    public function generateSecureToken(int $length = 32): string
    {
        return Str::random($length);
    }

    /**
     * Validate setup session integrity.
     */
    public function validateSetupSession(array $sessionData): array
    {
        $violations = [];

        // Check for required session fields
        $requiredFields = ['setup_started_at', 'setup_token', 'current_step'];
        foreach ($requiredFields as $field) {
            if (!isset($sessionData[$field])) {
                $violations[] = "Missing required session field: {$field}";
            }
        }

        // Validate setup token format
        if (isset($sessionData['setup_token'])) {
            if (!preg_match('/^[a-zA-Z0-9]{32,}$/', $sessionData['setup_token'])) {
                $violations[] = 'Invalid setup token format';
            }
        }

        // Check session age
        if (isset($sessionData['setup_started_at'])) {
            try {
                $startTime = \Carbon\Carbon::parse($sessionData['setup_started_at']);
                $maxAge = now()->subHours(2); // 2 hour session timeout
                
                if ($startTime->lt($maxAge)) {
                    $violations[] = 'Setup session has expired';
                }
            } catch (\Exception $e) {
                $violations[] = 'Invalid setup start time format';
            }
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations
        ];
    }

    /**
     * Log security event for audit purposes.
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $logData = array_merge([
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown',
            'session_id' => session()->getId() ?? 'unknown',
        ], $context);

        Log::channel('security')->info("Setup security event: {$event}", $logData);
    }
}