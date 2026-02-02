<?php

namespace App\Services;

use App\Mail\SetupTestMail;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MailTestService
{
    /**
     * Send a test email to verify SMTP configuration.
     *
     * @param  string  $toAddress  The email address to send the test to
     * @return array Result with success status, message, and details
     */
    public function sendTestEmail(string $toAddress): array
    {
        $testId = Str::random(8);
        $startTime = microtime(true);

        try {
            // Send synchronously (no queue) for immediate feedback
            Mail::to($toAddress)->send(new SetupTestMail(
                testId: $testId,
                appName: config('app.name', 'Upload Drive-In'),
                sentAt: now()->toDateTimeString()
            ));

            $duration = round((microtime(true) - $startTime) * 1000);

            Log::info('Test email sent successfully', [
                'test_id' => $testId,
                'to' => $toAddress,
                'duration_ms' => $duration,
            ]);

            return [
                'success' => true,
                'message' => 'Test email sent successfully',
                'test_id' => $testId,
                'duration_ms' => $duration,
                'details' => [
                    'recipient' => $toAddress,
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                ],
            ];
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000);

            Log::error('Test email failed', [
                'test_id' => $testId,
                'to' => $toAddress,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'duration_ms' => $duration,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'trace' => collect(explode("\n", $e->getTraceAsString()))->take(5)->implode("\n"),
            ]);

            $classified = $this->classifyMailError($e);

            return [
                'success' => false,
                'message' => $classified['message'],
                'error_type' => $classified['type'],
                'troubleshooting' => $classified['troubleshooting'],
                'test_id' => $testId,
                'duration_ms' => $duration,
                'details' => [
                    'error' => $this->sanitizeErrorMessage($e->getMessage()),
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                ],
            ];
        }
    }

    /**
     * Classify mail errors and provide troubleshooting guidance.
     *
     * @param  Exception  $e  The exception to classify
     * @return array Error classification with message, type, and troubleshooting tips
     */
    public function classifyMailError(Exception $e): array
    {
        $message = $e->getMessage();
        $lowerMessage = strtolower($message);

        // Connection timeout
        if (str_contains($lowerMessage, 'connection timed out') ||
            str_contains($lowerMessage, 'connection timeout') ||
            str_contains($lowerMessage, 'operation timed out')) {
            return [
                'type' => 'connection_timeout',
                'message' => 'Connection timed out',
                'troubleshooting' => [
                    'Check if MAIL_PORT is correct (common ports: 465 for SSL, 587 for TLS)',
                    'Verify MAIL_ENCRYPTION matches your port (ssl for 465, tls for 587)',
                    'Ensure the SMTP server hostname is correct',
                    'Check if your firewall allows outbound connections on the SMTP port',
                ],
            ];
        }

        // Authentication failed
        if (str_contains($lowerMessage, 'authentication failed') ||
            str_contains($lowerMessage, 'invalid credentials') ||
            str_contains($lowerMessage, 'username and password not accepted') ||
            str_contains($lowerMessage, 'auth') && str_contains($lowerMessage, 'failed')) {
            return [
                'type' => 'authentication_failed',
                'message' => 'Authentication failed',
                'troubleshooting' => [
                    'Verify MAIL_USERNAME is correct',
                    'Verify MAIL_PASSWORD is correct',
                    'For Gmail, use an App Password instead of your account password',
                    'Some providers require enabling "Less secure app access"',
                    'Check if your email account requires 2FA setup',
                ],
            ];
        }

        // Connection refused
        if (str_contains($lowerMessage, 'connection refused') ||
            str_contains($lowerMessage, 'connection could not be established') ||
            str_contains($lowerMessage, 'no route to host')) {
            return [
                'type' => 'connection_refused',
                'message' => 'Cannot connect to mail server',
                'troubleshooting' => [
                    'Verify MAIL_HOST is correct',
                    'Check if the mail server is running and accessible',
                    'Ensure your firewall allows outbound SMTP connections',
                    'Try using the IP address instead of hostname',
                    'Contact your hosting provider if outbound SMTP is blocked',
                ],
            ];
        }

        // SSL/TLS errors
        if (str_contains($lowerMessage, 'ssl') ||
            str_contains($lowerMessage, 'tls') ||
            str_contains($lowerMessage, 'certificate') ||
            str_contains($lowerMessage, 'crypto')) {
            return [
                'type' => 'ssl_error',
                'message' => 'SSL/TLS negotiation failed',
                'troubleshooting' => [
                    'For port 465, set MAIL_ENCRYPTION=ssl',
                    'For port 587, set MAIL_ENCRYPTION=tls',
                    'Try setting MAIL_ENCRYPTION=null if using port 25',
                    'Check if MAIL_SCHEME needs to be set (usually null)',
                    'Verify the mail server supports the encryption type',
                ],
            ];
        }

        // Expected response code errors (SMTP protocol errors)
        if (str_contains($lowerMessage, 'expected response code') ||
            str_contains($lowerMessage, 'smtp error')) {
            return [
                'type' => 'smtp_error',
                'message' => 'SMTP server rejected the request',
                'troubleshooting' => [
                    'Check if the SMTP server address is correct',
                    'Verify the mail server is configured to accept connections',
                    'Check MAIL_FROM_ADDRESS is set and valid',
                    'Some servers require matching FROM address to authenticated user',
                    'Review SMTP server logs for more details',
                ],
            ];
        }

        // DNS/hostname resolution errors
        if (str_contains($lowerMessage, 'getaddrinfo') ||
            str_contains($lowerMessage, 'could not resolve') ||
            str_contains($lowerMessage, 'name or service not known')) {
            return [
                'type' => 'dns_error',
                'message' => 'Cannot resolve mail server hostname',
                'troubleshooting' => [
                    'Verify MAIL_HOST is spelled correctly',
                    'Check your DNS configuration',
                    'Try using the IP address instead of hostname',
                    'Ensure your server has internet connectivity',
                ],
            ];
        }

        // Stream/socket errors
        if (str_contains($lowerMessage, 'stream_socket') ||
            str_contains($lowerMessage, 'socket')) {
            return [
                'type' => 'socket_error',
                'message' => 'Socket connection error',
                'troubleshooting' => [
                    'Check if the SMTP port is correct and open',
                    'Verify firewall settings allow the connection',
                    'Try a different SMTP port (25, 465, 587)',
                    'Check if PHP socket extension is enabled',
                ],
            ];
        }

        // Generic/unknown error
        return [
            'type' => 'unknown',
            'message' => 'Failed to send email',
            'troubleshooting' => [
                'Check all MAIL_* settings in your .env file',
                'Verify SMTP credentials are correct',
                'Check application logs for more details',
                'Try a different mail provider for testing',
                'Contact your hosting provider for SMTP restrictions',
            ],
        ];
    }

    /**
     * Sanitize error messages to avoid leaking sensitive information.
     *
     * @param  string  $message  The error message to sanitize
     * @return string Sanitized error message
     */
    private function sanitizeErrorMessage(string $message): string
    {
        // Remove potential password/credential leaks from error messages
        $patterns = [
            '/password[=:]\s*[^\s,;]+/i' => 'password=[REDACTED]',
            '/secret[=:]\s*[^\s,;]+/i' => 'secret=[REDACTED]',
            '/key[=:]\s*[^\s,;]+/i' => 'key=[REDACTED]',
            '/token[=:]\s*[^\s,;]+/i' => 'token=[REDACTED]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $message = preg_replace($pattern, $replacement, $message);
        }

        // Truncate very long messages
        if (strlen($message) > 500) {
            $message = substr($message, 0, 500).'...';
        }

        return $message;
    }
}
