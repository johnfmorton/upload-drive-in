<?php

namespace Tests\Unit\Mail;

use App\Mail\ClientVerificationMail;
use Tests\TestCase;

class ClientVerificationMailTest extends TestCase
{
    /** @test */
    public function it_uses_correct_template_path()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new ClientVerificationMail($verificationUrl, 'client');

        $content = $mail->content();

        $this->assertEquals('emails.verification.client-verification', $content->markdown);
    }

    /** @test */
    public function it_uses_correct_subject_line()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new ClientVerificationMail($verificationUrl, 'client');

        $envelope = $mail->envelope();

        $this->assertEquals(__('messages.client_verify_email_subject'), $envelope->subject);
    }

    /** @test */
    public function it_can_be_instantiated_and_rendered()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new ClientVerificationMail($verificationUrl, 'client');

        $this->assertEquals($verificationUrl, $mail->verificationUrl);
        $this->assertEquals('client', $mail->userRole);
        $this->assertNotEmpty($mail->companyName);
    }

    /** @test */
    public function it_has_no_attachments()
    {
        $verificationUrl = 'https://example.com/verify';
        $mail = new ClientVerificationMail($verificationUrl, 'client');

        $attachments = $mail->attachments();

        $this->assertEmpty($attachments);
    }
}