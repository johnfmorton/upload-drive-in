<?php

namespace Tests\Unit\Mail;

use App\Mail\EmployeeVerificationMail;
use Tests\TestCase;

class EmployeeVerificationMailTest extends TestCase
{
    /** @test */
    public function it_uses_correct_template_path()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new EmployeeVerificationMail($verificationUrl, 'employee');

        $content = $mail->content();

        $this->assertEquals('emails.verification.employee-verification', $content->markdown);
    }

    /** @test */
    public function it_uses_correct_subject_line()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new EmployeeVerificationMail($verificationUrl, 'employee');

        $envelope = $mail->envelope();

        $this->assertEquals(__('messages.employee_verify_email_subject'), $envelope->subject);
    }

    /** @test */
    public function it_can_be_instantiated_and_rendered()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new EmployeeVerificationMail($verificationUrl, 'employee');

        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('employee', $mail->userRole);
        $this->assertNotEmpty($mail->companyName);
    }

    /** @test */
    public function it_has_no_attachments()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new EmployeeVerificationMail($verificationUrl, 'employee');

        $attachments = $mail->attachments();

        $this->assertEmpty($attachments);
    }
}