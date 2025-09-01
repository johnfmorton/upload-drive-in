<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Enums\UserRole;
use App\Enums\CloudStorageErrorType;

class FileUpload extends Model
{
    use HasFactory;

    // Status constants for upload recovery system
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_MISSING_FILE = 'missing_file';
    public const STATUS_RETRY = 'retry';

    protected $fillable = [
        'client_user_id',
        'company_user_id',
        'filename',
        'original_filename',
        'provider_file_id',
        'storage_provider',
        'message',
        'validation_method',
        'mime_type',
        'file_size',
        'chunk_size',
        'total_chunks',
        'google_drive_file_id',
        'uploaded_by_user_id',
        'email', // For backward compatibility with existing uploads
        'retry_count',
        'last_error',
        'error_details',
        'last_processed_at',
        'recovery_attempts',
        'cloud_storage_error_type',
        'cloud_storage_error_context',
        'connection_health_at_failure',
        'retry_recommended_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'chunk_size' => 'integer',
        'total_chunks' => 'integer',
        'retry_count' => 'integer',
        'recovery_attempts' => 'integer',
        'error_details' => 'array',
        'cloud_storage_error_context' => 'array',
        'last_processed_at' => 'datetime',
        'connection_health_at_failure' => 'datetime',
        'retry_recommended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'can_preview',
        'preview_url',
        'download_url',
        'thumbnail_url',
        'file_size_human',
        'cloud_storage_error_message',
        'cloud_storage_error_description',
        'cloud_storage_error_severity'
    ];

