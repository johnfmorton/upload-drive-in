<?php

namespace Tests\Unit\Services;

use App\Mail\AdminVerificationMail;
use App\Mail\ClientVerificationMail;
use App\Mail\EmployeeVerificationMail;
use App\Models\User;
use App\Services\VerificationMailFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerificationMailFactoryTest extends TestCase
{
    use RefreshDatabase;

    private VerificationMailFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new VerificationMailFactory();
    }

    #[Test]
    public function it_creates_admin_verification_mail_for_admin_user()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForUser($user, $verificationUrl);

        $this->assertInstanceOf(AdminVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('admin', $mail->userRole);
    }

    #[Test]
    public function it_creates_employee_verification_mail_for_employee_user()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForUser($user, $verificationUrl);

        $this->assertInstanceOf(EmployeeVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('employee', $mail->userRole);
    }

    #[Test]
    public function it_creates_client_verification_mail_for_client_user()
    {
        $user = User::factory()->create(['role' => 'client']);
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForUser($user, $verificationUrl);

        $this->assertInstanceOf(ClientVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('client', $mail->userRole);
    }

    #[Test]
    public function it_creates_client_verification_mail_for_null_user()
    {
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForUser(null, $verificationUrl);

        $this->assertInstanceOf(ClientVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('client', $mail->userRole);
    }

    #[Test]
    public function it_creates_admin_verification_mail_for_admin_context()
    {
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForContext('admin', $verificationUrl);

        $this->assertInstanceOf(AdminVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('admin', $mail->userRole);
    }

    #[Test]
    public function it_creates_employee_verification_mail_for_employee_context()
    {
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForContext('employee', $verificationUrl);

        $this->assertInstanceOf(EmployeeVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('employee', $mail->userRole);
    }

    #[Test]
    public function it_creates_client_verification_mail_for_client_context()
    {
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForContext('client', $verificationUrl);

        $this->assertInstanceOf(ClientVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('client', $mail->userRole);
    }

    #[Test]
    public function it_creates_client_verification_mail_for_unknown_context()
    {
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForContext('unknown', $verificationUrl);

        $this->assertInstanceOf(ClientVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('client', $mail->userRole);
    }

    #[Test]
    public function it_handles_case_insensitive_contexts()
    {
        $verificationUrl = 'https://example.com/verify';

        $adminMail = $this->factory->createForContext('ADMIN', $verificationUrl);
        $employeeMail = $this->factory->createForContext('Employee', $verificationUrl);
        $clientMail = $this->factory->createForContext('CLIENT', $verificationUrl);

        $this->assertInstanceOf(AdminVerificationMail::class, $adminMail);
        $this->assertInstanceOf(EmployeeVerificationMail::class, $employeeMail);
        $this->assertInstanceOf(ClientVerificationMail::class, $clientMail);
    }

    #[Test]
    public function it_returns_available_contexts()
    {
        $contexts = $this->factory->getAvailableContexts();

        $this->assertEquals(['admin', 'employee', 'client'], $contexts);
    }

    #[Test]
    public function it_determines_context_for_admin_user()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $context = $this->factory->determineContextForUser($user);

        $this->assertEquals('admin', $context);
    }

    #[Test]
    public function it_determines_context_for_employee_user()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $context = $this->factory->determineContextForUser($user);

        $this->assertEquals('employee', $context);
    }

    #[Test]
    public function it_determines_context_for_client_user()
    {
        $user = User::factory()->create(['role' => 'client']);

        $context = $this->factory->determineContextForUser($user);

        $this->assertEquals('client', $context);
    }

    #[Test]
    public function it_determines_context_for_null_user()
    {
        $context = $this->factory->determineContextForUser(null);

        $this->assertEquals('client', $context);
    }

    #[Test]
    public function it_handles_empty_string_context()
    {
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForContext('', $verificationUrl);

        $this->assertInstanceOf(ClientVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('client', $mail->userRole);
    }

    #[Test]
    public function it_handles_whitespace_context()
    {
        $verificationUrl = 'https://example.com/verify';

        $mail = $this->factory->createForContext('  admin  ', $verificationUrl);

        // Should still fallback to client since the match is exact after strtolower
        $this->assertInstanceOf(ClientVerificationMail::class, $mail);
        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('client', $mail->userRole);
    }

    #[Test]
    public function it_creates_different_instances_for_multiple_calls()
    {
        $verificationUrl = 'https://example.com/verify';

        $mail1 = $this->factory->createForContext('admin', $verificationUrl);
        $mail2 = $this->factory->createForContext('admin', $verificationUrl);

        $this->assertInstanceOf(AdminVerificationMail::class, $mail1);
        $this->assertInstanceOf(AdminVerificationMail::class, $mail2);
        $this->assertNotSame($mail1, $mail2); // Different instances
        $this->assertEquals($mail1->verificationUrl, $mail2->verificationUrl);
        $this->assertEquals($mail1->userRole, $mail2->userRole);
    }
}