<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

/**
 * Service class for handling file preview operations.
 * Provides MIME type detection, preview generation, and thumbnail creation.
 */
class FilePreviewService
{
    /**
     * @var GoogleDriveService
     */
    private GoogleDriveService $googleDriveService;

    /**
     * @var array Supported MIME types for preview
     */
    private array $previewableMimeTypes = [
        // Images
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',
        'image/tiff',
        // PDFs
        'application/pdf',
        // Text files
        'text/plain',
        'text/html',
        'text/css',
        'text/javascript',
        'text/csv',
        'application/json',
        'application/xml',
        'text/xml',
        'application/javascript',
        // Code files
        'text/x-php',
        'text/x-python',
        'text/x-java',
        'text/x-c',
        'text/x-cpp',
        'text/x-csharp',
        'text/x-ruby',
        'text/x-go',
        'text/x-rust',
        'text/x-sql',
        'text/x-yaml',
        'application/x-yaml',
        'text/markdown',
    ];

    /**
     * @var array MIME types that support thumbnail generation
     */
    private array $thumbnailableMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',
        'image/tiff',
    ];

    public function __construct(
        GoogleDriveService $googleDriveService,
        private ThumbnailService $thumbnailService
    ) {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Check if a file can be previewed based on its MIME type.
     *
     * @param string $mimeType The MIME type to check
     * @return bool True if the file can be previewed
     */
    public function canPreview(string $mimeType): bool
    {
        return in_array($mimeType, $this->previewableMimeTypes);
    }

    /**
     * Check if a file supports thumbnail generation.
     *
     * @param string $mimeType The MIME type to check
     * @return bool True if thumbnails can be generated
     */
    public function canGenerateThumbnail(string $mimeType): bool
    {
        return in_array($mimeType, $this->thumbnailableMimeTypes);
    }

    /**
     * Generate a preview response for a file.
     *
     * @param FileUpload $file The file to preview
     * @param User $user The user requesting the preview
     * @return Response The preview response
     * @throws Exception If preview generation fails
     */
    public function generatePreview(FileUpload $file, User $user): Response
    {
        // Check if user can access this file
        if (!$file->canBeAccessedBy($user)) {
            throw new Exception('Access denied to this file.');
        }

        // Check if file can be previewed
        if (!$this->canPreview($file->mime_type)) {
            throw new Exception('File type not supported for preview.');
        }

        try {
            $fileContent = $this->getFileContent($file, $user);

            if ($fileContent === null) {
                throw new Exception('File content could not be retrieved.');
            }

            return $this->createPreviewResponse($file, $fileContent);
        } catch (Exception $e) {
            Log::error('Failed to generate file preview', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate HTML preview content for a file.
     *
     * @param FileUpload $file The file to preview
     * @param User $user The user requesting the preview
     * @return string HTML content for preview
     * @throws Exception If preview generation fails
     */
    public function getPreviewHtml(FileUpload $file, User $user): string
    {
        // Check if user can access this file
        if (!$file->canBeAccessedBy($user)) {
            throw new Exception('Access denied to this file.');
        }

        // Check if file can be previewed
        if (!$this->canPreview($file->mime_type)) {
            return $this->getUnsupportedPreviewHtml($file);
        }

        try {
            $fileContent = $this->getFileContent($file, $user);

            if ($fileContent === null) {
                return $this->getErrorPreviewHtml('File content could not be retrieved.');
            }

            return $this->generateHtmlPreview($file, $fileContent);
        } catch (Exception $e) {
            Log::error('Failed to generate HTML preview', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return $this->getErrorPreviewHtml($e->getMessage());
        }
    }

    /**
     * Generate a thumbnail for an image file.
     *
     * @param FileUpload $file The file to create thumbnail for
     * @param User $user The user requesting the thumbnail
     * @param int $width Thumbnail width (default: 150)
     * @param int $height Thumbnail height (default: 150)
     * @return Response|null The thumbnail response or null if not possible
     */
    public function getThumbnail(FileUpload $file, User $user, int $width = 150, int $height = 150): ?Response
    {
        // Check if user can access this file
        if (!$file->canBeAccessedBy($user)) {
            return null;
        }

        // Check if file supports thumbnails
        if (!$this->canGenerateThumbnail($file->mime_type)) {
            return null;
        }

        try {
            $fileContent = $this->getFileContent($file, $user);

            if ($fileContent === null) {
                return null;
            }

            return $this->createThumbnailResponse($file, $fileContent, $width, $height);
        } catch (Exception $e) {
            Log::error('Failed to generate thumbnail', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get file content from local storage or Google Drive.
     *
     * @param FileUpload $file The file to retrieve
     * @param User $user The user requesting the file
     * @return string|null The file content or null if not available
     * @throws Exception If file retrieval fails
     */
    private function getFileContent(FileUpload $file, User $user): ?string
    {
        // Try local file first
        if ($this->hasLocalFile($file)) {
            return Storage::disk('public')->get('uploads/' . $file->filename);
        }

        // Try Google Drive if file has Google Drive ID
        if ($file->google_drive_file_id) {
            return $this->getGoogleDriveFileContent($file, $user);
        }

        return null;
    }

    /**
     * Get file content from Google Drive.
     *
     * @param FileUpload $file The file to retrieve
     * @param User $user The user requesting the file
     * @return string|null The file content or null if not available
     * @throws Exception If Google Drive retrieval fails
     */
    private function getGoogleDriveFileContent(FileUpload $file, User $user): ?string
    {
        try {
            // Find a user with Google Drive access
            $driveUser = $this->findUserWithGoogleDriveAccess($user);

            if (!$driveUser) {
                throw new Exception('No Google Drive access available.');
            }

            $service = $this->googleDriveService->getDriveService($driveUser);
            $response = $service->files->get($file->google_drive_file_id, ['alt' => 'media']);

            return $response->getBody();
        } catch (Exception $e) {
            Log::error('Failed to retrieve file from Google Drive', [
                'file_id' => $file->id,
                'google_drive_file_id' => $file->google_drive_file_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a preview response based on file type.
     *
     * @param FileUpload $file The file being previewed
     * @param string $content The file content
     * @return Response The preview response
     */
    private function createPreviewResponse(FileUpload $file, string $content): Response
    {
        $mimeType = $file->mime_type;

        // Generate a unique ETag based on file ID and size to prevent cache mix-ups
        $etag = md5($file->id . '_' . $file->file_size . '_' . $file->updated_at->timestamp);

        // For images, return the image directly
        if (str_starts_with($mimeType, 'image/')) {
            return response($content, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $file->original_filename . '"',
                'Cache-Control' => 'public, max-age=3600',
                'ETag' => '"' . $etag . '"',
                'Vary' => 'Accept-Encoding',
            ]);
        }

        // For PDFs, return the PDF directly
        if ($mimeType === 'application/pdf') {
            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $file->original_filename . '"',
                'Cache-Control' => 'public, max-age=3600',
                'ETag' => '"' . $etag . '"',
                'Vary' => 'Accept-Encoding',
            ]);
        }

        // For text files, return as plain text
        if (str_starts_with($mimeType, 'text/') || in_array($mimeType, ['application/json', 'application/xml'])) {
            return response($content, 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Content-Disposition' => 'inline; filename="' . $file->original_filename . '"',
                'Cache-Control' => 'public, max-age=3600',
                'ETag' => '"' . $etag . '"',
                'Vary' => 'Accept-Encoding',
            ]);
        }

        // Fallback for other supported types
        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $file->original_filename . '"',
            'Cache-Control' => 'public, max-age=3600',
            'ETag' => '"' . $etag . '"',
            'Vary' => 'Accept-Encoding',
        ]);
    }

    /**
     * Generate HTML preview content based on file type.
     *
     * @param FileUpload $file The file being previewed
     * @param string $content The file content
     * @return string HTML preview content
     */
    private function generateHtmlPreview(FileUpload $file, string $content): string
    {
        $mimeType = $file->mime_type;

        // For images
        if (str_starts_with($mimeType, 'image/')) {
            $base64 = base64_encode($content);
            return "<img src=\"data:{$mimeType};base64,{$base64}\" alt=\"{$file->original_filename}\" style=\"max-width: 100%; height: auto;\" />";
        }

        // For PDFs
        if ($mimeType === 'application/pdf') {
            $base64 = base64_encode($content);
            return "<embed src=\"data:application/pdf;base64,{$base64}\" type=\"application/pdf\" width=\"100%\" height=\"600px\" />";
        }

        // For text files
        if (str_starts_with($mimeType, 'text/') || in_array($mimeType, ['application/json', 'application/xml'])) {
            $escapedContent = htmlspecialchars($content);
            $language = $this->detectLanguageFromMimeType($mimeType);

            return "<pre><code class=\"language-{$language}\">{$escapedContent}</code></pre>";
        }

        return $this->getUnsupportedPreviewHtml($file);
    }

    /**
     * Create a thumbnail response for an image.
     *
     * @param FileUpload $file The image file
     * @param string $content The image content
     * @param int $width Thumbnail width
     * @param int $height Thumbnail height
     * @return Response The thumbnail response
     * @throws Exception If thumbnail creation fails
     */
    private function createThumbnailResponse(FileUpload $file, string $content, int $width, int $height): Response
    {
        try {
            // For SVG files, return them directly without processing
            if ($file->mime_type === 'image/svg+xml') {
                // Include mime type in ETag to bust caches when encoding strategy changes
                $etag = md5($file->id . '_' . $file->file_size . '_' . $width . 'x' . $height . '_' . $file->updated_at->timestamp . '_image/svg+xml');
                return response($content, 200, [
                    'Content-Type' => 'image/svg+xml',
                    'Content-Disposition' => 'inline; filename="' . $file->original_filename . '"',
                    'Cache-Control' => 'public, max-age=86400', // Cache for 24 hours
                    'ETag' => '"' . $etag . '"',
                    'Vary' => 'Accept-Encoding',
                ]);
            }

            // Create image manager with GD driver
            $manager = new ImageManager(new Driver());

            // Create image from content
            $image = $manager->read($content);

            // Resize to thumbnail size while maintaining aspect ratio
            $image->scale(width: $width, height: $height);

            // Preserve transparency for alpha-capable formats
            $supportsAlpha = in_array(strtolower($file->mime_type), [
                'image/png', 'image/webp', 'image/gif'
            ]);

            if ($supportsAlpha) {
                $thumbnailBinary = $image->toPng()->toString();
                $contentType = 'image/png';
            } else {
                $thumbnailBinary = $image->toJpeg(quality: 85)->toString();
                $contentType = 'image/jpeg';
            }

            // Generate a unique ETag (include mime to invalidate old JPEG caches)
            $etag = md5($file->id . '_' . $file->file_size . '_' . $width . 'x' . $height . '_' . $file->updated_at->timestamp . '_' . $contentType);

            return response($thumbnailBinary, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="thumb_' . $file->original_filename . '"',
                'Cache-Control' => 'public, max-age=86400', // Cache for 24 hours
                'ETag' => '"' . $etag . '"',
                'Vary' => 'Accept-Encoding',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create thumbnail', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if file exists in local storage.
     *
     * @param FileUpload $file The file to check
     * @return bool True if file exists locally
     */
    private function hasLocalFile(FileUpload $file): bool
    {
        return Storage::disk('public')->exists('uploads/' . $file->filename);
    }

    /**
     * Find a user with Google Drive access for file operations.
     *
     * @param User $requestingUser The user requesting access
     * @return User|null User with Google Drive access or null
     */
    private function findUserWithGoogleDriveAccess(User $requestingUser): ?User
    {
        // First try the requesting user if they have Google Drive connected
        if ($requestingUser->hasGoogleDriveConnected()) {
            return $requestingUser;
        }

        // Try the user who uploaded the file
        if ($requestingUser->uploadedByUser && $requestingUser->uploadedByUser->hasGoogleDriveConnected()) {
            return $requestingUser->uploadedByUser;
        }

        // Fallback to any admin user with Google Drive connected
        return User::where('role', \App\Enums\UserRole::ADMIN)
            ->whereHas('googleDriveToken')
            ->first();
    }

    /**
     * Detect programming language from MIME type for syntax highlighting.
     *
     * @param string $mimeType The MIME type
     * @return string Language identifier for syntax highlighting
     */
    private function detectLanguageFromMimeType(string $mimeType): string
    {
        return match($mimeType) {
            'text/x-php' => 'php',
            'text/x-python' => 'python',
            'text/x-java' => 'java',
            'text/x-c' => 'c',
            'text/x-cpp' => 'cpp',
            'text/x-csharp' => 'csharp',
            'text/x-ruby' => 'ruby',
            'text/x-go' => 'go',
            'text/x-rust' => 'rust',
            'text/x-sql' => 'sql',
            'text/x-yaml', 'application/x-yaml' => 'yaml',
            'text/markdown' => 'markdown',
            'text/html' => 'html',
            'text/css' => 'css',
            'text/javascript', 'application/javascript' => 'javascript',
            'application/json' => 'json',
            'application/xml', 'text/xml' => 'xml',
            'text/csv' => 'csv',
            default => 'text'
        };
    }

    /**
     * Generate HTML for unsupported file types.
     *
     * @param FileUpload $file The file
     * @return string HTML content
     */
    private function getUnsupportedPreviewHtml(FileUpload $file): string
    {
        return "
            <div class=\"preview-unsupported\">
                <div class=\"file-icon\">📄</div>
                <h3>{$file->original_filename}</h3>
                <p>File type: {$file->mime_type}</p>
                <p>Size: {$file->getHumanFileSize()}</p>
                <p>This file type cannot be previewed. Please download the file to view its contents.</p>
                <a href=\"{$file->getDownloadUrl()}\" class=\"btn btn-primary\">Download File</a>
            </div>
        ";
    }

    /**
     * Generate HTML for preview errors.
     *
     * @param string $error The error message
     * @return string HTML content
     */
    private function getErrorPreviewHtml(string $error): string
    {
        return "
            <div class=\"preview-error\">
                <div class=\"error-icon\">⚠️</div>
                <h3>Preview Error</h3>
                <p>{$error}</p>
                <p>Please try downloading the file instead.</p>
            </div>
        ";
    }
}
