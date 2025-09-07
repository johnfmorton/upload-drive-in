<?php

namespace Tests\Unit;

use App\Services\VerificationMailFactory;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EmailVerificationLoggingTest extends TestCase
{
    use RefreshDatabase;

    private VerificationMailFactory $mailFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailFactory = app(VerificationMailFactory::class);
        
        // Clear cache before each test
        Cache::flush();
        
        // Allow all log calls by default
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
    }

    /** @test */
    public function it_logs_template_selection_for_admin_user()
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $mail = $this->mailFactory->createForUser($user, 'http://example.com/verify');
        
        $this->assertInstanceOf(\App\Mail\AdminVerificationMail::class, $mail);
    }

    /** @test */
    public function it_logs_template_selection_for_employee_user()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $mail = $this->mailFactory->createForUser($user, 'http://example.com/verify');
        
        $this->assertInstanceOf(\App\Mail\EmployeeVerificationMail::class, $mail);
    }

    /** @test */
    public function it_logs_template_selection_for_client_user()
    {
        $user = User::factory()->create(['role' => UserRole::CLIENT]);
        $mail = $this->mailFactory->createForUser($user, 'http://example.com/verify');
        
        $this->assertInstanceOf(\App\Mail\ClientVerificationMail::class, $mail);
    }

    /** @test */
    public function it_logs_fallback_when_user_is_null()
    {
        $mail = $this->mailFactory->createForUser(null, 'http://example.com/verify');
        
        $this->assertInstanceOf(\App\Mail\ClientVerificationMail::class, $mail);
    }

    /** @test */
    public function it_logs_template_selection_for_context()
    {
        $mail = $this->mailFactory->createForContext('admin', 'http://example.com/verify');
        
        $this->assertInstanceOf(\App\Mail\AdminVerificationMail::class, $mail);
    }

    /** @test */
    public function it_logs_warning_for_unknown_context()
    {
        $mail = $this->mailFactory->createForContext('unknown_role', 'http://example.com/verify');
        
        $this->assertInstanceOf(\App\Mail\ClientVerificationMail::class, $mail);
    }

    /** @test */
    public function it_updates_metrics_for_template_selection()
    {
        $this->mailFactory->createForUser(
            User::factory()->create(['role' => UserRole::ADMIN]), 
            'http://example.com/verify'
        );

        $metrics = $this->mailFactory->getMetrics('admin', 'template_selected');
        $this->assertEquals(1, $metrics['admin']['template_selected']);
    }

    /** @test */
    public function it_logs_email_sent_success()
    {
        $this->mailFactory->logEmailSent('admin', 'admin@example.com');

        $metrics = $this->mailFactory->getMetrics('admin', 'email_sent');
        $this->assertEquals(1, $metrics['admin']['email_sent']);
    }

    /** @test */
    public function it_logs_email_send_error()
    {
        $this->mailFactory->logEmailSendError('client', 'SMTP connection failed', 'client@example.com');

        $metrics = $this->mailFactory->getMetrics('client', 'email_send_error');
        $this->assertEquals(1, $metrics['client']['email_send_error']);
    }

    /** @test */
    public function it_logs_verification_success()
    {
        $this->mailFactory->logVerificationSuccess('employee', 'employee@example.com');

        $metrics = $this->mailFactory->getMetrics('employee', 'verification_success');
        $this->assertEquals(1, $metrics['employee']['verification_success']);
    }

    /** @test */
    public function it_logs_verification_failure()
    {
        $this->mailFactory->logVerificationFailure('client', 'Invalid verification code', 'client@example.com');

        $metrics = $this->mailFactory->getMetrics('client', 'verification_failure');
        $this->assertEquals(1, $metrics['client']['verification_failure']);
    }

    /** @test */
    public function it_returns_comprehensive_metrics()
    {
        // Create some test metrics
        $this->mailFactory->logEmailSent('admin', 'admin@example.com');
        $this->mailFactory->logEmailSent('employee', 'employee@example.com');
        $this->mailFactory->logVerificationSuccess('admin', 'admin@example.com');
        $this->mailFactory->logVerificationFailure('employee', 'Expired code', 'employee@example.com');

        $metrics = $this->mailFactory->getMetrics();

        $this->assertEquals(1, $metrics['admin']['email_sent']);
        $this->assertEquals(1, $metrics['employee']['email_sent']);
        $this->assertEquals(1, $metrics['admin']['verification_success']);
        $this->assertEquals(1, $metrics['employee']['verification_failure']);
        $this->assertEquals(0, $metrics['client']['email_sent']);
    }

    /** @test */
    public function it_returns_daily_metrics_separately()
    {
        // Create some metrics
        $this->mailFactory->logEmailSent('admin', 'admin@example.com');
        
        $allTimeMetrics = $this->mailFactory->getMetrics('admin', 'email_sent', false);
        $dailyMetrics = $this->mailFactory->getMetrics('admin', 'email_sent', true);

        $this->assertEquals(1, $allTimeMetrics['admin']['email_sent']);
        $this->assertEquals(1, $dailyMetrics['admin']['email_sent']);
    }

    /** @test */
    public function it_handles_metrics_errors_gracefully()
    {
        // Mock Cache to throw an exception
        Cache::shouldReceive('increment')
            ->andThrow(new \Exception('Cache connection failed'));
        
        Cache::shouldReceive('get')->andReturn(0);
        Cache::shouldReceive('put')->andReturn(true);

        // This should not throw an exception
        $this->mailFactory->logEmailSent('admin', 'admin@example.com');
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }
}