<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileUpload extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'file_size' => 'integer',
        'chunk_size' => 'integer',
        'total_chunks' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
}