    /**
     * Get the client user who uploaded this file.
     */
    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    /**
     * Get the company user who received this file.
     */
    public function companyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }

    /**
     * Get the user who uploaded this file (employee or admin).
     */
    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Check if a user can access this file based on their role and relationships.
     *
     * @param User $user The user to check access for
     * @return bool True if the user can access the file, false otherwise
     */
    public function canBeAccessedBy(User $user): bool
    {
        // Admin users can access all files
        if ($user->isAdmin()) {
            return true;
        }

        // Client users can only access files they uploaded
        if ($user->isClient()) {
            return $this->client_user_id === $user->id;
        }

        // Employee users can access files from clients they manage
        if ($user->isEmployee()) {
            // Allow access to files they uploaded themselves
            if ($this->uploaded_by_user_id === $user->id) {
                return true;
            }

            // Check if this employee manages the client who uploaded the file
            if ($this->client_user_id) {
                return $user->clientUsers()->where('client_user_id', $this->client_user_id)->exists();
            }

            return false;
        }

        return false;
    }

    /**
     * Scope to get files accessible by a specific user.
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        // Admin users can access all files
        if ($user->isAdmin()) {
            return $query;
        }

        // Client users can only access their own files
        if ($user->isClient()) {
            return $query->where('client_user_id', $user->id);
        }

        // Employee users can access files from clients they manage or files they uploaded
        if ($user->isEmployee()) {
            $clientIds = $user->clientUsers()->pluck('client_user_id')->toArray();
            
            return $query->where(function ($q) use ($user, $clientIds) {
                $q->whereIn('client_user_id', $clientIds)
                  ->orWhere('uploaded_by_user_id', $user->id);
            });
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if the file can be previewed based on its MIME type.
     *
     * @return bool
     */
    public function isPreviewable(): bool
    {
        if (!$this->mime_type) {
            return false;
        }

        $previewableMimeTypes = [
            // Images
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            // PDFs
            'application/pdf',
            // Text files
            'text/plain',
            'text/html',
            'text/css',
            'text/javascript',
            'application/json',
            'application/xml',
            'text/xml',
        ];

        return in_array($this->mime_type, $previewableMimeTypes);
    }

    /**
     * Get the preview URL for this file.
     *
     * @return string|null
     */
    public function getPreviewUrl(): ?string
    {
        if (!$this->isPreviewable()) {
            return null;
        }

        // Add cache-busting parameter to prevent preview mix-ups
        $timestamp = $this->updated_at ? $this->updated_at->timestamp : time();
        return route('files.preview', $this) . '?v=' . $timestamp;
    }

    /**
     * Get the download URL for this file.
     *
     * @return string
     */
    public function getDownloadUrl(): string
    {
        return route('admin.file-manager.download', $this);
    }

    /**
     * Get the thumbnail URL for this file.
     *
     * @return string|null
     */
    public function getThumbnailUrl(): ?string
    {
        if (!$this->isPreviewable() || !str_starts_with($this->mime_type, 'image/')) {
            return null;
        }

        // Add cache-busting parameter to prevent thumbnail mix-ups
        $timestamp = $this->updated_at ? $this->updated_at->timestamp : time();
        return route('files.thumbnail', $this) . '?v=' . $timestamp;
    }

    /**
     * Accessor for can_preview attribute.
     *
     * @return bool
     */
    public function getCanPreviewAttribute(): bool
    {
        return $this->isPreviewable();
    }

    /**
     * Accessor for preview_url attribute.
     *
     * @return string|null
     */
    public function getPreviewUrlAttribute(): ?string
    {
        return $this->getPreviewUrl();
    }

    /**
     * Accessor for download_url attribute.
     *
     * @return string
     */
    public function getDownloadUrlAttribute(): string
    {
        return $this->getDownloadUrl();
    }

    /**
     * Accessor for thumbnail_url attribute.
     *
     * @return string|null
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->getThumbnailUrl();
    }

    /**
     * Accessor for file_size_human attribute.
     *
     * @return string
     */
    public function getFileSizeHumanAttribute(): string
    {
        return $this->getHumanFileSize();
    }

    /**
     * Accessor for cloud_storage_error_message attribute.
     *
     * @return string|null
     */
    public function getCloudStorageErrorMessageAttribute(): ?string
    {
        return $this->getCloudStorageErrorMessage();
    }

    /**
     * Accessor for cloud_storage_error_description attribute.
     *
     * @return string|null
     */
    public function getCloudStorageErrorDescriptionAttribute(): ?string
    {
        $errorType = $this->getCloudStorageErrorType();
        return $errorType?->getDescription();
    }

    /**
     * Accessor for cloud_storage_error_severity attribute.
     *
     * @return string|null
     */
    public function getCloudStorageErrorSeverityAttribute(): ?string
    {
        return $this->getCloudStorageErrorSeverity();
    }

    /**
     * Get the current status of the upload based on various conditions.
     *
     * @return string
     */
    public function getUploadStatus(): string
    {
        // Check if file has been successfully uploaded
        if (!empty($this->google_drive_file_id)) {
            return self::STATUS_UPLOADED;
        }

        // Check if file is marked as failed
        if (!empty($this->last_error) && $this->retry_count >= config('upload-recovery.max_retry_attempts', 3)) {
            return self::STATUS_FAILED;
        }

        // Check if file is missing from local storage
        if (!$this->localFileExists()) {
            return self::STATUS_MISSING_FILE;
        }

        // Check if file is being retried
        if ($this->retry_count > 0) {
            return self::STATUS_RETRY;
        }

        // Check if file is currently being processed
        if ($this->last_processed_at && $this->last_processed_at->diffInMinutes(now()) < 5) {
            return self::STATUS_PROCESSING;
        }

        // Default to pending
        return self::STATUS_PENDING;
    }

    /**
     * Check if this upload is stuck (pending beyond threshold).
     *
     * @return bool
     */
    public function isStuck(): bool
    {
        $thresholdMinutes = config('upload-recovery.stuck_threshold_minutes', 30);
        
        // If file has been uploaded successfully, it's not stuck
        if (!empty($this->google_drive_file_id)) {
            return false;
        }

        // Check if it's been pending for too long
        $lastActivity = $this->last_processed_at ?? $this->created_at;
        return $lastActivity->diffInMinutes(now()) > $thresholdMinutes;
    }

    /**
     * Check if the local file still exists in storage.
     *
     * @return bool
     */
    public function localFileExists(): bool
    {
        if (!$this->filename) {
            return false;
        }

        $filePath = storage_path('app/public/uploads/' . $this->filename);
        return file_exists($filePath);
    }

    /**
     * Update the recovery status with error information.
     *
     * @param string|null $error
     * @param array|null $errorDetails
     * @return bool
     */
    public function updateRecoveryStatus(?string $error = null, ?array $errorDetails = null): bool
    {
        $this->retry_count = ($this->retry_count ?? 0) + 1;
        $this->last_processed_at = now();
        
        if ($error) {
            $this->last_error = $error;
        }
        
        if ($errorDetails) {
            $this->error_details = array_merge($this->error_details ?? [], $errorDetails);
        }

        return $this->save();
    }

    /**
     * Mark the upload as successfully recovered.
     *
     * @param string $googleDriveFileId
     * @return bool
     */
    public function markAsRecovered(string $googleDriveFileId): bool
    {
        $this->google_drive_file_id = $googleDriveFileId;
        $this->last_error = null;
        $this->error_details = null;
        $this->last_processed_at = now();

        return $this->save();
    }

    /**
     * Increment recovery attempts counter.
     *
     * @return bool
     */
    public function incrementRecoveryAttempts(): bool
    {
        $this->recovery_attempts = ($this->recovery_attempts ?? 0) + 1;
        return $this->save();
    }

    /**
     * Check if the upload has exceeded maximum recovery attempts.
     *
     * @return bool
     */
    public function hasExceededRecoveryAttempts(): bool
    {
        $maxAttempts = config('upload-recovery.max_recovery_attempts', 5);
        return ($this->recovery_attempts ?? 0) >= $maxAttempts;
    }

    /**
     * Check if the upload can be retried.
     *
     * @return bool
     */
    public function canBeRetried(): bool
    {
        $maxRetries = config('upload-recovery.max_retry_attempts', 3);
        return ($this->retry_count ?? 0) < $maxRetries && !$this->hasExceededRecoveryAttempts();
    }

    /**
     * Update cloud storage error information.
     *
     * @param CloudStorageErrorType|string|null $errorType
     * @param array|null $errorContext
     * @param \DateTime|null $connectionHealthAt
     * @param \DateTime|null $retryRecommendedAt
     * @return bool
     */
    public function updateCloudStorageError(
        CloudStorageErrorType|string|null $errorType = null,
        ?array $errorContext = null,
        ?\DateTime $connectionHealthAt = null,
        ?\DateTime $retryRecommendedAt = null
    ): bool {
        if ($errorType instanceof CloudStorageErrorType) {
            $this->cloud_storage_error_type = $errorType->value;
        } elseif (is_string($errorType)) {
            $this->cloud_storage_error_type = $errorType;
        }

        if ($errorContext !== null) {
            $this->cloud_storage_error_context = $errorContext;
        }

        if ($connectionHealthAt !== null) {
            $this->connection_health_at_failure = $connectionHealthAt;
        }

        if ($retryRecommendedAt !== null) {
            $this->retry_recommended_at = $retryRecommendedAt;
        }

        return $this->save();
    }

    /**
     * Clear cloud storage error information.
     *
     * @return bool
     */
    public function clearCloudStorageError(): bool
    {
        $this->cloud_storage_error_type = null;
        $this->cloud_storage_error_context = null;
        $this->connection_health_at_failure = null;
        $this->retry_recommended_at = null;

        return $this->save();
    }

    /**
     * Check if this upload has a cloud storage error.
     *
     * @return bool
     */
    public function hasCloudStorageError(): bool
    {
        return !empty($this->cloud_storage_error_type);
    }

    /**
     * Get the cloud storage error type as an enum.
     *
     * @return CloudStorageErrorType|null
     */
    public function getCloudStorageErrorType(): ?CloudStorageErrorType
    {
        if (empty($this->cloud_storage_error_type)) {
            return null;
        }

        return CloudStorageErrorType::tryFrom($this->cloud_storage_error_type);
    }

    /**
     * Check if the cloud storage error is recoverable.
     *
     * @return bool
     */
    public function isCloudStorageErrorRecoverable(): bool
    {
        $errorType = $this->getCloudStorageErrorType();
        return $errorType?->isRecoverable() ?? false;
    }

    /**
     * Check if the cloud storage error requires user intervention.
     *
     * @return bool
     */
    public function cloudStorageErrorRequiresUserIntervention(): bool
    {
        $errorType = $this->getCloudStorageErrorType();
        return $errorType?->requiresUserIntervention() ?? false;
    }

    /**
     * Get the cloud storage error severity.
     *
     * @return string|null
     */
    public function getCloudStorageErrorSeverity(): ?string
    {
        $errorType = $this->getCloudStorageErrorType();
        return $errorType?->getSeverity();
    }

    /**
     * Get a user-friendly error message for cloud storage errors.
     *
     * @return string|null
     */
    public function getCloudStorageErrorMessage(): ?string
    {
        $errorType = $this->getCloudStorageErrorType();
        if (!$errorType) {
            return null;
        }

        $provider = $this->storage_provider ?? 'cloud storage';
        $context = $this->cloud_storage_error_context ?? [];

        return match ($errorType) {
            CloudStorageErrorType::TOKEN_EXPIRED => 
                "Your {$provider} connection has expired. Please reconnect to continue uploading files.",
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 
                "Insufficient {$provider} permissions. Please reconnect and grant full access.",
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 
                "{$provider} API limit reached. " . ($context['retry_after'] ?? 'Uploads will resume automatically later.'),
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => 
                "{$provider} storage quota exceeded. Please free up space or upgrade your plan.",
            CloudStorageErrorType::NETWORK_ERROR => 
                'Network connection issue. The upload will be retried automatically.',
            CloudStorageErrorType::FILE_NOT_FOUND => 
                'The target folder was not found in your cloud storage.',
            CloudStorageErrorType::FOLDER_ACCESS_DENIED => 
                'Access denied to the target folder in your cloud storage.',
            CloudStorageErrorType::INVALID_FILE_TYPE => 
                'This file type is not allowed by your cloud storage provider.',
            CloudStorageErrorType::FILE_TOO_LARGE => 
                'File size exceeds the maximum allowed by your cloud storage provider.',
            CloudStorageErrorType::INVALID_FILE_CONTENT => 
                'The file content is invalid or corrupted.',
            CloudStorageErrorType::SERVICE_UNAVAILABLE => 
                "{$provider} service is temporarily unavailable. The upload will be retried automatically.",
            CloudStorageErrorType::INVALID_CREDENTIALS => 
                "Invalid {$provider} credentials. Please reconnect your account.",
            CloudStorageErrorType::TIMEOUT => 
                'Upload timed out. The upload will be retried automatically.',
            CloudStorageErrorType::UNKNOWN_ERROR => 
                "{$provider} upload failed: " . ($context['original_message'] ?? 'Unknown error occurred.'),
        };
    }

    /**
     * Delete the file from Google Drive using the new service approach.
     *
     * @return bool True if deletion was successful, false otherwise
     */
    public function deleteFromGoogleDrive(): bool
    {
        if (!$this->google_drive_file_id) {
            return true; // Nothing to delete
        }

        try {
            // Find a user with Google Drive connected to perform the deletion
            $user = $this->uploadedByUser;
            if (!$user || !$user->hasGoogleDriveConnected()) {
                // Fallback to admin user
                $user = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)
                    ->whereHas('googleDriveToken')
                    ->first();
            }

            if (!$user) {
                \Log::warning('No user with Google Drive connection found for file deletion', [
                    'file_upload_id' => $this->id,
                    'google_drive_file_id' => $this->google_drive_file_id
                ]);
                return false;
            }

            $driveService = app(\App\Services\GoogleDriveService::class);
            $service = $driveService->getDriveService($user);
            $service->files->delete($this->google_drive_file_id);
            
            \Log::info('Successfully deleted file from Google Drive', [
                'file_upload_id' => $this->id,
                'google_drive_file_id' => $this->google_drive_file_id
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to delete file from Google Drive', [
                'file_upload_id' => $this->id,
                'google_drive_file_id' => $this->google_drive_file_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if the file size is within the provider's limits.
     *
     * @return bool
     */
    public function isFileSizeWithinProviderLimits(): bool
    {
        $maxSize = config("cloud-storage.features.{$this->storage_provider}.max_file_size", 0);
        return $maxSize === 0 || $this->file_size <= $maxSize;
    }

    /**
     * Get a human-readable file size.
     *
     * @return string
     */
    public function getHumanFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Get the maximum file size allowed for the provider.
     *
     * @return string Human readable maximum file size
     */
    public function getProviderMaxFileSize(): string
    {
        $maxSize = config("cloud-storage.features.{$this->storage_provider}.max_file_size", 0);
        if ($maxSize === 0) {
            return 'Unlimited';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($maxSize >= 1024 && $index < count($units) - 1) {
            $maxSize /= 1024;
            $index++;
        }

        return round($maxSize, 2) . ' ' . $units[$index];
    }

    /**
     * Check if this upload is pending (not yet uploaded to cloud storage).
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return empty($this->google_drive_file_id);
    }

    /**
     * Scope to get only pending uploads.
     */
    public function scopePending($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('google_drive_file_id')
              ->orWhere('google_drive_file_id', '');
        });
    }

    /**
     * Scope to get only completed uploads.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('google_drive_file_id')
                    ->where('google_drive_file_id', '!=', '');
    }

    /**
     * Scope to get uploads that are stuck (pending beyond threshold).
     */
    public function scopeStuck($query)
    {
        $thresholdMinutes = config('upload-recovery.stuck_threshold_minutes', 30);
        $thresholdTime = now()->subMinutes($thresholdMinutes);

        return $query->where(function ($q) {
            $q->whereNull('google_drive_file_id')
              ->orWhere('google_drive_file_id', '');
        })->where(function ($q) use ($thresholdTime) {
            $q->where('last_processed_at', '<', $thresholdTime)
              ->orWhere(function ($subQ) use ($thresholdTime) {
                  $subQ->whereNull('last_processed_at')
                       ->where('created_at', '<', $thresholdTime);
              });
        });
    }

    /**
     * Scope to get uploads that have failed.
     */
    public function scopeFailed($query)
    {
        $maxRetries = config('upload-recovery.max_retry_attempts', 3);
        
        return $query->where('retry_count', '>=', $maxRetries)
                    ->whereNotNull('last_error')
                    ->where(function ($q) {
                        $q->whereNull('google_drive_file_id')
                          ->orWhere('google_drive_file_id', '');
                    });
    }

    /**
     * Scope to get uploads with missing local files.
     */
    public function scopeMissingFile($query)
    {
        // This scope will need to be used with a custom filter since we can't check file existence in SQL
        return $query->where(function ($q) {
            $q->whereNull('google_drive_file_id')
              ->orWhere('google_drive_file_id', '');
        });
    }

    /**
     * Scope to get uploads that are being retried.
     */
    public function scopeRetrying($query)
    {
        return $query->where('retry_count', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('google_drive_file_id')
                          ->orWhere('google_drive_file_id', '');
                    });
    }

    /**
     * Scope to get uploads by status.
     */
    public function scopeByStatus($query, string $status)
    {
        switch ($status) {
            case self::STATUS_UPLOADED:
                return $query->completed();
            case self::STATUS_PENDING:
                return $query->pending();
            case self::STATUS_FAILED:
                return $query->failed();
            case self::STATUS_RETRY:
                return $query->retrying();
            case self::STATUS_MISSING_FILE:
                return $query->missingFile();
            case self::STATUS_PROCESSING:
                // Files processed in the last 5 minutes
                return $query->where('last_processed_at', '>=', now()->subMinutes(5))
                            ->where(function ($q) {
                                $q->whereNull('google_drive_file_id')
                                  ->orWhere('google_drive_file_id', '');
                            });
            default:
                return $query;
        }
    }

    /**
     * Scope to get uploads that can be recovered.
     */
    public function scopeRecoverable($query)
    {
        $maxRecoveryAttempts = config('upload-recovery.max_recovery_attempts', 5);
        
        return $query->where(function ($q) {
            $q->whereNull('google_drive_file_id')
              ->orWhere('google_drive_file_id', '');
        })->where('recovery_attempts', '<', $maxRecoveryAttempts);
    }

    /**
     * Scope to get uploads that need attention (stuck or failed).
     */
    public function scopeNeedsAttention($query)
    {
        $thresholdMinutes = config('upload-recovery.stuck_threshold_minutes', 30);
        $thresholdTime = now()->subMinutes($thresholdMinutes);
        $maxRetries = config('upload-recovery.max_retry_attempts', 3);

        return $query->where(function ($q) {
            $q->whereNull('google_drive_file_id')
              ->orWhere('google_drive_file_id', '');
        })->where(function ($q) use ($thresholdTime, $maxRetries) {
            // Stuck uploads
            $q->where(function ($stuckQ) use ($thresholdTime) {
                $stuckQ->where('last_processed_at', '<', $thresholdTime)
                       ->orWhere(function ($subQ) use ($thresholdTime) {
                           $subQ->whereNull('last_processed_at')
                                ->where('created_at', '<', $thresholdTime);
                       });
            })
            // Or failed uploads
            ->orWhere(function ($failedQ) use ($maxRetries) {
                $failedQ->where('retry_count', '>=', $maxRetries)
                        ->whereNotNull('last_error');
            });
        });
    }

    /**
     * Scope to get uploads with cloud storage errors.
     */
    public function scopeWithCloudStorageError($query)
    {
        return $query->whereNotNull('cloud_storage_error_type');
    }

    /**
     * Scope to get uploads with specific cloud storage error type.
     */
    public function scopeWithCloudStorageErrorType($query, CloudStorageErrorType|string $errorType)
    {
        $errorValue = $errorType instanceof CloudStorageErrorType ? $errorType->value : $errorType;
        return $query->where('cloud_storage_error_type', $errorValue);
    }

    /**
     * Scope to get uploads with recoverable cloud storage errors.
     */
    public function scopeWithRecoverableCloudStorageError($query)
    {
        $recoverableTypes = collect(CloudStorageErrorType::cases())
            ->filter(fn($type) => $type->isRecoverable())
            ->map(fn($type) => $type->value)
            ->toArray();

        return $query->whereIn('cloud_storage_error_type', $recoverableTypes);
    }

    /**
     * Scope to get uploads with cloud storage errors requiring user intervention.
     */
    public function scopeWithCloudStorageErrorRequiringIntervention($query)
    {
        $interventionTypes = collect(CloudStorageErrorType::cases())
            ->filter(fn($type) => $type->requiresUserIntervention())
            ->map(fn($type) => $type->value)
            ->toArray();

        return $query->whereIn('cloud_storage_error_type', $interventionTypes);
    }

    /**
     * Scope to get uploads by cloud storage error severity.
     */
    public function scopeWithCloudStorageErrorSeverity($query, string $severity)
    {
        $severityTypes = collect(CloudStorageErrorType::cases())
            ->filter(fn($type) => $type->getSeverity() === $severity)
            ->map(fn($type) => $type->value)
            ->toArray();

        return $query->whereIn('cloud_storage_error_type', $severityTypes);
    }
}
