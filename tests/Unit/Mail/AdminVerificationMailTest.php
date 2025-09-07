<?php

namespace Tests\Unit\Mail;

use App\Mail\AdminVerificationMail;
use Tests\TestCase;

class AdminVerificationMailTest extends TestCase
{
    /** @test */
    public function it_uses_correct_template_path()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new AdminVerificationMail($verificationUrl, 'admin');

        $content = $mail->content();

        $this->assertEquals('emails.verification.admin-verification', $content->markdown);
    }

    /** @test */
    public function it_uses_correct_subject_line()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new AdminVerificationMail($verificationUrl, 'admin');

        $envelope = $mail->envelope();

        $this->assertEquals(__('messages.admin_verify_email_subject'), $envelope->subject);
    }

    /** @test */
    public function it_can_be_instantiated_and_rendered()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new AdminVerificationMail($verificationUrl, 'admin');

        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('admin', $mail->userRole);
        $this->assertNotEmpty($mail->companyName);
    }

    /** @test */
    public function it_has_no_attachments()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new AdminVerificationMail($verificationUrl, 'admin');

        $attachments = $mail->attachments();

        $this->assertEmpty($attachments);
    }
}