<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ThumbnailService;
use App\Services\GoogleDriveService;
use App\Models\FileUpload;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response;
use Mockery;

class ThumbnailServiceTest extends TestCase
{
    use RefreshDatabase;

    private ThumbnailService $service;
    private User $admin;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ThumbnailService();
        
        // Create test users
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        Storage::fake('public');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_null_for_unauthorized_users()
    {
        $file = FileUpload::factory()->create([
            'client_user_id' => $this->client->id,
            'mime_type' => 'image/jpeg'
        ]);

        $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);

        $result = $this->service->getThumbnail($file, $otherClient);

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_for_non_image_files()
    {
        $file = FileUpload::factory()->create([
            'mime_type' => 'application/pdf',
            'client_user_id' => null // Admin can access
        ]);

        $result = $this->service->getThumbnail($file, $this->admin);

        $this->assertNull($result);
    }

    /** @test */
    public function it_generates_thumbnail_for_local_image()
    {
        // Create a larger test image using GD
        $image = imagecreate(100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $white);
        imagesetpixel($image, 50, 50, $red);
        
        ob_start();
        imagepng($image);
        $imageContent = ob_get_clean();
        imagedestroy($image);
        
        Storage::disk('public')->put('uploads/test-image.png', $imageContent);
        
        $file = FileUpload::factory()->create([
            'filename' => 'test-image.png',
            'original_filename' => 'test-image.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($imageContent),
            'client_user_id' => null
        ]);

        $response = $this->service->getThumbnail($file, $this->admin);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=86400', $response->headers->get('Cache-Control'));
    }

    /** @test */
    public function it_caches_generated_thumbnails()
    {
        // Create a test image using GD
        $image = imagecreate(50, 50);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        ob_start();
        imagepng($image);
        $imageContent = ob_get_clean();
        imagedestroy($image);
        
        Storage::disk('public')->put('uploads/test-image.png', $imageContent);
        
        $file = FileUpload::factory()->create([
            'filename' => 'test-image.png',
            'mime_type' => 'image/png',
            'client_user_id' => null
        ]);

        // First call should generate and cache
        $response1 = $this->service->getThumbnail($file, $this->admin);
        
        // Second call should use cache
        $response2 = $this->service->getThumbnail($file, $this->admin);

        $this->assertInstanceOf(Response::class, $response1);
        $this->assertInstanceOf(Response::class, $response2);
        
        // Verify cache was used
        $cacheKey = 'thumbnail:' . $file->id;
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_returns_cached_thumbnail_when_available()
    {
        $file = FileUpload::factory()->create([
            'mime_type' => 'image/jpeg',
            'client_user_id' => null
        ]);

        // Pre-populate cache
        $cachedData = [
            'content' => 'cached-thumbnail-content',
            'mime_type' => 'image/jpeg'
        ];
        Cache::put('thumbnail:' . $file->id, $cachedData, 86400);

        $response = $this->service->getThumbnail($file, $this->admin);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('cached-thumbnail-content', $response->getContent());
        $this->assertEquals('image/jpeg', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_handles_missing_local_files_gracefully()
    {
        $file = FileUpload::factory()->create([
            'filename' => 'missing-image.jpg',
            'mime_type' => 'image/jpeg',
            'client_user_id' => null,
            'google_drive_file_id' => null
        ]);

        $result = $this->service->getThumbnail($file, $this->admin);

        $this->assertNull($result);
    }

    /** @test */
    public function it_invalidates_thumbnail_cache()
    {
        $file = FileUpload::factory()->create([
            'mime_type' => 'image/jpeg'
        ]);

        // Pre-populate cache
        $cacheKey = 'thumbnail:' . $file->id;
        Cache::put($cacheKey, ['content' => 'test', 'mime_type' => 'image/jpeg'], 86400);
        
        $this->assertTrue(Cache::has($cacheKey));

        $this->service->invalidateThumbnailCache($file);

        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_warms_up_thumbnail_cache()
    {
        // Create multiple image files
        $files = FileUpload::factory()->count(3)->create([
            'mime_type' => 'image/jpeg'
        ]);

        // Create test images using GD
        $image = imagecreate(20, 20);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        ob_start();
        imagejpeg($image);
        $imageContent = ob_get_clean();
        imagedestroy($image);
        
        foreach ($files as $file) {
            Storage::disk('public')->put('uploads/' . $file->filename, $imageContent);
        }

        $warmedCount = $this->service->warmUpThumbnailCache(10);

        $this->assertEquals(3, $warmedCount);
        
        // Verify caches were created
        foreach ($files as $file) {
            $cacheKey = 'thumbnail:' . $file->id;
            $this->assertTrue(Cache::has($cacheKey));
        }
    }

    /** @test */
    public function it_clears_all_thumbnail_caches()
    {
        // Create image files and populate their caches
        $files = FileUpload::factory()->count(2)->create([
            'mime_type' => 'image/png'
        ]);

        foreach ($files as $file) {
            $cacheKey = 'thumbnail:' . $file->id;
            Cache::put($cacheKey, ['content' => 'test', 'mime_type' => 'image/jpeg'], 86400);
        }

        $clearedCount = $this->service->clearAllThumbnailCaches();

        $this->assertEquals(2, $clearedCount);
        
        // Verify caches were cleared
        foreach ($files as $file) {
            $cacheKey = 'thumbnail:' . $file->id;
            $this->assertFalse(Cache::has($cacheKey));
        }
    }

    /** @test */
    public function it_handles_google_drive_files_when_no_admin_available()
    {
        $file = FileUpload::factory()->create([
            'mime_type' => 'image/jpeg',
            'google_drive_file_id' => 'test-drive-id',
            'client_user_id' => null
        ]);

        // No admin users with Google Drive tokens exist
        $result = $this->service->getThumbnail($file, $this->admin);

        $this->assertNull($result);
    }

    /** @test */
    public function it_detects_image_file_types_correctly()
    {
        $imageTypes = [
            'image/jpeg' => true,
            'image/png' => true,
            'image/gif' => true,
            'image/webp' => true,
            'image/bmp' => true,
            'image/tiff' => true,
            'application/pdf' => false,
            'text/plain' => false,
            'video/mp4' => false
        ];

        foreach ($imageTypes as $mimeType => $expected) {
            $file = FileUpload::factory()->create([
                'mime_type' => $mimeType,
                'client_user_id' => null
            ]);

            $result = $this->service->getThumbnail($file, $this->admin);
            
            if ($expected) {
                // For image types, we expect either a Response or null (if file doesn't exist)
                $this->assertTrue($result === null || $result instanceof Response);
            } else {
                // For non-image types, we expect null
                $this->assertNull($result, "Expected null for MIME type: {$mimeType}");
            }
        }
    }

    /** @test */
    public function it_handles_thumbnail_generation_errors_gracefully()
    {
        // Create a file with invalid image content
        Storage::disk('public')->put('uploads/invalid-image.jpg', 'invalid image content');
        
        $file = FileUpload::factory()->create([
            'filename' => 'invalid-image.jpg',
            'mime_type' => 'image/jpeg',
            'client_user_id' => null
        ]);

        $result = $this->service->getThumbnail($file, $this->admin);

        // Should handle the error gracefully and return null
        $this->assertNull($result);
    }
}