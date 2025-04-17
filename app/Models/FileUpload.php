<?php

namespace App\Models;

use App\Services\CloudStorage\CloudStorageFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FileUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'filename',
        'original_filename',
        'provider_file_id',
        'storage_provider',
        'message',
        'validation_method',
        'mime_type',
        'file_size',
        'google_drive_file_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the cloud storage provider instance for this file upload.
     *
     * @return \App\Services\CloudStorage\CloudStorageProvider
     */
    public function getStorageProvider()
    {
        $factory = new CloudStorageFactory();
        return $factory->create($this->storage_provider);
    }

    /**
     * Delete the file from cloud storage.
     *
     * @return bool True if deletion was successful, false otherwise
     */
    public function deleteFromCloudStorage(): bool
    {
        if (!$this->provider_file_id) {
            return true; // Nothing to delete
        }

        try {
            $provider = $this->getStorageProvider();
            return $provider->deleteFile($this->provider_file_id);
        } catch (\Exception $e) {
            \Log::error('Failed to delete file from cloud storage', [
                'file_upload_id' => $this->id,
                'provider' => $this->storage_provider,
                'provider_file_id' => $this->provider_file_id,
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
}
