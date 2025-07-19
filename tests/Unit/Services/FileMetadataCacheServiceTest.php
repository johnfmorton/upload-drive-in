<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileMetadataCacheService;
use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FileMetadataCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private FileMetadataCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new FileMetadataCacheService();
        Cache::flush();
    }

    /** @test */
    public function it_generates_and_caches_file_metadata()
    {
        $file = FileUpload::factory()->create([
            'original_filename' => 'test-document.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'google_drive_file_id' => 'test-drive-id'
        ]);

        $metadata = $this->service->getFileMetadata($file);

        $this->assertIsArray($metadata);
        $this->assertEquals($file->id, $metadata['id']);
        $this->assertEquals('test-document.pdf', $metadata['original_filename']);
        $this->assertEquals(1024, $metadata['file_size']);
        $this->assertEquals('1 KB', $metadata['file_size_human']);
        $this->assertEquals('application/pdf', $metadata['mime_type']);
        $this->assertEquals('document', $metadata['mime_type_category']);
        $this->assertEquals('pdf', $metadata['file_extension']);
        $this->assertTrue($metadata['is_pending'] === false); // Has Google Drive ID
        $this->assertStringContainsString('https://drive.google.com/file/d/test-drive-id/view', $metadata['google_drive_url']);
        
        // Verify it was cached
        $cacheKey = 'file_metadata:' . $file->id;
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_returns_cached_metadata_on_subsequent_calls()
    {
        $file = FileUpload::factory()->create();

        // First call generates and caches
        $metadata1 = $this->service->getFileMetadata($file);
        
        // Second call should return cached version
        $metadata2 = $this->service->getFileMetadata($file);

        $this->assertEquals($metadata1, $metadata2);
    }

    /** @test */
    public function it_generates_comprehensive_file_statistics()
    {
        // Create test data
        FileUpload::factory()->count(5)->create(['google_drive_file_id' => 'completed']);
        FileUpload::factory()->count(3)->create(['google_drive_file_id' => null]); // Pending
        
        // Create files for different time periods
        FileUpload::factory()->create([
            'created_at' => now(),
            'file_size' => 1024
        ]);
        FileUpload::factory()->create([
            'created_at' => now()->startOfWeek(),
            'file_size' => 2048
        ]);
        FileUpload::factory()->create([
            'created_at' => now()->startOfMonth(),
            'file_size' => 4096
        ]);

        $statistics = $this->service->getFileStatistics();

        $this->assertIsArray($statistics);
        $this->assertArrayHasKey('total_files', $statistics);
        $this->assertArrayHasKey('pending_files', $statistics);
        $this->assertArrayHasKey('completed_files', $statistics);
        $this->assertArrayHasKey('total_size', $statistics);
        $this->assertArrayHasKey('total_size_formatted', $statistics);
        $this->assertArrayHasKey('today_files', $statistics);
        $this->assertArrayHasKey('week_files', $statistics);
        $this->assertArrayHasKey('month_files', $statistics);
        $this->assertArrayHasKey('completion_rate', $statistics);
        $this->assertArrayHasKey('file_types', $statistics);
        $this->assertArrayHasKey('cached_at', $statistics);

        $this->assertEquals(11, $statistics['total_files']); // 5 + 3 + 3 from time periods
        $this->assertEquals(3, $statistics['pending_files']);
        $this->assertEquals(8, $statistics['completed_files']); // 5 + 3 from time periods
        $this->assertGreaterThan(0, $statistics['total_size']);
        $this->assertIsString($statistics['total_size_formatted']);
        $this->assertIsFloat($statistics['completion_rate']);
        $this->assertTrue(is_array($statistics['file_types']) || $statistics['file_types'] instanceof \Illuminate\Support\Collection);
    }

    /** @test */
    public function it_generates_filter_options()
    {
        // Create files with different types and emails
        FileUpload::factory()->create([
            'mime_type' => 'image/jpeg',
            'email' => 'user1@example.com'
        ]);
        FileUpload::factory()->create([
            'mime_type' => 'application/pdf',
            'email' => 'user2@example.com'
        ]);
        FileUpload::factory()->create([
            'mime_type' => 'image/jpeg',
            'email' => 'user1@example.com'
        ]);

        $filterOptions = $this->service->getFilterOptions();

        $this->assertIsArray($filterOptions);
        $this->assertArrayHasKey('file_types', $filterOptions);
        $this->assertArrayHasKey('user_emails', $filterOptions);
        $this->assertArrayHasKey('file_size_ranges', $filterOptions);
        $this->assertArrayHasKey('cached_at', $filterOptions);

        // Check file types
        $this->assertCount(2, $filterOptions['file_types']); // jpeg and pdf
        $this->assertEquals('image/jpeg', $filterOptions['file_types'][0]['value']);
        $this->assertEquals(2, $filterOptions['file_types'][0]['count']);
        $this->assertEquals('image', $filterOptions['file_types'][0]['category']);

        // Check user emails
        $this->assertCount(2, $filterOptions['user_emails']);
        $this->assertEquals('user1@example.com', $filterOptions['user_emails'][0]['value']);
        $this->assertEquals(2, $filterOptions['user_emails'][0]['count']);

        // Check file size ranges
        $this->assertCount(4, $filterOptions['file_size_ranges']);
        $this->assertEquals('Small (< 1MB)', $filterOptions['file_size_ranges'][0]['label']);
    }

    /** @test */
    public function it_invalidates_file_cache()
    {
        $file = FileUpload::factory()->create();
        
        // Generate and cache metadata
        $this->service->getFileMetadata($file);
        
        $cacheKey = 'file_metadata:' . $file->id;
        $this->assertTrue(Cache::has($cacheKey));

        // Invalidate cache
        $this->service->invalidateFileCache($file);

        $this->assertFalse(Cache::has($cacheKey));
        $this->assertFalse(Cache::has('file_statistics'));
        $this->assertFalse(Cache::has('file_filter_options'));
    }

    /** @test */
    public function it_invalidates_global_caches()
    {
        // Generate and cache statistics and filter options
        $this->service->getFileStatistics();
        $this->service->getFilterOptions();
        
        $this->assertTrue(Cache::has('file_statistics'));
        $this->assertTrue(Cache::has('file_filter_options'));

        // Invalidate global caches
        $this->service->invalidateGlobalCaches();

        $this->assertFalse(Cache::has('file_statistics'));
        $this->assertFalse(Cache::has('file_filter_options'));
    }

    /** @test */
    public function it_warms_up_cache_for_recent_files()
    {
        // Create files with different update times
        $recentFiles = FileUpload::factory()->count(3)->create([
            'updated_at' => now()
        ]);
        $olderFiles = FileUpload::factory()->count(2)->create([
            'updated_at' => now()->subDays(5)
        ]);

        $warmedCount = $this->service->warmUpCache(3);

        $this->assertEquals(3, $warmedCount);
        
        // Verify recent files were cached
        foreach ($recentFiles as $file) {
            $cacheKey = 'file_metadata:' . $file->id;
            $this->assertTrue(Cache::has($cacheKey));
        }
    }

    /** @test */
    public function it_categorizes_mime_types_correctly()
    {
        $testCases = [
            'image/jpeg' => 'image',
            'image/png' => 'image',
            'application/pdf' => 'document',
            'application/msword' => 'document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
            'video/mp4' => 'video',
            'audio/mpeg' => 'audio',
            'application/zip' => 'archive',
            'text/plain' => 'text',
            'application/unknown' => 'other'
        ];

        foreach ($testCases as $mimeType => $expectedCategory) {
            $file = FileUpload::factory()->create(['mime_type' => $mimeType]);
            $metadata = $this->service->getFileMetadata($file);
            
            $this->assertEquals($expectedCategory, $metadata['mime_type_category'], 
                "Failed for MIME type: {$mimeType}");
        }
    }

    /** @test */
    public function it_formats_file_sizes_correctly()
    {
        $testCases = [
            0 => '0 Bytes',
            1024 => '1 KB',
            1048576 => '1 MB',
            1073741824 => '1 GB',
            1099511627776 => '1 TB'
        ];

        foreach ($testCases as $bytes => $expected) {
            $file = FileUpload::factory()->create(['file_size' => $bytes]);
            $metadata = $this->service->getFileMetadata($file);
            
            $this->assertEquals($expected, $metadata['file_size_human'], 
                "Failed for file size: {$bytes}");
        }
    }

    /** @test */
    public function it_detects_pending_status_correctly()
    {
        $pendingFile = FileUpload::factory()->create(['google_drive_file_id' => null]);
        $completedFile = FileUpload::factory()->create(['google_drive_file_id' => 'drive-id']);

        $pendingMetadata = $this->service->getFileMetadata($pendingFile);
        $completedMetadata = $this->service->getFileMetadata($completedFile);

        $this->assertTrue($pendingMetadata['is_pending']);
        $this->assertFalse($completedMetadata['is_pending']);
    }

    /** @test */
    public function it_generates_google_drive_urls_correctly()
    {
        $fileWithDriveId = FileUpload::factory()->create(['google_drive_file_id' => 'test-drive-id']);
        $fileWithoutDriveId = FileUpload::factory()->create(['google_drive_file_id' => null]);

        $metadataWithUrl = $this->service->getFileMetadata($fileWithDriveId);
        $metadataWithoutUrl = $this->service->getFileMetadata($fileWithoutDriveId);

        $this->assertEquals('https://drive.google.com/file/d/test-drive-id/view', $metadataWithUrl['google_drive_url']);
        $this->assertNull($metadataWithoutUrl['google_drive_url']);
    }

    /** @test */
    public function it_extracts_file_extensions_correctly()
    {
        $testCases = [
            'document.pdf' => 'pdf',
            'image.jpeg' => 'jpeg',
            'archive.tar.gz' => 'gz',
            'no-extension' => '',
            'file.with.multiple.dots.txt' => 'txt'
        ];

        foreach ($testCases as $filename => $expectedExtension) {
            $file = FileUpload::factory()->create(['original_filename' => $filename]);
            $metadata = $this->service->getFileMetadata($file);
            
            $this->assertEquals($expectedExtension, $metadata['file_extension'], 
                "Failed for filename: {$filename}");
        }
    }

    /** @test */
    public function it_calculates_completion_rate_correctly()
    {
        // Create 7 completed files and 3 pending files (70% completion rate)
        FileUpload::factory()->count(7)->create(['google_drive_file_id' => 'completed']);
        FileUpload::factory()->count(3)->create(['google_drive_file_id' => null]);

        $statistics = $this->service->getFileStatistics();

        $this->assertEquals(70.0, $statistics['completion_rate']);
    }

    /** @test */
    public function it_handles_empty_database_gracefully()
    {
        $statistics = $this->service->getFileStatistics();
        $filterOptions = $this->service->getFilterOptions();

        $this->assertEquals(0, $statistics['total_files']);
        $this->assertEquals(0, $statistics['pending_files']);
        $this->assertEquals(0, $statistics['completed_files']);
        $this->assertEquals(0, $statistics['total_size']);
        $this->assertEquals(0, $statistics['completion_rate']);
        $this->assertEmpty($statistics['file_types']);

        $this->assertEmpty($filterOptions['file_types']);
        $this->assertEmpty($filterOptions['user_emails']);
        $this->assertCount(4, $filterOptions['file_size_ranges']); // Size ranges are static
    }
}