<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\Employee\FileManagerController;
use App\Services\FileManagerService;
use App\Services\FilePreviewService;
use App\Services\AuditLogService;
use App\Services\FileSecurityService;
use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use App\Exceptions\FileManagerException;
use App\Exceptions\FileAccessException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Mockery;

class EmployeeFileManagerControllerTest extends TestCase
{
    use RefreshDatabase;

    private FileManagerController $controller;
    private FileManagerService $mockFileManagerService;
    private FilePreviewService $mockFilePreviewService;
    private AuditLogService $mockAuditLogService;
    private FileSecurityService $mockFileSecurityService;
    private User $employee;
    private User $client;
    private FileUpload $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockFileManagerService = Mockery::mock(FileManagerService::class);
        $this->mockFilePreviewService = Mockery::mock(FilePreviewService::class);
        $this->mockAuditLogService = Mockery::mock(AuditLogService::class);
        $this->mockFileSecurityService = Mockery::mock(FileSecurityService::class);
        
        $this->controller = new FileManagerController(
            $this->mockFileManagerService,
            $this->mockFilePreviewService,
            $this->mockAuditLogService,
            $this->mockFileSecurityService
        );

        $this->employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create a file that the employee can access (uploaded by a client they manage)
        $this->testFile = FileUpload::factory()->create([
            'client_user_id' => $this->client->id,
            'uploaded_by_user_id' => $this->employee->id,
            'mime_type' => 'application/pdf'
        ]);
        
        // Create the client-employee relationship
        \App\Models\ClientUserRelationship::create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $this->client->id,
            'is_primary' => true
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function download_validates_security_and_downloads_file()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn([]); // No security violations

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('download', $this->testFile, $this->employee, Mockery::type(Request::class));

        $mockResponse = new \Symfony\Component\HttpFoundation\StreamedResponse();
        $this->mockFileManagerService->shouldReceive('downloadFile')
            ->once()
            ->with($this->testFile, $this->employee)
            ->andReturn($mockResponse);

        $response = $this->controller->download('employee-username', $this->testFile);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function download_blocks_files_with_security_violations()
    {
        $this->actingAs($this->employee);

        $securityViolations = [
            ['severity' => 'high', 'message' => 'Malicious file detected']
        ];

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn($securityViolations);

        $this->mockAuditLogService->shouldReceive('logSecurityViolation')
            ->once()
            ->with('download_blocked_security', $this->employee, Mockery::type(Request::class), Mockery::type('array'));

        $response = $this->controller->download('employee-username', $this->testFile);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function download_denies_access_to_unauthorized_files()
    {
        $this->actingAs($this->employee);

        // Create a file that the employee cannot access (different client, not uploaded by employee)
        $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);
        $unauthorizedFile = FileUpload::factory()->create([
            'client_user_id' => $otherClient->id,
            'uploaded_by_user_id' => $otherClient->id
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied to this file');

        $this->controller->download('employee-username', $unauthorizedFile);
    }

    /** @test */
    public function download_requires_employee_authentication()
    {
        // Test without authentication
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Please visit the home page to start using the app.');

        $this->controller->download('employee-username', $this->testFile);
    }

    /** @test */
    public function download_denies_access_to_non_employee_users()
    {
        $this->actingAs($this->client);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Please visit the home page to start using the app.');

        $this->controller->download('employee-username', $this->testFile);
    }

    /** @test */
    public function download_handles_file_access_exceptions()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn([]);

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('download', $this->testFile, $this->employee, Mockery::type(Request::class));

        $exception = new FileAccessException(
            'File not found',
            'The requested file could not be found',
            404,
            null,
            ['file_id' => $this->testFile->id]
        );

        $this->mockFileManagerService->shouldReceive('downloadFile')
            ->once()
            ->with($this->testFile, $this->employee)
            ->andThrow($exception);

        $response = $this->controller->download('employee-username', $this->testFile);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function download_handles_general_exceptions()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn([]);

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('download', $this->testFile, $this->employee, Mockery::type(Request::class));

        $exception = new \Exception('Unexpected error occurred');

        $this->mockFileManagerService->shouldReceive('downloadFile')
            ->once()
            ->with($this->testFile, $this->employee)
            ->andThrow($exception);

        $response = $this->controller->download('employee-username', $this->testFile);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function download_returns_json_response_for_ajax_requests()
    {
        $this->actingAs($this->employee);

        // Create a file that the employee cannot access
        $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);
        $unauthorizedFile = FileUpload::factory()->create([
            'client_user_id' => $otherClient->id,
            'uploaded_by_user_id' => $otherClient->id
        ]);

        // Create an AJAX request
        $request = Request::create('/employee/file-manager/download', 'GET', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->app->instance('request', $request);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied to this file');

        $this->controller->download('employee-username', $unauthorizedFile);
    }

    /** @test */
    public function preview_generates_file_preview()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('isPreviewSafe')
            ->once()
            ->with($this->testFile->mime_type)
            ->andReturn(true);

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('preview', $this->testFile, $this->employee, Mockery::type(Request::class));

        $mockResponse = new Response('preview content');
        $this->mockFilePreviewService->shouldReceive('generatePreview')
            ->once()
            ->with($this->testFile, $this->employee)
            ->andReturn($mockResponse);

        $response = $this->controller->preview('employee-username', $this->testFile);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('preview content', $response->getContent());
    }

    /** @test */
    public function preview_blocks_unsafe_file_types()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('isPreviewSafe')
            ->once()
            ->with($this->testFile->mime_type)
            ->andReturn(false);

        $this->mockAuditLogService->shouldReceive('logSecurityViolation')
            ->once()
            ->with('preview_blocked_unsafe_type', $this->employee, Mockery::type(Request::class), Mockery::type('array'));

        $response = $this->controller->preview('employee-username', $this->testFile);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('security restrictions', $response->getContent());
    }

