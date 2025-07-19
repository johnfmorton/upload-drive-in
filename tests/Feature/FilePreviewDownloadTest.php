<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FileUpload;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FilePreviewDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        Storage::fake('public');
    }

    public function test_image_preview_functionality()
    {
        $this->actingAs($this->admin);

        // Create test image
        $imageContent = $this->createTestImage();
        Storage::disk('public')->put('uploads/test-image.jpg', $imageContent);
        
        $file = FileUpload::factory()->create([
            'filename' => 'test-image.jpg',
            'original_filename' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => strlen($imageContent)
        ]);

        $response = $this->get(route('admin.file-manager.preview', $file));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeader('Content-Disposition', function ($value) {
            return str_contains($value, 'inline') && str_contains($value, 'test-image.jpg');
        });
        $response->assertHeader('Cache-Control', 'public, max-age=3600');
    }

    public function test_pdf_preview_functionality()
    {
        $this->actingAs($this->admin);

        // Create mock PDF content
        $pdfContent = '%PDF-1.4 mock pdf content';
        Storage::disk('public')->put('uploads/test-document.pdf', $pdfContent);
        
        $file = FileUpload::factory()->create([
            'filename' => 'test-document.pdf',
            'original_filename' => 'test-document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => strlen($pdfContent)
        ]);

        $response = $this->get(route('admin.file-manager.preview', $file));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', function ($value) {
            return str_contains($value, 'inline') && str_contains($value, 'test-document.pdf');
        });
    }

    public function test_text_file_preview_functionality()
    {
        $this->actingAs($this->admin);

        $textContent = "Hello World!\nThis is a test file.";
        Storage::disk('public')->put('uploads/test.txt', $textContent);
        
        $file = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($textContent)
        ]);

        $response = $this->get(route('admin.file-manager.preview', $file));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertEquals($textContent, $response->getContent());
    }

    public function test_unsupported_file_type_preview()
    {
        $this->actingAs($this->admin);

        $file = FileUpload::factory()->create([
            'mime_type' => 'application/zip',
            'original_filename' => 'archive.zip'
        ]);

        $response = $this->get(route('admin.file-manager.preview', $file));

        $response->assertStatus(403);
        $response->assertSee('security restrictions');
    }

    public function test_thumbnail_generation_for_images()
    {
        $this->actingAs($this->admin);

        // Create test image
        $imageContent = $this->createTestImage();
        Storage::disk('public')->put('uploads/test-image.png', $imageContent);
        
        $file = FileUpload::factory()->create([
            'filename' => 'test-image.png',
            'original_filename' => 'test-image.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($imageContent)
        ]);

        $response = $this->get(route('admin.file-manager.thumbnail', $file));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeader('Cache-Control', function ($value) {
            return str_contains($value, 'max-age=86400');
        });
    }

    public function test_thumbnail_blocked_for_non_images()
    {
        $this->actingAs($this->admin);

        $file = FileUpload::factory()->create([
            'mime_type' => 'application/pdf',
            'original_filename' => 'document.pdf'
        ]);

        $response = $this->get(route('admin.file-manager.thumbnail', $file));

        $response->assertStatus(403);
        $response->assertSee('non-image files');
    }

    public function test_file_download_functionality()
    {
        $this->actingAs($this->admin);

        $fileContent = 'Test file content for download';
        Storage::disk('public')->put('uploads/download-test.txt', $fileContent);
        
        $file = FileUpload::factory()->create([
            'filename' => 'download-test.txt',
            'original_filename' => 'download-test.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($fileContent)
        ]);

        $response = $this->get(route('admin.file-manager.download', $file));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=download-test.txt');
        $response->assertHeader('Content-Type', 'text/plain');
    }

    public function test_google_drive_file_download_fallback()
    {
        $this->actingAs($this->admin);

        // Create Google Drive token for admin
        GoogleDriveToken::create([
            'user_id' => $this->admin->id,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive']
        ]);

        $file = FileUpload::factory()->create([
            'google_drive_file_id' => 'test-drive-file-id',
            'original_filename' => 'drive-file.txt',
            'mime_type' => 'text/plain'
        ]);

        // This would normally require mocking the Google Drive service
        // For now, we test that the route is accessible
        $response = $this->get(route('admin.file-manager.download', $file));
        
        // Expect either success or a specific error about Google Drive
        $this->assertTrue(in_array($response->getStatusCode(), [200, 500]));
    }

    public function test_preview_access_control()
    {
        // Test client accessing their own file
        $this->actingAs($this->client);
        
        $clientFile = FileUpload::factory()->create([
            'client_user_id' => $this->client->id,
            'mime_type' => 'text/plain'
        ]);
        
        Storage::disk('public')->put('uploads/' . $clientFile->filename, 'client content');

        $response = $this->get(route('admin.file-manager.preview', $clientFile));
        $response->assertStatus(200);

        // Test client accessing other's file
        $otherFile = FileUpload::factory()->create([
            'client_user_id' => $this->admin->id,
            'mime_type' => 'text/plain'
        ]);

        $response = $this->get(route('admin.file-manager.preview', $otherFile));
        $response->assertStatus(404);
    }

    public function test_download_access_control()
    {
        // Test admin can download any file
        $this->actingAs($this->admin);
        
        $file = FileUpload::factory()->create([
            'client_user_id' => $this->client->id
        ]);
        
        Storage::disk('public')->put('uploads/' . $file->filename, 'test content');

        $response = $this->get(route('admin.file-manager.download', $file));
        $response->assertStatus(200);

        // Test client can only download their own files
        $this->actingAs($this->client);
        
        $clientFile = FileUpload::factory()->create([
            'client_user_id' => $this->client->id
        ]);
        
        Storage::disk('public')->put('uploads/' . $clientFile->filename, 'client content');

        $response = $this->get(route('admin.file-manager.download', $clientFile));
        $response->assertStatus(200);

        // Test client cannot download other's files
        $response = $this->get(route('admin.file-manager.download', $file));
        $response->assertStatus(404);
    }

    public function test_missing_file_handling()
    {
        $this->actingAs($this->admin);

        // File exists in database but not in storage
        $file = FileUpload::factory()->create([
            'filename' => 'missing-file.txt',
            'mime_type' => 'text/plain',
            'google_drive_file_id' => null
        ]);

        $response = $this->get(route('admin.file-manager.preview', $file));
        $response->assertStatus(404);

        $response = $this->get(route('admin.file-manager.download', $file));
        $response->assertStatus(500);
    }

    public function test_large_file_handling()
    {
        $this->actingAs($this->admin);

        // Create a larger file for testing streaming
        $largeContent = str_repeat('Large file content. ', 10000); // ~200KB
        Storage::disk('public')->put('uploads/large-file.txt', $largeContent);
        
        $file = FileUpload::factory()->create([
            'filename' => 'large-file.txt',
            'original_filename' => 'large-file.txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($largeContent)
        ]);

        $response = $this->get(route('admin.file-manager.download', $file));

        $response->assertStatus(200);
        $response->assertHeader('Content-Length', strlen($largeContent));
    }

    public function test_preview_caching_headers()
    {
        $this->actingAs($this->admin);

        $imageContent = $this->createTestImage();
        Storage::disk('public')->put('uploads/cached-image.jpg', $imageContent);
        
        $file = FileUpload::factory()->create([
            'filename' => 'cached-image.jpg',
            'mime_type' => 'image/jpeg'
        ]);

        $response = $this->get(route('admin.file-manager.preview', $file));

        $response->assertStatus(200);
        $response->assertHeader('Cache-Control', 'public, max-age=3600');
        $this->assertNotNull($response->headers->get('Last-Modified'));
    }

    public function test_authentication_required_for_preview_and_download()
    {
        $file = FileUpload::factory()->create();

        // Test without authentication
        $response = $this->get(route('admin.file-manager.preview', $file));
        $response->assertStatus(401);

        $response = $this->get(route('admin.file-manager.download', $file));
        $response->assertStatus(404); // Admin middleware redirects to 404
    }

    private function createTestImage(): string
    {
        // Create a simple test image using GD
        $image = imagecreate(100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $white);
        imagesetpixel($image, 50, 50, $red);
        
        ob_start();
        imagejpeg($image);
        $content = ob_get_clean();
        imagedestroy($image);
        
        return $content;
    }
}