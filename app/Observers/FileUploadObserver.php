<?php

namespace App\Observers;

use App\Models\FileUpload;
use App\Services\FileMetadataCacheService;
use App\Services\ThumbnailService;

/**
 * Observer for FileUpload model to handle cache invalidation.
 */
class FileUploadObserver
{
    public function __construct(
        private FileMetadataCacheService $cacheService,
        private ThumbnailService $thumbnailService
    ) {
    }

    /**
     * Handle the FileUpload "created" event.
     */
    public function created(FileUpload $fileUpload): void
    {
        // Invalidate global caches when new file is created
        $this->cacheService->invalidateGlobalCaches();
    }

    /**
     * Handle the FileUpload "updated" event.
     */
    public function updated(FileUpload $fileUpload): void
    {
        // Invalidate file-specific cache
        $this->cacheService->invalidateFileCache($fileUpload);
        
        // Invalidate thumbnail cache if it's an image
        if (str_starts_with($fileUpload->mime_type, 'image/')) {
            $this->thumbnailService->invalidateThumbnailCache($fileUpload);
        }
    }

    /**
     * Handle the FileUpload "deleted" event.
     */
    public function deleted(FileUpload $fileUpload): void
    {
        // Invalidate file-specific cache
        $this->cacheService->invalidateFileCache($fileUpload);
        
        // Invalidate thumbnail cache if it's an image
        if (str_starts_with($fileUpload->mime_type, 'image/')) {
            $this->thumbnailService->invalidateThumbnailCache($fileUpload);
        }
    }

    /**
     * Handle the FileUpload "restored" event.
     */
    public function restored(FileUpload $fileUpload): void
    {
        // Invalidate global caches when file is restored
        $this->cacheService->invalidateGlobalCaches();
    }

    /**
     * Handle the FileUpload "force deleted" event.
     */
    public function forceDeleted(FileUpload $fileUpload): void
    {
        // Invalidate file-specific cache
        $this->cacheService->invalidateFileCache($fileUpload);
        
        // Invalidate thumbnail cache if it's an image
        if (str_starts_with($fileUpload->mime_type, 'image/')) {
            $this->thumbnailService->invalidateThumbnailCache($fileUpload);
        }
    }
}