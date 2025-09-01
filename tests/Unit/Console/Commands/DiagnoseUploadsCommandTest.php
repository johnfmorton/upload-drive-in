<?php

namespace Tests\Unit\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\DiagnoseUploadsCommand;
use App\Services\UploadDiagnosticService;
use App\Services\UploadRecoveryService;
use Mockery;

class DiagnoseUploadsCommandTest extends TestCase
{
    private $diagnosticService;
    private $recoveryService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->diagnosticService = Mockery::mock(UploadDiagnosticService::class);
        $this->recoveryService = Mockery::mock(UploadRecoveryService::class);
        
        $this->app->instance(UploadDiagnosticService::class, $this->diagnosticService);
        $this->app->instance(UploadRecoveryService::class, $this->recoveryService);
    }

    public function test_command_runs_successfully()
    {
        // Mock the recovery service to return empty collection for stuck uploads
        $this->recoveryService
            ->shouldReceive('detectStuckUploads')
            ->andReturn(collect([]));

        // Mock the diagnostic service for API connectivity check
        $this->diagnosticService
            ->shouldReceive('validateGoogleDriveConnectivity')
            ->andReturn(['status' => 'connected']);

        $this->artisan('uploads:diagnose --check=queue')
            ->expectsOutput('🔍 Upload System Diagnostics')
            ->expectsOutput('==========================');
    }

    public function test_command_runs_with_queue_check()
    {
        // Mock the recovery service
        $this->recoveryService
            ->shouldReceive('detectStuckUploads')
            ->andReturn(collect([]));

        $this->artisan('uploads:diagnose --check=queue')
            ->expectsOutputToContain('Queue System Check');
    }

    public function test_command_runs_with_storage_check()
    {
        // Mock the recovery service
        $this->recoveryService
            ->shouldReceive('detectStuckUploads')
            ->andReturn(collect([]));

        $this->artisan('uploads:diagnose --check=storage')
            ->expectsOutputToContain('Storage System Check');
    }

    public function test_command_runs_with_api_check()
    {
        // Mock the recovery service
        $this->recoveryService
            ->shouldReceive('detectStuckUploads')
            ->andReturn(collect([]));

        $this->artisan('uploads:diagnose --check=api')
            ->expectsOutputToContain('Google Drive API Check');
    }

    public function test_command_runs_with_tokens_check()
    {
        // Mock the recovery service
        $this->recoveryService
            ->shouldReceive('detectStuckUploads')
            ->andReturn(collect([]));

        $this->artisan('uploads:diagnose --check=tokens')
            ->expectsOutputToContain('Google Drive Tokens Check');
    }

    public function test_command_runs_with_uploads_check()
    {
        // Mock the recovery service
        $this->recoveryService
            ->shouldReceive('detectStuckUploads')
            ->andReturn(collect([]));

        $this->artisan('uploads:diagnose --check=uploads')
            ->expectsOutputToContain('Upload Status Check');
    }

    public function test_command_outputs_json_format()
    {
        // Mock the recovery service
        $this->recoveryService
            ->shouldReceive('detectStuckUploads')
            ->andReturn(collect([]));

        $this->artisan('uploads:diagnose --check=queue --json')
            ->expectsOutputToContain('queue_system');
    }

    public function test_command_shows_help()
    {
        $this->artisan('uploads:diagnose --help')
            ->expectsOutputToContain('Perform comprehensive system health checks')
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}