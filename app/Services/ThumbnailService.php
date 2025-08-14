<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Service for generating and caching file thumbnails.
 */
class ThumbnailService
{
    private const CACHE_TTL = 86400; // 24 hours
    private const CACHE_PREFIX = 'thumbnail:';
    private const THUMBNAIL_SIZE = 200;
    private const THUMBNAIL_QUALITY = 80;

    private ImageManager $imageManager;

    public function __construct()
    {
        // Only initialize if Intervention Image is available
        if (class_exists(ImageManager::class)) {
            $this->imageManager = new ImageManager(new Driver());
        }
    }

    /**
     * Get or generate thumbnail for a file.
     */
    public function getThumbnail(FileUpload $file, User $user): ?Response
    {
        // Check if Intervention Image is available
        if (!isset($this->imageManager)) {
            return null;
        }

        // Check if user can access this file
        if (!$file->canBeAccessedBy($user)) {
            return null;
        }

        // Only generate thumbnails for images
        if (!$this->isImageFile($file)) {
            return null;
        }

        $cacheKey = self::CACHE_PREFIX . $file->id;

        try {
            // Try to get cached thumbnail
            $thumbnailData = Cache::get($cacheKey);

            if ($thumbnailData) {
                return $this->createThumbnailResponse($thumbnailData['content'], $thumbnailData['mime_type']);
            }

            // Generate new thumbnail
            $thumbnailData = $this->generateThumbnail($file);

            if ($thumbnailData) {
                // Cache the thumbnail
                Cache::put($cacheKey, $thumbnailData, self::CACHE_TTL);

                return $this->createThumbnailResponse($thumbnailData['content'], $thumbnailData['mime_type']);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Generate thumbnail for a file.
     */
    private function generateThumbnail(FileUpload $file): ?array
    {
        try {
            $imageContent = $this->getImageContent($file);

            if (!$imageContent) {
                return null;
            }

            // Create image from content
            $image = $this->imageManager->read($imageContent);

            // Resize to thumbnail size while maintaining aspect ratio
            $image->scale(width: self::THUMBNAIL_SIZE, height: self::THUMBNAIL_SIZE);

            // Preserve transparency for formats that support alpha channels
            $supportsAlpha = in_array(strtolower($file->mime_type), [
                'image/png',
                'image/webp',
                'image/gif',
            ]);

            if ($supportsAlpha) {
                // Encode as PNG to retain transparency
                $thumbnailContent = $image->toPng();
                $outputMime = 'image/png';
            } else {
                // Encode as JPEG (smaller; no alpha)
                $thumbnailContent = $image->toJpeg(self::THUMBNAIL_QUALITY);
                $outputMime = 'image/jpeg';
            }

            Log::info('Thumbnail generated successfully', [
                'file_id' => $file->id,
                'original_size' => strlen($imageContent),
                'thumbnail_size' => strlen($thumbnailContent)
            ]);

            return [
                'content' => $thumbnailContent,
                'mime_type' => $outputMime,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate thumbnail', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get image content from local storage or Google Drive.
     */
    private function getImageContent(FileUpload $file): ?string
    {
        // Try local file first
        if ($this->hasLocalFile($file)) {
            try {
                return Storage::disk('public')->get('uploads/' . $file->filename);
            } catch (\Exception $e) {
                Log::warning('Failed to read local file for thumbnail', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Try Google Drive if file has Google Drive ID
        if ($file->google_drive_file_id) {
            try {
                return $this->getGoogleDriveImageContent($file);
            } catch (\Exception $e) {
                Log::warning('Failed to read Google Drive file for thumbnail', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return null;
    }

    /**
     * Get image content from Google Drive.
     */
    private function getGoogleDriveImageContent(FileUpload $file): ?string
    {
        // Find a user with Google Drive access
        $driveUser = $this->findGoogleDriveUser();

        if (!$driveUser) {
            return null;
        }

        $googleDriveService = app(GoogleDriveService::class);

        try {
            return $googleDriveService->downloadFile($driveUser, $file->google_drive_file_id);
        } catch (\Exception $e) {
            Log::error('Failed to download file from Google Drive for thumbnail', [
                'file_id' => $file->id,
                'google_drive_file_id' => $file->google_drive_file_id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Find a user with Google Drive access.
     */
    private function findGoogleDriveUser(): ?User
    {
        return User::where('role', \App\Enums\UserRole::ADMIN)
            ->whereHas('googleDriveToken')
            ->first();
    }

    /**
     * Check if file exists in local storage.
     */
    private function hasLocalFile(FileUpload $file): bool
    {
        return Storage::disk('public')->exists('uploads/' . $file->filename);
    }

    /**
     * Check if file is an image that can have thumbnails.
     */
    private function isImageFile(FileUpload $file): bool
    {
        $imageTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/tiff'
        ];

        return in_array($file->mime_type, $imageTypes);
    }

    /**
     * Create HTTP response for thumbnail.
     */
    private function createThumbnailResponse(string $content, string $mimeType): Response
    {
        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => strlen($content),
            'Cache-Control' => 'public, max-age=86400', // Cache for 24 hours
            'Expires' => gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT',
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT'
        ]);
    }

    /**
     * Invalidate thumbnail cache for a file.
     */
    public function invalidateThumbnailCache(FileUpload $file): void
    {
        $cacheKey = self::CACHE_PREFIX . $file->id;
        Cache::forget($cacheKey);

        Log::debug('Thumbnail cache invalidated', ['file_id' => $file->id]);
    }

    /**
     * Warm up thumbnail cache for recently uploaded images.
     */
    public function warmUpThumbnailCache(int $limit = 50): int
    {
        $recentImages = FileUpload::where('mime_type', 'like', 'image/%')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $warmedCount = 0;
        $adminUser = User::where('role', \App\Enums\UserRole::ADMIN)->first();

        if (!$adminUser) {
            Log::warning('No admin user found for thumbnail cache warm-up');
            return 0;
        }

        foreach ($recentImages as $file) {
            try {
                $this->getThumbnail($file, $adminUser);
                $warmedCount++;
            } catch (\Exception $e) {
                Log::warning('Failed to warm up thumbnail cache', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Thumbnail cache warmed up', ['thumbnails_cached' => $warmedCount]);

        return $warmedCount;
    }

    /**
     * Clear all thumbnail caches.
     */
    public function clearAllThumbnailCaches(): int
    {
        $cleared = 0;

        // Get all file IDs to clear their thumbnail caches
        $fileIds = FileUpload::where('mime_type', 'like', 'image/%')->pluck('id');

        foreach ($fileIds as $fileId) {
            $cacheKey = self::CACHE_PREFIX . $fileId;
            if (Cache::forget($cacheKey)) {
                $cleared++;
            }
        }

        Log::info('All thumbnail caches cleared', ['cleared_count' => $cleared]);

        return $cleared;
    }
}
