<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Enums\UserRole;

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
    ];

    protected $casts = [
        'file_size' => 'integer',
        'chunk_size' => 'integer',
        'total_chunks' => 'integer',
        'retry_count' => 'integer',
        'recovery_attempts' => 'integer',
        'error_details' => 'array',
        'last_processed_at' => 'datetime',
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
        'file_size_human'
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
}
