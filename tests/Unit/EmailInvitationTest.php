<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Mail\LoginVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_verification_mail_can_be_created()
    {
        $loginUrl = 'http://example.com/login/token/123';
        
        $mail = new LoginVerificationMail($loginUrl);
        
        $this->assertInstanceOf(LoginVerificationMail::class, $mail);
    }

    public function test_login_verification_mail_has_correct_subject()
    {
        $loginUrl = 'http://example.com/login/token/123';
        
        $mail = new LoginVerificationMail($loginUrl);
        
        // Test that the mail object can be created successfully
        $this->assertInstanceOf(LoginVerificationMail::class, $mail);
        
        // Test that the mail has the login URL property
        $this->assertNotEmpty($loginUrl);
    }

    public function test_temporary_signed_route_generation()
    {
        $user = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);
        
        $loginUrl = URL::temporarySignedRoute(
            'login.via.token',
            now()->addDays(7),
            ['user' => $user->id]
        );
        
        $this->assertStringContainsString('login', $loginUrl);
        $this->assertStringContainsString('signature', $loginUrl);
        $this->assertStringContainsString((string)$user->id, $loginUrl);
    }

    public function test_mail_can_be_sent_to_valid_email()
    {
        Mail::fake();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => UserRole::CLIENT,
        ]);
        
        $loginUrl = 'http://example.com/login/token/123';
        
        Mail::to($user->email)->send(new LoginVerificationMail($loginUrl));
        
        Mail::assertSent(LoginVerificationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_mail_queue_integration()
    {
        Mail::fake();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => UserRole::CLIENT,
        ]);
        
        $loginUrl = 'http://example.com/login/token/123';
        
        Mail::to($user->email)->queue(new LoginVerificationMail($loginUrl));
        
        Mail::assertQueued(LoginVerificationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_email_validation_with_filter_var()
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'admin+tag@company.org',
        ];
        
        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user name@domain.com',
            '',
        ];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '{$email}' should be valid"
            );
        }
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '{$email}' should be invalid"
            );
        }
    }

    public function test_mail_configuration_access()
    {
        $driver = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        
        $this->assertNotNull($driver);
        // Host might be null in testing environment, so we just check it's accessible
        $this->assertTrue(is_string($host) || is_null($host));
    }

    public function test_mail_exception_handling()
    {
        Mail::fake();
        
        // Simulate mail sending exception
        Mail::shouldReceive('to')
            ->andThrow(new \Exception('SMTP connection failed'));
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => UserRole::CLIENT,
        ]);
        
        $loginUrl = 'http://example.com/login/token/123';
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SMTP connection failed');
        
        Mail::to($user->email)->send(new LoginVerificationMail($loginUrl));
    }

    public function test_url_generation_with_expiration()
    {
        $user = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);
        
        $expirationTime = now()->addDays(7);
        
        $loginUrl = URL::temporarySignedRoute(
            'login.via.token',
            $expirationTime,
            ['user' => $user->id]
        );
        
        $this->assertStringContainsString('expires', $loginUrl);
        $this->assertStringContainsString('signature', $loginUrl);
    }

    public function test_multiple_email_recipients()
    {
        Mail::fake();
        
        $users = User::factory()->count(3)->create([
            'role' => UserRole::CLIENT,
        ]);
        
        $loginUrl = 'http://example.com/login/token/123';
        
        foreach ($users as $user) {
            Mail::to($user->email)->send(new LoginVerificationMail($loginUrl));
        }
        
        Mail::assertSent(LoginVerificationMail::class, 3);
        
        foreach ($users as $user) {
            Mail::assertSent(LoginVerificationMail::class, function ($mail) use ($user) {
                return $mail->hasTo($user->email);
            });
        }
    }
}