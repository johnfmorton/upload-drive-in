<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\Admin\FileManagerController;
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

class FileManagerControllerTest extends TestCase
{
    use RefreshDatabase;

    private FileManagerController $controller;
    private FileManagerService $mockFileManagerService;
    private FilePreviewService $mockFilePreviewService;
    private AuditLogService $mockAuditLogService;
    private FileSecurityService $mockFileSecurityService;
    private User $admin;
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

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->testFile = FileUpload::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function index_returns_view_for_admin_user()
    {
        $this->actingAs($this->admin);

        $mockFiles = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        $mockStatistics = ['total_files' => 0];
        $mockFilterOptions = ['file_types' => []];

        $this->mockFileManagerService->shouldReceive('getFilteredFiles')
            ->once()
            ->andReturn($mockFiles);
        
        $this->mockFileManagerService->shouldReceive('getFileStatistics')
            ->once()
            ->andReturn($mockStatistics);
        
        $this->mockFileManagerService->shouldReceive('getFilterOptions')
            ->once()
            ->andReturn($mockFilterOptions);

        $request = Request::create('/admin/file-manager');
        $response = $this->controller->index($request);

        $this->assertInstanceOf(View::class, $response);
    }

    /** @test */
    public function index_returns_json_for_ajax_requests()
    {
        $this->actingAs($this->admin);

        $mockFiles = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        $mockStatistics = ['total_files' => 0];
        $mockFilterOptions = ['file_types' => []];

        $this->mockFileManagerService->shouldReceive('getFilteredFiles')
            ->once()
            ->andReturn($mockFiles);
        
        $this->mockFileManagerService->shouldReceive('getFileStatistics')
            ->once()
            ->andReturn($mockStatistics);
        
        $this->mockFileManagerService->shouldReceive('getFilterOptions')
            ->once()
            ->andReturn($mockFilterOptions);

        $request = Request::create('/admin/file-manager', 'GET', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $response = $this->controller->index($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
    }

    /** @test */
    public function index_denies_access_to_non_admin_users()
    {
        $this->actingAs($this->client);

        $request = Request::create('/admin/file-manager');
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Please visit the home page to start using the app.');
        
        $this->controller->index($request);
    }

    /** @test */
    public function show_returns_file_details_for_admin()
    {
        $this->actingAs($this->admin);

        $mockFileDetails = [
            'file' => $this->testFile,
            'size_formatted' => '1.00 KB'
        ];

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('view', $this->testFile, $this->admin, Mockery::type(Request::class));

        $this->mockFileManagerService->shouldReceive('getFileDetails')
            ->once()
            ->with($this->testFile)
            ->andReturn($mockFileDetails);

        $response = $this->controller->show($this->testFile);

        $this->assertInstanceOf(View::class, $response);
    }

    /** @test */
    public function update_validates_input_and_updates_file()
    {
        $this->actingAs($this->admin);

        $updateData = ['message' => 'Updated message'];
        $updatedFile = $this->testFile;
        $updatedFile->message = 'Updated message';

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('update', $this->testFile, $this->admin, Mockery::type(Request::class), Mockery::type('array'));

        $this->mockFileManagerService->shouldReceive('updateFileMetadata')
            ->once()
            ->with($this->testFile, $updateData)
            ->andReturn($updatedFile);

        $request = Request::create('/admin/file-manager/' . $this->testFile->id, 'PUT', $updateData);
        $response = $this->controller->update($request, $this->testFile);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function destroy_deletes_file_successfully()
    {
        $this->actingAs($this->admin);

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('delete', $this->testFile, $this->admin, Mockery::type(Request::class));

        $this->mockFileManagerService->shouldReceive('deleteFile')
            ->once()
            ->with($this->testFile);

        $response = $this->controller->destroy($this->testFile);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function bulk_destroy_deletes_multiple_files()
    {
        $this->actingAs($this->admin);

        $fileIds = [1, 2, 3];
        $deletedCount = 3;

        $this->mockAuditLogService->shouldReceive('logBulkFileOperation')
            ->once()
            ->with('delete', $fileIds, $this->admin, Mockery::type(Request::class));

        $this->mockFileManagerService->shouldReceive('bulkDeleteFiles')
            ->once()
            ->with($fileIds)
            ->andReturn($deletedCount);

        $request = Request::create('/admin/file-manager/bulk-destroy', 'DELETE', ['file_ids' => $fileIds]);
        $response = $this->controller->bulkDestroy($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function process_pending_handles_pending_uploads()
    {
        $this->actingAs($this->admin);

        $result = [
            'count' => 5,
            'message' => 'Processing 5 pending uploads'
        ];

        $this->mockFileManagerService->shouldReceive('processPendingUploads')
            ->once()
            ->andReturn($result);

        $response = $this->controller->processPending();

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function download_validates_security_and_downloads_file()
    {
        $this->actingAs($this->admin);

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn([]); // No security violations

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('download', $this->testFile, $this->admin, Mockery::type(Request::class));

        $mockResponse = new \Symfony\Component\HttpFoundation\StreamedResponse();
        $this->mockFileManagerService->shouldReceive('downloadFile')
            ->once()
            ->with($this->testFile, $this->admin)
            ->andReturn($mockResponse);

        $response = $this->controller->download($this->testFile);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function download_blocks_files_with_security_violations()
    {
        $this->actingAs($this->admin);

        $securityViolations = [
            ['severity' => 'high', 'message' => 'Malicious file detected']
        ];

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->once()
            ->with($this->testFile)
            ->andReturn($securityViolations);

        $this->mockAuditLogService->shouldReceive('logSecurityViolation')
            ->once()
            ->with('download_blocked_security', $this->admin, Mockery::type(Request::class), Mockery::type('array'));

        $response = $this->controller->download($this->testFile);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function bulk_download_creates_zip_archive()
    {
        $this->actingAs($this->admin);

        $fileIds = [1, 2, 3];
        $files = collect([
            FileUpload::factory()->make(['id' => 1]),
            FileUpload::factory()->make(['id' => 2]),
            FileUpload::factory()->make(['id' => 3])
        ]);

        // Mock the FileUpload query
        FileUpload::shouldReceive('whereIn')
            ->with('id', $fileIds)
            ->andReturnSelf();
        FileUpload::shouldReceive('get')
            ->andReturn($files);

        $this->mockFileSecurityService->shouldReceive('validateExistingFile')
            ->times(3)
            ->andReturn([]); // No security violations

        $this->mockAuditLogService->shouldReceive('logBulkFileOperation')
            ->once()
            ->with('download', $fileIds, $this->admin, Mockery::type(Request::class));

        $mockResponse = new \Symfony\Component\HttpFoundation\StreamedResponse();
        $this->mockFileManagerService->shouldReceive('bulkDownloadFiles')
            ->once()
            ->with($fileIds)
            ->andReturn($mockResponse);

        $request = Request::create('/admin/file-manager/bulk-download', 'POST', ['file_ids' => $fileIds]);
        $response = $this->controller->bulkDownload($request);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function preview_generates_file_preview()
    {
        $this->actingAs($this->admin);

        $this->mockFileSecurityService->shouldReceive('isPreviewSafe')
            ->once()
            ->with($this->testFile->mime_type)
            ->andReturn(true);

        $this->mockAuditLogService->shouldReceive('logFileAccess')
            ->once()
            ->with('preview', $this->testFile, $this->admin, Mockery::type(Request::class));

        $mockResponse = new Response('preview content');
        $this->mockFilePreviewService->shouldReceive('generatePreview')
            ->once()
            ->with($this->testFile, $this->admin)
            ->andReturn($mockResponse);

        $response = $this->controller->preview($this->testFile);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('preview content', $response->getContent());
    }

    /** @test */
    public function preview_blocks_unsafe_file_types()
    {
        $this->actingAs($this->admin);

        $this->mockFileSecurityService->shouldReceive('isPreviewSafe')
            ->once()
            ->with($this->testFile->mime_type)
            ->andReturn(false);

        $this->mockAuditLogService->shouldReceive('logSecurityViolation')
            ->once()
            ->with('preview_blocked_unsafe_type', $this->admin, Mockery::type(Request::class), Mockery::type('array'));

        $response = $this->controller->preview($this->testFile);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('security restrictions', $response->getContent());
    }

    /** @test */
    public function thumbnail_generates_image_thumbnail()
    {
        $this->actingAs($this->admin);
        
        $imageFile = FileUpload::factory()->create(['mime_type' => 'image/jpeg']);

        $mockResponse = new Response('thumbnail content');
        $this->mockFilePreviewService->shouldReceive('getThumbnail')
            ->once()
            ->with($imageFile, $this->admin)
            ->andReturn($mockResponse);

        $response = $this->controller->thumbnail($imageFile);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('thumbnail content', $response->getContent());
    }

    /** @test */
    public function thumbnail_blocks_non_image_files()
    {
        $this->actingAs($this->admin);
        
        $nonImageFile = FileUpload::factory()->create(['mime_type' => 'application/pdf']);

        $this->mockAuditLogService->shouldReceive('logSecurityViolation')
            ->once()
            ->with('thumbnail_blocked_non_image', $this->admin, Mockery::type(Request::class), Mockery::type('array'));

        $response = $this->controller->thumbnail($nonImageFile);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('non-image files', $response->getContent());
    }

    /** @test */
    public function it_handles_file_manager_exceptions_gracefully()
    {
        $this->actingAs($this->admin);

        $exception = new FileManagerException(
            'Test error',
            'User friendly message',
            500,
            null,
            ['test' => 'context']
        );

        $this->mockFileManagerService->shouldReceive('getFilteredFiles')
            ->once()
            ->andThrow($exception);

        $request = Request::create('/admin/file-manager');
        $response = $this->controller->index($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    /** @test */
    public function it_handles_file_access_exceptions()
    {
        $this->actingAs($this->admin);

        $exception = new FileAccessException(
            'Access denied',
            'You cannot access this file',
            403,
            null,
            ['file_id' => $this->testFile->id]
        );

        $this->mockFileManagerService->shouldReceive('getFileDetails')
            ->once()
            ->with($this->testFile)
            ->andThrow($exception);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('You cannot access this file');

        $this->controller->show($this->testFile);
    }

    /** @test */
    public function it_requires_authentication_for_preview_and_thumbnail()
    {
        // Test without authentication
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Authentication required');

        $this->controller->preview($this->testFile);
    }

    /** @test */
    public function it_validates_bulk_operation_input()
    {
        $this->actingAs($this->admin);

        // Test with invalid file IDs
        $request = Request::create('/admin/file-manager/bulk-destroy', 'DELETE', ['file_ids' => ['invalid']]);
        
        $response = $this->controller->bulkDestroy($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }
}