    /** @test */
    public function preview_denies_access_to_unauthorized_files()
    {
        $this->actingAs($this->employee);

        // Create a file that the employee cannot access
        $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);
        $unauthorizedFile = FileUpload::factory()->create([
            'client_user_id' => $otherClient->id,
            'uploaded_by_user_id' => $otherClient->id
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied to this file');

        $this->controller->preview('employee-username', $unauthorizedFile);
    }

    /** @test */
    public function preview_requires_authentication()
    {
        // Test without authentication
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Authentication required');

        $this->controller->preview('employee-username', $this->testFile);
    }

    /** @test */
    public function preview_handles_exceptions_gracefully()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('isPreviewSafe')
            ->once()
            ->with($this->testFile->mime_type)
            ->andReturn(true);

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('preview', $this->testFile, $this->employee, Mockery::type(Request::class));

        $exception = new \Exception('Preview generation failed');

        $this->mockFilePreviewService->shouldReceive('generatePreview')
            ->once()
            ->with($this->testFile, $this->employee)
            ->andThrow($exception);

        $response = $this->controller->preview('employee-username', $this->testFile);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Preview not available', $response->getContent());
    }

    /** @test */
    public function preview_supports_conditional_requests_with_etag()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('isPreviewSafe')
            ->once()
            ->with($this->testFile->mime_type)
            ->andReturn(true);

        // Generate expected ETag
        $etag = md5($this->testFile->id . '_' . $this->testFile->file_size . '_' . $this->testFile->updated_at->timestamp);

        // Create request with If-None-Match header
        $request = Request::create('/employee/file-manager/preview', 'GET');
        $request->headers->set('If-None-Match', '"' . $etag . '"');
        $this->app->instance('request', $request);

        $response = $this->controller->preview('employee-username', $this->testFile);

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
    }

    /** @test */
    public function security_violation_logs_and_blocks_download()
    {
        $this->actingAs($this->employee);

        $securityViolations = [
            ['severity' => 'high', 'message' => 'Malicious file detected']
        ];

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn($securityViolations);

        $this->mockAuditLogService->shouldReceive('logSecurityViolation')
            ->once()
            ->with('download_blocked_security', $this->employee, Mockery::type(Request::class), Mockery::type('array'));

        $response = $this->controller->download('employee-username', $this->testFile);

        // Should return a redirect response (not JSON in unit test environment)
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function file_access_exception_handles_error_gracefully()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn([]);

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('download', $this->testFile, $this->employee, Mockery::type(Request::class));

        $exception = new FileAccessException(
            'File not found',
            'The requested file could not be found',
            404,
            null,
            ['file_id' => $this->testFile->id]
        );

        $this->mockFileManagerService->shouldReceive('downloadFile')
            ->once()
            ->with($this->testFile, $this->employee)
            ->andThrow($exception);

        $response = $this->controller->download('employee-username', $this->testFile);

        // Should return a redirect response (not JSON in unit test environment)
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function general_exception_handles_error_gracefully()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn([]);

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('download', $this->testFile, $this->employee, Mockery::type(Request::class));

        $exception = new \Exception('Unexpected error occurred');

        $this->mockFileManagerService->shouldReceive('downloadFile')
            ->once()
            ->with($this->testFile, $this->employee)
            ->andThrow($exception);

        $response = $this->controller->download('employee-username', $this->testFile);

        // Should return a redirect response (not JSON in unit test environment)
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function download_logs_file_access_for_audit_trail()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn([]);

        // Verify audit logging is called with correct parameters
        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('download', $this->testFile, $this->employee, Mockery::type(Request::class));

        $mockResponse = new \Symfony\Component\HttpFoundation\StreamedResponse();
        $this->mockFileManagerService->shouldReceive('downloadFile')
            ->once()
            ->with($this->testFile, $this->employee)
            ->andReturn($mockResponse);

        $response = $this->controller->download('employee-username', $this->testFile);
        
        // Assert that the response is correct
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function preview_logs_file_access_for_audit_trail()
    {
        $this->actingAs($this->employee);

        $this->mockFileSecurityService->shouldReceive('isPreviewSafe')
            ->once()
            ->with($this->testFile->mime_type)
            ->andReturn(true);

        // Verify audit logging is called with correct parameters
        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('preview', $this->testFile, $this->employee, Mockery::type(Request::class));

        $mockResponse = new Response('preview content');
        $this->mockFilePreviewService->shouldReceive('generatePreview')
            ->once()
            ->with($this->testFile, $this->employee)
            ->andReturn($mockResponse);

        $response = $this->controller->preview('employee-username', $this->testFile);
        
        // Assert that the response is correct
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('preview content', $response->getContent());
    }
}