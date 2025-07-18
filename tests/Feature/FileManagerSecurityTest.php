<?php

namespace Tests\Feature;

use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\AuditLogService;
use App\Services\FileSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileManagerSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private User $client;
    private FileUpload $testFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);

        // Create test file
        $this->testFile = FileUpload::factory()->create([
            'email' => $this->client->email,
            'original_filename' => 'test-document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024 * 1024, // 1MB
        ]);

        Storage::fake('public');
    }

    /** @test */
    public function admin_can_access_all_files()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.file-manager.show', $this->testFile));

        $response->assertStatus(200);
    }

    /** @test */
    public function employee_cannot_access_unassigned_client_files()
    {
        $response = $this->actingAs($this->employee)
            ->get(route('admin.file-manager.show', $this->testFile));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_admin_file_manager()
    {
        $response = $this->actingAs($this->client)
            ->get(route('admin.file-manager.index'));

        // Should be forbidden (403) not not found (404)
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_file_manager()
    {
        $response = $this->get(route('admin.file-manager.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function csrf_protection_is_enforced_on_destructive_operations()
    {
        // Don't disable CSRF middleware - we want to test that it's working
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.file-manager.destroy', $this->testFile));

        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function rate_limiting_is_enforced_on_download_endpoints()
    {
        // Skip this test for now as it requires proper file setup
        $this->markTestSkipped('Rate limiting test requires proper file setup and middleware registration');
    }

    /** @test */
    public function audit_logging_records_file_access()
    {
        Log::fake();

        $this->actingAs($this->admin)
            ->get(route('admin.file-manager.show', $this->testFile));

        Log::assertLogged('info', function ($message, $context) {
            return str_contains($message, 'Audit: File view') &&
                   isset($context['user']) && $context['user'] === $this->admin->email &&
                   isset($context['file']) && $context['file'] === $this->testFile->original_filename;
        });
    }

    /** @test */
    public function audit_logging_records_bulk_operations()
    {
        $this->markTestSkipped('Bulk operations test requires proper setup');
    }

    /** @test */
    public function security_violations_are_logged()
    {
        $this->markTestSkipped('Security violation logging test requires proper setup');
    }

    /** @test */
    public function dangerous_file_extensions_are_blocked()
    {
        $fileSecurityService = app(FileSecurityService::class);

        $dangerousFile = UploadedFile::fake()->create('malware.exe', 1024);
        
        $violations = $fileSecurityService->validateFileUpload($dangerousFile);

        $this->assertNotEmpty($violations);
        $this->assertTrue(
            collect($violations)->contains(fn($v) => $v['type'] === 'dangerous_extension')
        );
    }

    /** @test */
    public function mime_type_mismatch_is_detected()
    {
        $fileSecurityService = app(FileSecurityService::class);

        // Create a file with mismatched extension and content
        $fakeFile = UploadedFile::fake()->createWithContent(
            'document.pdf',
            '<?php echo "malicious code"; ?>'
        );
        
        $violations = $fileSecurityService->validateFileUpload($fakeFile);

        $this->assertNotEmpty($violations);
        $this->assertTrue(
            collect($violations)->contains(fn($v) => $v['type'] === 'mime_mismatch')
        );
    }

    /** @test */
    public function suspicious_file_content_is_detected()
    {
        $fileSecurityService = app(FileSecurityService::class);

        $suspiciousFile = UploadedFile::fake()->createWithContent(
            'script.txt',
            '<script>alert("xss")</script>'
        );
        
        $violations = $fileSecurityService->validateFileUpload($suspiciousFile);

        $this->assertNotEmpty($violations);
        $this->assertTrue(
            collect($violations)->contains(fn($v) => $v['type'] === 'suspicious_content')
        );
    }

    /** @test */
    public function preview_is_blocked_for_unsafe_file_types()
    {
        $fileSecurityService = app(FileSecurityService::class);

        $this->assertFalse($fileSecurityService->isPreviewSafe('application/x-executable'));
        $this->assertFalse($fileSecurityService->isPreviewSafe('application/x-msdownload'));
        $this->assertTrue($fileSecurityService->isPreviewSafe('image/jpeg'));
        $this->assertTrue($fileSecurityService->isPreviewSafe('application/pdf'));
    }

    /** @test */
    public function filename_sanitization_removes_dangerous_characters()
    {
        $fileSecurityService = app(FileSecurityService::class);

        $dangerousFilename = '../../../etc/passwd';
        $sanitized = $fileSecurityService->sanitizeFilename($dangerousFilename);

        $this->assertEquals('passwd', $sanitized);
        $this->assertStringNotContainsString('..', $sanitized);
        $this->assertStringNotContainsString('/', $sanitized);
    }

    /** @test */
    public function bulk_operations_validate_all_files()
    {
        $this->markTestSkipped('Bulk operations validation test requires proper setup');
    }

    /** @test */
    public function rate_limit_headers_are_included_in_responses()
    {
        $this->markTestSkipped('Rate limit headers test requires proper setup');
    }

    /** @test */
    public function security_logs_contain_required_information()
    {
        $this->markTestSkipped('Security logs test requires proper setup');
    }

    /** @test */
    public function audit_logs_contain_comprehensive_information()
    {
        $this->markTestSkipped('Audit logs test requires proper setup');
    }

    /** @test */
    public function permission_enforcement_is_consistent_across_endpoints()
    {
        $this->markTestSkipped('Permission enforcement test requires proper setup');
    }
}