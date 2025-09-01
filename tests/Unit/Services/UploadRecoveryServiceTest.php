<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UploadRecoveryService;
use App\Models\FileUpload;
use App\Models\User;
use App\Jobs\UploadToGoogleDrive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class UploadRecoveryServiceTest extends TestCase
{
    use RefreshDatabase;

    private UploadRecoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UploadRecoveryService();
        
        // Set up test configuration
        Config::set('upload-recovery.stuck_threshold_minutes', 30);
        Config::set('upload-recovery.max_retry_attempts', 3);
        Config::set('upload-recovery.max_recovery_attempts', 5);
        Config::set('upload-recovery.batch_size', 10);
    }

    public function test_detect_stuck_uploads_returns_empty_collection_when_no_stuck_uploads()
    {
        // Create a recent upload that's not stuck
        FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'created_at' => now()->subMinutes(10),
            'last_processed_at' => now()->subMinutes(5)
        ]);

        $stuckUploads = $this->service->detectStuckUploads();

        $this->assertCount(0, $stuckUploads);
    }

    public function test_detect_stuck_uploads_finds_uploads_beyond_threshold()
    {
        // Create a stuck upload (older than 30 minutes)
        $stuckUpload = FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'created_at' => now()->subMinutes(45),
            'last_processed_at' => now()->subMinutes(40)
        ]);

        // Create a non-stuck upload
        FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'created_at' => now()->subMinutes(10),
            'last_processed_at' => now()->subMinutes(5)
        ]);

        $stuckUploads = $this->service->detectStuckUploads();

        $this->assertCount(1, $stuckUploads);
        $this->assertEquals($stuckUpload->id, $stuckUploads->first()->id);
    }

    public function test_attempt_recovery_returns_error_for_nonexistent_upload()
    {
        $result = $this->service->attemptRecovery(999);

        $this->assertFalse($result['success']);
        $this->assertEquals('Upload record not found', $result['error']);
        $this->assertEquals(999, $result['upload_id']);
    }

    public function test_attempt_recovery_returns_success_for_already_completed_upload()
    {
        $upload = FileUpload::factory()->create([
            'google_drive_file_id' => 'test-drive-id'
        ]);

        $result = $this->service->attemptRecovery($upload->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('Upload already completed', $result['message']);
        $this->assertTrue($result['already_completed']);
    }

    public function test_attempt_recovery_fails_when_max_recovery_attempts_exceeded()
    {
        $upload = FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'recovery_attempts' => 5 // Exceeds max of 5
        ]);

        $result = $this->service->attemptRecovery($upload->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('Upload has exceeded maximum recovery attempts', $result['error']);
        $this->assertTrue($result['max_attempts_exceeded']);
    }

    public function test_attempt_recovery_fails_when_local_file_missing()
    {
        Storage::fake('public');
        
        $upload = FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'filename' => 'nonexistent-file.txt',
            'recovery_attempts' => 0
        ]);

        $result = $this->service->attemptRecovery($upload->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('Local file no longer exists', $result['error']);
        $this->assertTrue($result['file_missing']);
    }

    public function test_attempt_recovery_queues_job_for_valid_upload()
    {
        Queue::fake();
        
        // Create a real file in the expected location
        $filename = 'test-file-' . uniqid() . '.txt';
        $filePath = storage_path('app/public/uploads/' . $filename);
        
        // Ensure directory exists
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        file_put_contents($filePath, 'test content');
        
        $upload = FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'filename' => $filename,
            'recovery_attempts' => 0
        ]);

        $result = $this->service->attemptRecovery($upload->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('Upload re-queued for processing', $result['message']);
        $this->assertEquals(1, $result['recovery_attempts']);
        
        Queue::assertPushed(UploadToGoogleDrive::class);
        
        // Verify recovery attempts was incremented
        $upload->refresh();
        $this->assertEquals(1, $upload->recovery_attempts);
        $this->assertNotNull($upload->last_processed_at);
        
        // Clean up
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function test_bulk_recovery_processes_all_stuck_uploads()
    {
        Queue::fake();
        
        // Create real files in the expected location
        $filename1 = 'file1-' . uniqid() . '.txt';
        $filename2 = 'file2-' . uniqid() . '.txt';
        $filePath1 = storage_path('app/public/uploads/' . $filename1);
        $filePath2 = storage_path('app/public/uploads/' . $filename2);
        
        // Ensure directory exists
        if (!is_dir(dirname($filePath1))) {
            mkdir(dirname($filePath1), 0755, true);
        }
        
        file_put_contents($filePath1, 'content 1');
        file_put_contents($filePath2, 'content 2');
        
        // Create stuck uploads
        $upload1 = FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'filename' => $filename1,
            'created_at' => now()->subMinutes(45),
            'recovery_attempts' => 0
        ]);
        
        $upload2 = FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'filename' => $filename2,
            'created_at' => now()->subMinutes(50),
            'recovery_attempts' => 0
        ]);

        $result = $this->service->bulkRecovery();

        $this->assertEquals(2, $result['total_processed']);
        $this->assertEquals(2, $result['successful_recoveries']);
        $this->assertEquals(0, $result['failed_recoveries']);
        $this->assertNotNull($result['started_at']);
        $this->assertNotNull($result['completed_at']);
        
        Queue::assertPushed(UploadToGoogleDrive::class, 2);
        
        // Clean up
        if (file_exists($filePath1)) unlink($filePath1);
        if (file_exists($filePath2)) unlink($filePath2);
    }

    public function test_bulk_recovery_processes_specific_upload_ids()
    {
        Queue::fake();
        
        // Create real file for upload1
        $filename1 = 'file1-' . uniqid() . '.txt';
        $filePath1 = storage_path('app/public/uploads/' . $filename1);
        
        // Ensure directory exists
        if (!is_dir(dirname($filePath1))) {
            mkdir(dirname($filePath1), 0755, true);
        }
        
        file_put_contents($filePath1, 'content 1');
        
        $upload1 = FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'filename' => $filename1,
            'recovery_attempts' => 0
        ]);
        
        $upload2 = FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'filename' => 'nonexistent-file.txt', // File doesn't exist
            'recovery_attempts' => 0
        ]);

        // Process only upload1
        $result = $this->service->bulkRecovery([$upload1->id]);

        $this->assertEquals(1, $result['total_processed']);
        $this->assertEquals(1, $result['successful_recoveries']);
        $this->assertEquals(0, $result['failed_recoveries']);
        
        Queue::assertPushed(UploadToGoogleDrive::class, 1);
        
        // Clean up
        if (file_exists($filePath1)) unlink($filePath1);
    }

    public function test_get_recovery_statistics_returns_correct_counts()
    {
        // Create various upload states
        FileUpload::factory()->create(['google_drive_file_id' => 'completed-1']);
        FileUpload::factory()->create(['google_drive_file_id' => null, 'created_at' => now()->subMinutes(45)]);
        FileUpload::factory()->create(['google_drive_file_id' => null, 'retry_count' => 3, 'last_error' => 'Test error']);

        $stats = $this->service->getRecoveryStatistics();

        $this->assertEquals(3, $stats['counts']['total_uploads']);
        $this->assertEquals(2, $stats['counts']['pending_uploads']);
        $this->assertEquals(1, $stats['counts']['completed_uploads']);
        $this->assertArrayHasKey('recent_activity', $stats);
        $this->assertArrayHasKey('system_health', $stats);
        $this->assertNotNull($stats['generated_at']);
    }

    public function test_analyze_failure_pattern_identifies_common_errors()
    {
        // Create uploads with similar errors
        FileUpload::factory()->create([
            'last_error' => 'Token expired',
            'retry_count' => 1,
            'created_at' => now()->subHours(2)
        ]);
        
        FileUpload::factory()->create([
            'last_error' => 'Token expired',
            'retry_count' => 2,
            'created_at' => now()->subHours(1)
        ]);
        
        FileUpload::factory()->create([
            'last_error' => 'Token expired',
            'retry_count' => 1,
            'created_at' => now()->subMinutes(45)
        ]);
        
        FileUpload::factory()->create([
            'last_error' => 'Network timeout',
            'retry_count' => 1,
            'created_at' => now()->subMinutes(30)
        ]);

        $uploads = FileUpload::where('created_at', '>=', now()->subHours(3))
            ->where(function ($query) {
                $query->whereNotNull('last_error')
                      ->orWhere('retry_count', '>', 0);
            })
            ->get();
            
        $analysis = $this->service->analyzeFailurePattern($uploads);

        $this->assertEquals(4, $analysis['total_uploads_analyzed']);
        $this->assertArrayHasKey('error_patterns', $analysis);
        $this->assertArrayHasKey('common_issues', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
        
        // Should identify token issues as common (3 occurrences)
        $this->assertGreaterThan(0, count($analysis['common_issues']));
        $this->assertContains('Check Google Drive token validity and refresh mechanism', $analysis['recommendations']);
    }
}