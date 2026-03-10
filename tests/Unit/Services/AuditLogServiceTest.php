<?php

namespace Tests\Unit\Services;

use App\Models\FileUpload;
use App\Models\User;
use App\Services\AuditLogService;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $auditLogService;
    private User $user;
    private FileUpload $file;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditLogService = new AuditLogService();
        $this->user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->file = FileUpload::factory()->create();

        $this->request = Request::create('/test', 'GET');
        $this->request->setUserResolver(fn() => $this->user);
        $this->request->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->request->headers->set('User-Agent', 'Test Browser');
    }

    /**
     * Set up Log spy with a channel mock that captures calls.
     *
     * @param string $channel The channel name to mock
     * @return \Mockery\MockInterface The channel logger spy
     */
    private function spyOnLogWithChannel(string $channel = 'audit'): \Mockery\MockInterface
    {
        $channelLogger = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        Log::shouldReceive('channel')->with($channel)->andReturn($channelLogger);
        return $channelLogger;
    }

    /** @test */
    public function it_logs_file_access_with_complete_information()
    {
        $auditLogger = $this->spyOnLogWithChannel('audit');
        Log::shouldReceive('info')->withAnyArgs()->atLeast()->once();

        $this->auditLogService->logFileAccess(
            'download',
            $this->file,
            $this->user,
            $this->request
        );

        $auditLogger->shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return $message === 'File download' &&
                   $context['action'] === 'download' &&
                   $context['user_id'] === $this->user->id &&
                   $context['user_email'] === $this->user->email &&
                   $context['user_role'] === $this->user->role->value &&
                   $context['file_id'] === $this->file->id &&
                   $context['file_name'] === $this->file->original_filename &&
                   $context['ip_address'] === '192.168.1.1' &&
                   isset($context['timestamp']);
        })->atLeast()->once();
    }

    /** @test */
    public function it_logs_bulk_operations_with_file_counts()
    {
        $auditLogger = $this->spyOnLogWithChannel('audit');
        Log::shouldReceive('info')->withAnyArgs()->atLeast()->once();

        $fileIds = [1, 2, 3];

        $this->auditLogService->logBulkFileOperation(
            'delete',
            $fileIds,
            $this->user,
            $this->request
        );

        $auditLogger->shouldHaveReceived('info')->withArgs(function ($message, $context) use ($fileIds) {
            return $message === 'Bulk file delete' &&
                   $context['action'] === 'bulk_delete' &&
                   $context['file_count'] === count($fileIds) &&
                   $context['file_ids'] === $fileIds &&
                   $context['user_id'] === $this->user->id;
        })->atLeast()->once();
    }

    /** @test */
    public function it_logs_security_violations_with_high_priority()
    {
        $securityLogger = $this->spyOnLogWithChannel('security');
        Log::shouldReceive('warning')->withAnyArgs()->atLeast()->once();

        $this->auditLogService->logSecurityViolation(
            'unauthorized_access',
            $this->user,
            $this->request,
            ['resource' => 'sensitive_file']
        );

        $securityLogger->shouldHaveReceived('warning')->withArgs(function ($message, $context) {
            return str_contains($message, 'Security violation: unauthorized_access') &&
                   $context['violation_type'] === 'unauthorized_access' &&
                   $context['user_id'] === $this->user->id &&
                   $context['context']['resource'] === 'sensitive_file';
        })->atLeast()->once();
    }

    /** @test */
    public function it_logs_access_denied_events()
    {
        $securityLogger = $this->spyOnLogWithChannel('security');
        Log::shouldReceive('info')->withAnyArgs()->atLeast()->once();

        $this->auditLogService->logAccessDenied(
            'file_123',
            $this->user,
            $this->request,
            'insufficient_permissions'
        );

        $securityLogger->shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return str_contains($message, 'Access denied: file_123') &&
                   $context['event'] === 'access_denied' &&
                   $context['resource'] === 'file_123' &&
                   $context['reason'] === 'insufficient_permissions' &&
                   $context['user_id'] === $this->user->id;
        })->atLeast()->once();
    }

    /** @test */
    public function it_includes_session_and_request_tracking()
    {
        $auditLogger = $this->spyOnLogWithChannel('audit');
        Log::shouldReceive('info')->withAnyArgs()->atLeast()->once();

        $this->request->headers->set('X-Request-ID', 'test-request-123');

        $this->auditLogService->logFileAccess(
            'view',
            $this->file,
            $this->user,
            $this->request
        );

        $auditLogger->shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return array_key_exists('session_id', $context) &&
                   $context['request_id'] === 'test-request-123';
        })->atLeast()->once();
    }

    /** @test */
    public function it_logs_to_both_audit_and_main_channels()
    {
        $auditLogger = $this->spyOnLogWithChannel('audit');

        Log::shouldReceive('info')->withArgs(function ($message, $context) {
            return str_contains($message, 'Audit: File download') &&
                   $context['user'] === $this->user->email &&
                   $context['file'] === $this->file->original_filename;
        })->atLeast()->once();

        $this->auditLogService->logFileAccess(
            'download',
            $this->file,
            $this->user,
            $this->request
        );

        // Check audit channel
        $auditLogger->shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return $message === 'File download' &&
                   $context['action'] === 'download';
        })->atLeast()->once();
    }

    /** @test */
    public function it_handles_additional_data_in_logs()
    {
        $auditLogger = $this->spyOnLogWithChannel('audit');
        Log::shouldReceive('info')->withAnyArgs()->atLeast()->once();

        $additionalData = [
            'operation_type' => 'bulk',
            'affected_count' => 5
        ];

        $this->auditLogService->logFileAccess(
            'update',
            $this->file,
            $this->user,
            $this->request,
            $additionalData
        );

        $auditLogger->shouldHaveReceived('info')->withArgs(function ($message, $context) use ($additionalData) {
            return $context['additional_data'] === $additionalData;
        })->atLeast()->once();
    }

    /** @test */
    public function it_generates_unique_request_ids_when_not_provided()
    {
        $auditLogger = $this->spyOnLogWithChannel('audit');
        Log::shouldReceive('info')->withAnyArgs()->atLeast()->once();

        $this->auditLogService->logFileAccess(
            'view',
            $this->file,
            $this->user,
            $this->request
        );

        $auditLogger->shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return isset($context['request_id']) && !empty($context['request_id']);
        })->atLeast()->once();
    }
}
