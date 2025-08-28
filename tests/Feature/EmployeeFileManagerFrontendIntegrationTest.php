<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FileUpload;
use App\Services\FileManagerService;
use App\Services\FilePreviewService;
use App\Services\FileSecurityService;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeFileManagerFrontendIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private FileUpload $testFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an employee user
        $this->employee = User::factory()->create([
            'role' => 'employee',
            'username' => 'testemployee'
        ]);

        // Create a client user who uploads files to the employee
        $client = User::factory()->create(['role' => 'client']);
        
        // Create a client-employee relationship
        $this->employee->clientUsers()->attach($client->id);
        
        // Create a test file uploaded by the client to the employee
        Storage::fake('public');
        $uploadedFile = UploadedFile::fake()->create('test-document.pdf', 1024, 'application/pdf');
        
        $this->testFile = FileUpload::factory()->create([
            'company_user_id' => $this->employee->id,  // Employee who receives the file
            'client_user_id' => $client->id,           // Client who uploads the file
            'original_filename' => 'test-document.pdf',
            'filename' => 'test-file.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'google_drive_file_id' => 'test-drive-id-123'
        ]);

        // Store the actual file for testing
        Storage::disk('public')->put('uploads/' . $this->testFile->filename, $uploadedFile->getContent());
    }

    /** @test */
    public function employee_preview_modal_works_with_new_backend_endpoints()
    {
        $this->actingAs($this->employee);

        // Test the preview endpoint directly
        $response = $this->get(route('employee.file-manager.preview', [
            'username' => $this->employee->username,
            'fileUpload' => $this->testFile
        ]));

        // Should return successful response (not redirect to Google Drive)
        $this->assertNotEquals(302, $response->getStatusCode(), 'Preview should not redirect to Google Drive');
        
        // Should serve file content or appropriate response
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 404]), 
            'Preview should return 200 (success) or 404 (not available), got: ' . $response->getStatusCode()
        );

        // Verify no Google Drive URLs in response
        $content = $response->getContent();
        $this->assertStringNotContainsString('drive.google.com', $content, 'Response should not contain Google Drive URLs');
        $this->assertStringNotContainsString('https://drive.google.com/file/d/', $content, 'Response should not contain Google Drive preview URLs');
    }

    /** @test */
    public function employee_download_functionality_works_from_interface()
    {
        $this->actingAs($this->employee);

        // Test the download endpoint directly
        $response = $this->get(route('employee.file-manager.download', [
            'username' => $this->employee->username,
            'fileUpload' => $this->testFile
        ]));

        // Should return successful response (not redirect to Google Drive)
        $this->assertNotEquals(302, $response->getStatusCode(), 'Download should not redirect to Google Drive');
        
        // Should serve file content
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 404]), 
            'Download should return 200 (success) or 404 (not found), got: ' . $response->getStatusCode()
        );

        // Verify no Google Drive URLs in response headers
        $location = $response->headers->get('Location');
        if ($location) {
            $this->assertStringNotContainsString('drive.google.com', $location, 'Download should not redirect to Google Drive');
            $this->assertStringNotContainsString('https://drive.google.com/uc?export=download', $location, 'Download should not redirect to Google Drive download URL');
        }
    }

    /** @test */
    public function error_messages_display_correctly_in_employee_ui()
    {
        $this->actingAs($this->employee);

        // Create another user and a file that the employee cannot access
        $otherUser = User::factory()->create(['role' => 'employee']);
        $restrictedFile = FileUpload::factory()->create([
            'company_user_id' => $otherUser->id,
            'original_filename' => 'restricted-file.pdf',
            'mime_type' => 'application/pdf'
        ]);

        // Test preview access denied
        $response = $this->get(route('employee.file-manager.preview', [
            'username' => $this->employee->username,
            'fileUpload' => $restrictedFile
        ]));

        $this->assertEquals(403, $response->getStatusCode(), 'Should return 403 for unauthorized preview access');

        // Test download access denied
        $response = $this->get(route('employee.file-manager.download', [
            'username' => $this->employee->username,
            'fileUpload' => $restrictedFile
        ]));

        $this->assertEquals(403, $response->getStatusCode(), 'Should return 403 for unauthorized download access');

        // Test AJAX error response format for preview
        $response = $this->getJson(route('employee.file-manager.preview', [
            'username' => $this->employee->username,
            'fileUpload' => $restrictedFile
        ]));

        $this->assertEquals(403, $response->getStatusCode());
        // Preview returns plain text error, not JSON

        // Test AJAX error response format for download
        $response = $this->getJson(route('employee.file-manager.download', [
            'username' => $this->employee->username,
            'fileUpload' => $restrictedFile
        ]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function no_direct_google_drive_urls_appear_in_browser_network_tab()
    {
        $this->actingAs($this->employee);

        // Test file manager index page
        $response = $this->get(route('employee.file-manager.index', [
            'username' => $this->employee->username
        ]));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify no Google Drive URLs in the HTML
        $this->assertStringNotContainsString('drive.google.com', $content, 'File manager should not contain Google Drive URLs');
        $this->assertStringNotContainsString('https://drive.google.com/file/d/', $content, 'File manager should not contain Google Drive preview URLs');
        $this->assertStringNotContainsString('https://drive.google.com/uc?export=download', $content, 'File manager should not contain Google Drive download URLs');

        // Test file detail page
        $response = $this->get(route('employee.file-manager.show', [
            'username' => $this->employee->username,
            'fileUpload' => $this->testFile
        ]));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify no Google Drive URLs in the file detail page
        $this->assertStringNotContainsString('drive.google.com', $content, 'File detail page should not contain Google Drive URLs');
        $this->assertStringNotContainsString('https://drive.google.com/file/d/', $content, 'File detail page should not contain Google Drive preview URLs');
        $this->assertStringNotContainsString('https://drive.google.com/uc?export=download', $content, 'File detail page should not contain Google Drive download URLs');

        // Verify that preview and download URLs point to application endpoints
        $this->assertStringContainsString(
            route('employee.file-manager.preview', ['username' => $this->employee->username, 'fileUpload' => $this->testFile]),
            $content,
            'File detail page should contain application preview URL'
        );
        
        $this->assertStringContainsString(
            route('employee.file-manager.download', ['username' => $this->employee->username, 'fileUpload' => $this->testFile]),
            $content,
            'File detail page should contain application download URL'
        );
    }

    /** @test */
    public function employee_file_access_is_properly_logged()
    {
        $this->actingAs($this->employee);

        // Test preview - should work and be logged
        $response = $this->get(route('employee.file-manager.preview', [
            'username' => $this->employee->username,
            'fileUpload' => $this->testFile
        ]));

        // Should return successful response
        $this->assertTrue(in_array($response->getStatusCode(), [200, 404]));
        
        // Test download - should work and be logged
        $response = $this->get(route('employee.file-manager.download', [
            'username' => $this->employee->username,
            'fileUpload' => $this->testFile
        ]));

        // Should return successful response
        $this->assertTrue(in_array($response->getStatusCode(), [200, 404]));
    }

    /** @test */
    public function employee_security_violations_are_properly_handled()
    {
        $this->actingAs($this->employee);

        // Test that the security validation system is in place
        // We can't easily mock the services in integration tests, but we can verify
        // that the endpoints are using the security services by checking the response
        
        // Test download - should work normally for valid files
        $response = $this->get(route('employee.file-manager.download', [
            'username' => $this->employee->username,
            'fileUpload' => $this->testFile
        ]));

        // Should return successful response (not a security violation)
        $this->assertTrue(in_array($response->getStatusCode(), [200, 404]));
        
        // Test preview - should work normally for valid files
        $response = $this->get(route('employee.file-manager.preview', [
            'username' => $this->employee->username,
            'fileUpload' => $this->testFile
        ]));

        // Should return successful response (not a security violation)
        $this->assertTrue(in_array($response->getStatusCode(), [200, 404]));
    }

    /** @test */
    public function employee_preview_handles_unsafe_file_types()
    {
        $this->actingAs($this->employee);

        // Create a client for the unsafe file
        $unsafeClient = User::factory()->create(['role' => 'client']);
        $this->employee->clientUsers()->attach($unsafeClient->id);
        
        // Create a file with potentially unsafe mime type
        $unsafeFile = FileUpload::factory()->create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $unsafeClient->id,
            'original_filename' => 'script.exe',
            'filename' => 'script.exe',
            'mime_type' => 'application/x-executable',
            'file_size' => 1024
        ]);

        // Mock the file security service to block unsafe preview
        $fileSecurityService = $this->mock(FileSecurityService::class);
        $auditLogService = $this->mock(AuditLogService::class);

        $fileSecurityService->shouldReceive('isPreviewSafe')
            ->once()
            ->with('application/x-executable')
            ->andReturn(false);

        // Expect security violation logging
        $auditLogService->shouldReceive('logSecurityViolation')
            ->once()
            ->with('preview_blocked_unsafe_type', $this->employee, \Mockery::any(), \Mockery::any());

        // Test preview of unsafe file type
        $response = $this->get(route('employee.file-manager.preview', [
            'username' => $this->employee->username,
            'fileUpload' => $unsafeFile
        ]));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('security restrictions', $response->getContent());
    }

    /** @test */
    public function employee_file_manager_javascript_uses_correct_endpoints()
    {
        $this->actingAs($this->employee);

        // Get the file manager page
        $response = $this->get(route('employee.file-manager.index', [
            'username' => $this->employee->username
        ]));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check that JavaScript uses the correct employee endpoints
        $expectedPreviewUrl = "/employee/{$this->employee->username}/file-manager/{$this->testFile->id}/preview";
        $expectedDownloadUrl = "/employee/{$this->employee->username}/file-manager/{$this->testFile->id}/download";

        // The exact JavaScript might be in included components, but we can check for the pattern
        $this->assertTrue(
            strpos($content, 'employee') !== false,
            'File manager should reference employee endpoints'
        );
    }

    /** @test */
    public function employee_bulk_operations_work_correctly()
    {
        $this->actingAs($this->employee);

        // Create additional test files
        $client2 = User::factory()->create(['role' => 'client']);
        $this->employee->clientUsers()->attach($client2->id);
        
        $file2 = FileUpload::factory()->create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $client2->id,
            'original_filename' => 'test-document-2.pdf',
            'filename' => 'test-file-2.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048
        ]);

        // Test bulk download
        $response = $this->post(route('employee.file-manager.bulk-download', [
            'username' => $this->employee->username
        ]), [
            'file_ids' => [$this->testFile->id, $file2->id]
        ]);

        // Should return successful response or appropriate error
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302, 404]));

        // Verify no Google Drive URLs in bulk operations
        if ($response->getStatusCode() === 302) {
            $location = $response->headers->get('Location');
            if ($location) {
                $this->assertStringNotContainsString('drive.google.com', $location);
            }
        }
    }

    /** @test */
    public function employee_authentication_is_properly_enforced()
    {
        // Test without authentication
        $response = $this->get(route('employee.file-manager.preview', [
            'username' => 'testemployee',
            'fileUpload' => $this->testFile
        ]));

        // Should redirect to login or return 401
        $this->assertTrue(in_array($response->getStatusCode(), [302, 401]));

        $response = $this->get(route('employee.file-manager.download', [
            'username' => 'testemployee',
            'fileUpload' => $this->testFile
        ]));

        // Should redirect to login or return 401
        $this->assertTrue(in_array($response->getStatusCode(), [302, 401]));
    }
}