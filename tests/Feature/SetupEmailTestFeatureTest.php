<?php

namespace Tests\Feature;

use App\Services\MailTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SetupEmailTestFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable setup mode for tests
        config(['setup.enabled' => true]);

        // Clear rate limiter
        RateLimiter::clear('setup-email-test:127.0.0.1');
    }

    public function test_email_test_endpoint_requires_email(): void
    {
        $response = $this->postJson('/setup/email/test', []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
            ],
        ]);
    }

    public function test_email_test_endpoint_validates_email_format(): void
    {
        $response = $this->postJson('/setup/email/test', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
            ],
        ]);
    }

    public function test_email_test_endpoint_sends_email_successfully(): void
    {
        Mail::fake();

        // Use gmail.com which has valid MX records for DNS validation
        $response = $this->postJson('/setup/email/test', [
            'email' => 'test@gmail.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Test email sent successfully',
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'test_id',
                'duration_ms',
                'recipient',
            ],
        ]);

        Mail::assertSent(\App\Mail\SetupTestMail::class, function ($mail) {
            return $mail->hasTo('test@gmail.com');
        });
    }

    public function test_email_test_endpoint_rate_limits_requests(): void
    {
        Mail::fake();

        // Use gmail.com which has valid MX records for DNS validation
        // Make 3 successful requests
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/setup/email/test', [
                'email' => 'test@gmail.com',
            ]);
            $response->assertStatus(200);
        }

        // Fourth request should be rate limited
        $response = $this->postJson('/setup/email/test', [
            'email' => 'test@gmail.com',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
            ],
        ]);
    }

    public function test_mail_test_service_classifies_connection_timeout(): void
    {
        $service = new MailTestService();
        $exception = new \Exception('Connection timed out');

        $result = $service->classifyMailError($exception);

        $this->assertEquals('connection_timeout', $result['type']);
        $this->assertNotEmpty($result['troubleshooting']);
    }

    public function test_mail_test_service_classifies_authentication_failure(): void
    {
        $service = new MailTestService();
        $exception = new \Exception('Authentication failed');

        $result = $service->classifyMailError($exception);

        $this->assertEquals('authentication_failed', $result['type']);
        $this->assertNotEmpty($result['troubleshooting']);
    }

    public function test_mail_test_service_classifies_connection_refused(): void
    {
        $service = new MailTestService();
        $exception = new \Exception('Connection refused');

        $result = $service->classifyMailError($exception);

        $this->assertEquals('connection_refused', $result['type']);
        $this->assertNotEmpty($result['troubleshooting']);
    }

    public function test_mail_test_service_classifies_ssl_errors(): void
    {
        $service = new MailTestService();
        $exception = new \Exception('SSL certificate problem');

        $result = $service->classifyMailError($exception);

        $this->assertEquals('ssl_error', $result['type']);
        $this->assertNotEmpty($result['troubleshooting']);
    }

    public function test_mail_test_service_classifies_unknown_errors(): void
    {
        $service = new MailTestService();
        $exception = new \Exception('Some random error');

        $result = $service->classifyMailError($exception);

        $this->assertEquals('unknown', $result['type']);
        $this->assertNotEmpty($result['troubleshooting']);
    }
}
