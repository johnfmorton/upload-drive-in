<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AnalyzeSecurityLogs;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Carbon\Carbon;

class AnalyzeSecurityLogsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test log directory
        $logPath = storage_path('logs');
        if (!File::exists($logPath)) {
            File::makeDirectory($logPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test log files
        $logPath = storage_path('logs');
        $testFiles = File::glob("{$logPath}/security-*.log");
        foreach ($testFiles as $file) {
            File::delete($file);
        }
        
        parent::tearDown();
    }

    public function test_command_analyzes_security_logs(): void
    {
        $this->createTestLogFile();
        
        $this->artisan('security:analyze-logs', ['--days' => 1])
            ->expectsOutput('Analyzing security logs for the last 1 days...')
            ->assertExitCode(0);
    }

    public function test_command_handles_no_log_files(): void
    {
        $this->artisan('security:analyze-logs', ['--days' => 1])
            ->expectsOutput('No security log files found for the specified period.')
            ->assertExitCode(0);
    }

    public function test_command_filters_by_event_type(): void
    {
        $this->createTestLogFile();
        
        $this->artisan('security:analyze-logs', [
            '--days' => 1,
            '--type' => 'rate_limit_exceeded'
        ])->assertExitCode(0);
    }

    public function test_command_filters_by_user_id(): void
    {
        $this->createTestLogFile();
        
        $this->artisan('security:analyze-logs', [
            '--days' => 1,
            '--user' => '123'
        ])->assertExitCode(0);
    }

    public function test_command_filters_by_ip_address(): void
    {
        $this->createTestLogFile();
        
        $this->artisan('security:analyze-logs', [
            '--days' => 1,
            '--ip' => '192.168.1.1'
        ])->assertExitCode(0);
    }

    private function createTestLogFile(): void
    {
        $logPath = storage_path('logs');
        $date = Carbon::now()->format('Y-m-d');
        $filename = "security-{$date}.log";
        
        $logContent = $this->generateTestLogContent();
        
        File::put("{$logPath}/{$filename}", $logContent);
    }

    private function generateTestLogContent(): string
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        
        return "[{$timestamp}] production.INFO: Token Security Event " . json_encode([
            'event' => 'rate_limit_exceeded',
            'timestamp' => $timestamp,
            'data' => [
                'user_id' => 123,
                'attempts' => 5,
                'limit' => 5,
                'ip_address' => '192.168.1.1',
            ]
        ]) . "\n" .
        "[{$timestamp}] production.INFO: Token Security Event " . json_encode([
            'event' => 'token_refresh_failure',
            'timestamp' => $timestamp,
            'data' => [
                'user_id' => 456,
                'error_message' => 'Invalid refresh token',
                'ip_address' => '192.168.1.2',
            ]
        ]) . "\n";
    }
}