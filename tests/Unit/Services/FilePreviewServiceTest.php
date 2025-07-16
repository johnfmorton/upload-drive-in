<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FilePreviewService;
use App\Services\GoogleDriveService;
use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Mockery;

class FilePreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    private FilePreviewService $service;
    private GoogleDriveService $mockGoogleDriveService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockGoogleDriveService = Mockery::mock(GoogleDriveService::class);
        $this->service = new FilePreviewService($this->mockGoogleDriveService);
        
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_detect_previewable_mime_types()
    {
        // Image types
        $this->assertTrue($this->service->canPreview('image/jpeg'));
        $this->assertTrue($this->service->canPreview('image/png'));
        $this->assertTrue($this->service->canPreview('image/gif'));
        $this->assertTrue($this->service->canPreview('image/webp'));
        $this->assertTrue($this->service->canPreview('image/svg+xml'));

        // PDF
        $this->assertTrue($this->service->canPreview('application/pdf'));

        // Text files
        $this->assertTrue($this->service->canPreview('text/plain'));
        $this->assertTrue($this->service->canPreview('text/html'));
        $this->assertTrue($this->service->canPreview('text/css'));
        $this->assertTrue($this->service->canPreview('text/javascript'));
        $this->assertTrue($this->service->canPreview('application/json'));
        $this->assertTrue($this->service->canPreview('application/xml'));

        // Code files
        $this->assertTrue($this->service->canPreview('text/x-php'));
        $this->assertTrue($this->service->canPreview('text/x-python'));
        $this->assertTrue($this->service->canPreview('text/markdown'));

        // Non-previewable types
        $this->assertFalse($this->service->canPreview('application/zip'));
        $this->assertFalse($this->service->canPreview('video/mp4'));
        $this->assertFalse($this->service->canPreview('audio/mp3'));
        $this->assertFalse($this->service->canPreview('application/msword'));
    }

    /** @test */
    public function it_can_detect_thumbnailable_mime_types()
    {
        // Thumbnailable image types
        $this->assertTrue($this->service->canGenerateThumbnail('image/jpeg'));
        $this->assertTrue($this->service->canGenerateThumbnail('image/png'));
        $this->assertTrue($this->service->canGenerateThumbnail('image/gif'));
        $this->assertTrue($this->service->canGenerateThumbnail('image/webp'));
        $this->assertTrue($this->service->canGenerateThumbnail('image/bmp'));

        // Non-thumbnailable types
        $this->assertFalse($this->service->canGenerateThumbnail('image/svg+xml'));
        $this->assertFalse($this->service->canGenerateThumbnail('application/pdf'));
        $this->assertFalse($this->service->canGenerateThumbnail('text/plain'));
        $this->assertFalse($this->service->canGenerateThumbnail('application/zip'));
    }

    /** @test */
    public function it_generates_preview_for_local_image_file()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create a simple test image content (1x1 PNG)
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU8j8wAAAABJRU5ErkJggg==');
        
        // Store test file
        Storage::disk('public')->put('uploads/test-image.png', $imageContent);
        
        // Create file upload record
        $file = FileUpload::factory()->create([
            'filename' => 'test-image.png',
            'original_filename' => 'test-image.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($imageContent),
            'client_user_id' => null, // Admin can access all files
        ]);

        $response = $this->service->generatePreview($file, $admin);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    /** @test */
    public function it_generates_preview_for_local_text_file()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $textContent = "Hello, World!\nThis is a test file.";
        
        // Store test file
        Storage::disk('public')->put('uploads/test.txt', $textContent);
        
        // Create file upload record
        $file = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($textContent),
            'client_user_id' => null,
        ]);

        $response = $this->service->generatePreview($file, $admin);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertEquals($textContent, $response->getContent());
    }

    /** @test */
    public function it_generates_html_preview_for_image()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create a simple test image content
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChAI9jU8j8wAAAABJRU5ErkJggg==');
        
        // Store test file
        Storage::disk('public')->put('uploads/test-image.png', $imageContent);
        
        // Create file upload record
        $file = FileUpload::factory()->create([
            'filename' => 'test-image.png',
            'original_filename' => 'test-image.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($imageContent),
            'client_user_id' => null,
        ]);

        $html = $this->service->getPreviewHtml($file, $admin);

        $this->assertStringContainsString('<img src="data:image/png;base64,', $html);
        $this->assertStringContainsString('alt="test-image.png"', $html);
        $this->assertStringContainsString('max-width: 100%', $html);
    }

    /** @test */
    public function it_generates_html_preview_for_text_file()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $textContent = "<?php\necho 'Hello, World!';";
        
        // Store test file
        Storage::disk('public')->put('uploads/test.php', $textContent);
        
        // Create file upload record
        $file = FileUpload::factory()->create([
            'filename' => 'test.php',
            'original_filename' => 'test.php',
            'mime_type' => 'text/x-php',
            'file_size' => strlen($textContent),
            'client_user_id' => null,
        ]);

        $html = $this->service->getPreviewHtml($file, $admin);

        $this->assertStringContainsString('<pre><code class="language-php">', $html);
        $this->assertStringContainsString('&lt;?php', $html); // HTML escaped
        $this->assertStringContainsString('echo &#039;Hello, World!&#039;;', $html);
    }

    /** @test */
    public function it_returns_unsupported_preview_for_non_previewable_files()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create file upload record for unsupported type
        $file = FileUpload::factory()->create([
            'filename' => 'test.zip',
            'original_filename' => 'test.zip',
            'mime_type' => 'application/zip',
            'file_size' => 1024,
            'client_user_id' => null,
        ]);

        $html = $this->service->getPreviewHtml($file, $admin);

        $this->assertStringContainsString('preview-unsupported', $html);
        $this->assertStringContainsString('test.zip', $html);
        $this->assertStringContainsString('application/zip', $html);
        $this->assertStringContainsString('This file type cannot be previewed', $html);
        $this->assertStringContainsString('Download File', $html);
    }

    /** @test */
    public function it_generates_thumbnail_for_image()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create a simple 10x10 PNG image using GD
        $image = imagecreate(10, 10);
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $white);
        imagesetpixel($image, 5, 5, $red);
        
        ob_start();
        imagepng($image);
        $imageContent = ob_get_clean();
        imagedestroy($image);
        
        // Store test file
        Storage::disk('public')->put('uploads/test-image.png', $imageContent);
        
        // Create file upload record
        $file = FileUpload::factory()->create([
            'filename' => 'test-image.png',
            'original_filename' => 'test-image.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($imageContent),
            'client_user_id' => null,
        ]);

        $response = $this->service->getThumbnail($file, $admin, 100, 100);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('thumb_test-image.png', $response->headers->get('Content-Disposition'));
    }

    /** @test */
    public function it_returns_null_thumbnail_for_non_image_files()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create file upload record for non-image type
        $file = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'client_user_id' => null,
        ]);

        $response = $this->service->getThumbnail($file, $admin);

        $this->assertNull($response);
    }

    /** @test */
    public function it_denies_access_to_unauthorized_users()
    {
        // Create test users
        $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create file upload record owned by client1
        $file = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'client_user_id' => $client1->id,
        ]);

        // Client2 should not be able to access client1's file
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access denied to this file.');

        $this->service->generatePreview($file, $client2);
    }

    /** @test */
    public function it_handles_missing_local_files_gracefully()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create file upload record but don't store the actual file
        $file = FileUpload::factory()->create([
            'filename' => 'missing-file.txt',
            'original_filename' => 'missing-file.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'client_user_id' => null,
            'google_drive_file_id' => null, // No Google Drive backup either
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File content could not be retrieved.');

        $this->service->generatePreview($file, $admin);
    }

    /** @test */
    public function it_returns_error_html_for_missing_files()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create file upload record but don't store the actual file
        $file = FileUpload::factory()->create([
            'filename' => 'missing-file.txt',
            'original_filename' => 'missing-file.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'client_user_id' => null,
            'google_drive_file_id' => null,
        ]);

        $html = $this->service->getPreviewHtml($file, $admin);

        $this->assertStringContainsString('preview-error', $html);
        $this->assertStringContainsString('Preview Error', $html);
        $this->assertStringContainsString('File content could not be retrieved', $html);
    }

    /** @test */
    public function it_throws_exception_for_unsupported_file_types_in_preview()
    {
        // Create test users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create file upload record for unsupported type
        $file = FileUpload::factory()->create([
            'filename' => 'test.zip',
            'original_filename' => 'test.zip',
            'mime_type' => 'application/zip',
            'file_size' => 1024,
            'client_user_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File type not supported for preview.');

        $this->service->generatePreview($file, $admin);
    }
}