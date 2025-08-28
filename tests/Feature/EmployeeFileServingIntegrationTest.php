<?php

namespace Tests\Feature;

use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeFileServingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $otherEmployee;
    private FileUpload $employeeFile;
    private FileUpload $otherEmployeeFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'username' => 'test-employee'
        ]);
        
        $this->otherEmployee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'username' => 'other-employee'
        ]);

        // Create test files
        $this->employeeFile = FileUpload::factory()->create([
            'company_user_id' => $this->employee->id,
            'uploaded_by_user_id' => $this->employee->id,
            'filename' => 'employee-test-file.txt',
            'original_filename' => 'employee-test-file.txt',
            'mime_type' => 'text/plain',
            'file_size' => 1024,
            'email' => 'client@example.com'
        ]);

        $this->otherEmployeeFile = FileUpload::factory()->create([
            'company_user_id' => $this->otherEmployee->id,
            'uploaded_by_user_id' => $this->otherEmployee->id,
            'filename' => 'other-employee-file.txt',
            'original_filename' => 'other-employee-file.txt',
            'mime_type' => 'text/plain',
            'file_size' => 2048,
            'email' => 'other-client@example.com'
        ]);

        Storage::fake('public');
    }

    public function test_employee_can_download_their_own_files()
    {
        // Create the actual file content
        $fileContent = 'Employee test file content';
        Storage::disk('public')->put('uploads/' . $this->employeeFile->filename, $fileContent);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.file-manager.download', [
                'username' => $this->employee->username,
                'fileUpload' => $this->employeeFile
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=' . $this->employeeFile->original_filename);
        $response->assertHeader('Content-Type', 'text/plain');
        $this->assertEquals($fileContent, $response->getContent());
    }

    public function test_employee_can_preview_their_own_files()
    {
        // Create the actual file content
        $fileContent = 'Employee preview test content';
        Storage::disk('public')->put('uploads/' . $this->employeeFile->filename, $fileContent);

        $response = $this->actingAs($this->employee)
            ->get(route('employee.file-manager.preview', [
                'username' => $this->employee->username,
                'fileUpload' => $this->employeeFile
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertEquals($fileContent, $response->getContent());

        // Verify caching headers
        $response->assertHeader('Cache-Control', 'public, max-age=3600');
        $this->assertNotNull($response->headers->get('ETag'));
    }

    public function test_employee_cannot_download_other_employees_files()
    {
        // Create the actual file content
        Storage::disk('public')->put('uploads/' . $this->otherEmployeeFile->filename, 'Other employee content');

        $response = $this->actingAs($this->employee)
            ->get(route('employee.file-manager.download', [
                'username' => $this->employee->username,
                'fileUpload' => $this->otherEmployeeFile
            ]));

        $response->assertStatus(403);
    }

    public function test_employee_cannot_preview_other_employees_files()
    {
        // Create the actual file content
        Storage::disk('public')->put('uploads/' . $this->otherEmployeeFile->filename, 'Other employee content');

        $response = $this->actingAs($this->employee)
            ->get(route('employee.file-manager.preview', [
                'username' => $this->employee->username,
                'fileUpload' => $this->otherEmployeeFile
            ]));

        $response->assertStatus(403);
    }

    public function test_employee_download_enforces_security_validation()
    {
        // Create a file with dangerous extension
        $dangerousFile = FileUpload::factory()->create([
            'company_user_id' => $this->employee->id,
            'uploaded_by_user_id' => $this->employee->id,
            'filename' => 'malware.exe',
            'original_filename' => 'malware.exe',
            'mime_type' => 'application/x-executable',
            'file_size' => 1024
        ]);

        Storage::disk('public')->put('uploads/' . $dangerousFile->filename, 'fake executable content');

        $response = $this->actingAs($this->employee)
            ->get(route('employee.file-manager.download', [
                'username' => $this->employee->username,
                'fileUpload' => $dangerousFile
            ]));

        // Should be blocked due to security concerns
        $response->assertStatus(403);
        $response->assertSee('security concerns');
    }

    public function test_employee_preview_blocks_unsafe_file_types()
    {
        // Create a file with unsafe mime type for preview
        $unsafeFile = FileUpload::factory()->create([
            'company_user_id' => $this->employee->id,
            'uploaded_by_user_id' => $this->employee->id,
            'filename' => 'script.js',
            'original_filename' => 'script.js',
            'mime_type' => 'application/javascript',
            'file_size' => 512
        ]);

        Storage::disk('public')->put('uploads/' . $unsafeFile->filename, 'alert("xss")');

        $response = $this->actingAs($this->employee)
            ->get(route('employee.file-manager.preview', [
                'username' => $this->employee->username,
                'fileUpload' => $unsafeFile
            ]));

        $response->assertStatus(403);
        $response->assertSee('security restrictions');
    }

    public function test_employee_download_requires_authentication()
    {
        $response = $this->get(route('employee.file-manager.download', [
            'username' => $this->employee->username,
            'fileUpload' => $this->employeeFile
        ]));

        // Should redirect to login
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_employee_preview_requires_authentication()
    {
        $response = $this->get(route('employee.file-manager.preview', [
            'username' => $this->employee->username,
            'fileUpload' => $this->employeeFile
        ]));

        $response->assertStatus(401);
    }
}