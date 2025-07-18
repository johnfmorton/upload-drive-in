<?php

namespace Tests\Unit\Services;

use App\Models\FileUpload;
use App\Services\FileSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private FileSecurityService $fileSecurityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSecurityService = new FileSecurityService();
        Storage::fake('public');
    }

    /** @test */
    public function it_detects_dangerous_file_extensions()
    {
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'php', 'js', 'vbs'];

        foreach ($dangerousExtensions as $extension) {
            $file = UploadedFile::fake()->create("malware.{$extension}", 1024);
            $violations = $this->fileSecurityService->validateFileUpload($file);

            $this->assertNotEmpty($violations, "Should detect dangerous extension: {$extension}");
            $this->assertTrue(
                collect($violations)->contains(fn($v) => $v['type'] === 'dangerous_extension'),
                "Should flag dangerous extension: {$extension}"
            );
        }
    }

    /** @test */
    public function it_allows_safe_file_extensions()
    {
        $safeExtensions = ['pdf', 'jpg', 'png', 'txt', 'docx', 'xlsx'];

        foreach ($safeExtensions as $extension) {
            $file = UploadedFile::fake()->create("document.{$extension}", 1024);
            $violations = $this->fileSecurityService->validateFileUpload($file);

            $dangerousExtensionViolations = collect($violations)
                ->filter(fn($v) => $v['type'] === 'dangerous_extension');

            $this->assertTrue(
                $dangerousExtensionViolations->isEmpty(),
                "Should allow safe extension: {$extension}"
            );
        }
    }

    /** @test */
    public function it_detects_mime_type_mismatches()
    {
        // Create a file with PDF extension but text content
        $file = UploadedFile::fake()->createWithContent(
            'document.pdf',
            'This is plain text, not a PDF'
        );

        $violations = $this->fileSecurityService->validateFileUpload($file);

        $this->assertTrue(
            collect($violations)->contains(fn($v) => $v['type'] === 'mime_mismatch'),
            'Should detect MIME type mismatch'
        );
    }

    /** @test */
    public function it_detects_suspicious_file_content()
    {
        $suspiciousContents = [
            '<script>alert("xss")</script>',
            'eval($_POST["code"]);',
            'system("rm -rf /");',
            'shell_exec("malicious command");',
            'javascript:alert("xss")',
        ];

        foreach ($suspiciousContents as $content) {
            $file = UploadedFile::fake()->createWithContent('test.txt', $content);
            $violations = $this->fileSecurityService->validateFileUpload($file);

            $this->assertTrue(
                collect($violations)->contains(fn($v) => $v['type'] === 'suspicious_content'),
                "Should detect suspicious content: {$content}"
            );
        }
    }

    /** @test */
    public function it_validates_file_size_limits()
    {
        // Create a file larger than the default limit
        $largeFile = UploadedFile::fake()->create('large.pdf', 200 * 1024); // 200MB

        // Mock the config to set a smaller limit
        config(['filesystems.max_file_size' => 100 * 1024 * 1024]); // 100MB

        $violations = $this->fileSecurityService->validateFileUpload($largeFile);

        $this->assertTrue(
            collect($violations)->contains(fn($v) => $v['type'] === 'size_exceeded'),
            'Should detect file size exceeded'
        );
    }

    /** @test */
    public function it_sanitizes_filenames_properly()
    {
        $testCases = [
            '../../../etc/passwd' => 'passwd',
            'file with spaces.txt' => 'file_with_spaces.txt',
            'file@#$%^&*().txt' => 'file_________.txt',
            'very-long-filename-that-exceeds-the-maximum-allowed-length-for-filenames-in-most-filesystems-and-should-be-truncated-to-prevent-issues.txt' => 'very-long-filename-that-exceeds-the-maximum-allowed-length-for-filenames-in-most-filesystems-and-should-be-truncated-to-prevent-issues.txt',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->fileSecurityService->sanitizeFilename($input);
            
            if (strlen($expected) > 255) {
                $this->assertLessThanOrEqual(255, strlen($result));
            } else {
                $this->assertEquals($expected, $result);
            }
            
            // Ensure no path traversal characters remain
            $this->assertStringNotContainsString('..', $result);
            $this->assertStringNotContainsString('/', $result);
        }
    }

    /** @test */
    public function it_identifies_safe_preview_types()
    {
        $safeMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'text/plain',
            'text/csv',
        ];

        foreach ($safeMimeTypes as $mimeType) {
            $this->assertTrue(
                $this->fileSecurityService->isPreviewSafe($mimeType),
                "Should consider {$mimeType} safe for preview"
            );
        }
    }

    /** @test */
    public function it_identifies_unsafe_preview_types()
    {
        $unsafeMimeTypes = [
            'application/x-executable',
            'application/x-msdownload',
            'application/x-dosexec',
            'application/octet-stream',
            'text/x-php',
        ];

        foreach ($unsafeMimeTypes as $mimeType) {
            $this->assertFalse(
                $this->fileSecurityService->isPreviewSafe($mimeType),
                "Should consider {$mimeType} unsafe for preview"
            );
        }
    }

    /** @test */
    public function it_validates_existing_files()
    {
        // Create a test file with dangerous extension
        $file = FileUpload::factory()->create([
            'original_filename' => 'malware.exe',
            'mime_type' => 'application/x-executable',
            'file_size' => 1024,
        ]);

        $violations = $this->fileSecurityService->validateExistingFile($file);

        // Should have at least one violation (file not found or dangerous extension)
        $this->assertNotEmpty($violations);
        
        // Check if it's either a dangerous extension or file not found violation
        $hasDangerousExtension = collect($violations)->contains(fn($v) => $v['type'] === 'dangerous_extension');
        $hasFileNotFound = collect($violations)->contains(fn($v) => $v['type'] === 'file_not_found');
        
        $this->assertTrue($hasDangerousExtension || $hasFileNotFound);
    }

    /** @test */
    public function it_handles_missing_files_gracefully()
    {
        $file = FileUpload::factory()->create([
            'filename' => 'nonexistent-file.pdf',
            'original_filename' => 'document.pdf',
        ]);

        $violations = $this->fileSecurityService->validateExistingFile($file);

        $this->assertNotEmpty($violations);
        $this->assertTrue(
            collect($violations)->contains(fn($v) => $v['type'] === 'file_not_found')
        );
    }

    /** @test */
    public function it_categorizes_violations_by_severity()
    {
        $file = UploadedFile::fake()->create('malware.exe', 1024);
        $violations = $this->fileSecurityService->validateFileUpload($file);

        $highSeverityViolations = collect($violations)
            ->filter(fn($v) => $v['severity'] === 'high');

        $this->assertNotEmpty($highSeverityViolations);
    }

    /** @test */
    public function it_provides_descriptive_violation_messages()
    {
        $file = UploadedFile::fake()->create('script.exe', 1024);
        $violations = $this->fileSecurityService->validateFileUpload($file);

        foreach ($violations as $violation) {
            $this->assertArrayHasKey('type', $violation);
            $this->assertArrayHasKey('message', $violation);
            $this->assertArrayHasKey('severity', $violation);
            $this->assertNotEmpty($violation['message']);
        }
    }

    /** @test */
    public function it_skips_content_scanning_for_large_files()
    {
        // Create a file larger than the scan limit
        $largeFile = UploadedFile::fake()->createWithContent(
            'large.txt',
            str_repeat('A', 60 * 1024 * 1024) // 60MB
        );

        $violations = $this->fileSecurityService->validateFileUpload($largeFile);

        // Should not have content-based violations due to size limit
        $contentViolations = collect($violations)
            ->filter(fn($v) => $v['type'] === 'suspicious_content');

        $this->assertTrue($contentViolations->isEmpty());
    }
}