<?php

namespace App\Mail;

use App\Enums\TokenRefreshErrorType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TokenRefreshFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $provider;
    public TokenRefreshErrorType $errorType;
    public int $attemptCount;
    public ?string $errorMessage;
    public string $reconnectUrl;
    public bool $requiresUserAction;
    public string $nextRetryInfo;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user, 
        string $provider, 
        TokenRefreshErrorType $errorType, 
        int $attemptCount,
        ?string $errorMessage = null
    ) {
        $this->user = $user;
        $this->provider = $provider;
        $this->errorType = $errorType;
        $this->attemptCount = $attemptCount;
        $this->errorMessage = $errorMessage;
        $this->reconnectUrl = $this->generateReconnectUrl($provider);
        $this->requiresUserAction = $errorType->requiresUserIntervention();
        $this->nextRetryInfo = $this->getNextRetryInfo();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $urgency = $this->requiresUserAction ? 
            __('messages.token_refresh_failed_action_required') : 
            __('messages.token_refresh_failed_auto_recovery');
        
        return new Envelope(
            subject: __('messages.token_refresh_failed_subject', [
                'provider' => $this->getProviderDisplayName(),
                'urgency' => $urgency
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.token-refresh-failed',
            with: [
                'user' => $this->user,
                'provider' => $this->provider,
                'providerName' => $this->getProviderDisplayName(),
                'errorType' => $this->errorType,
                'errorTypeName' => $this->getErrorTypeDisplayName(),
                'errorDescription' => $this->getErrorDescription(),
                'attemptCount' => $this->attemptCount,
                'errorMessage' => $this->errorMessage,
                'reconnectUrl' => $this->reconnectUrl,
                'requiresUserAction' => $this->requiresUserAction,
                'nextRetryInfo' => $this->nextRetryInfo,
                'supportEmail' => config('mail.support_email') ?: config('mail.from.address') ?: 'noreply@example.com',
                'isRecoverable' => $this->errorType->isRecoverable(),
            ],
        );
    }

    /**
     * Generate the reconnect URL for the provider
     */
    private function generateReconnectUrl(string $provider): string
    {
        $baseUrl = config('app.url');
        
        switch ($provider) {
            case 'google-drive':
                return $baseUrl . '/admin/cloud-storage/google-drive/connect';
            default:
                return $baseUrl . '/admin/cloud-storage';
        }
    }

    /**
     * Get display name for the provider
     */
    private function getProviderDisplayName(): string
    {
        return match ($this->provider) {
            'google-drive' => __('messages.provider_google_drive'),
            'microsoft-teams' => __('messages.provider_microsoft_teams'),
            'dropbox' => __('messages.provider_dropbox'),
            default => ucwords(str_replace('-', ' ', $this->provider)),
        };
    }

    /**
     * Get display name for the error type
     */
    private function getErrorTypeDisplayName(): string
    {
        return match ($this->errorType) {
            TokenRefreshErrorType::NETWORK_TIMEOUT => __('messages.error_type_network_timeout'),
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN => __('messages.error_type_invalid_refresh_token'),
            TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN => __('messages.error_type_expired_refresh_token'),
            TokenRefreshErrorType::API_QUOTA_EXCEEDED => __('messages.error_type_api_quota_exceeded'),
            TokenRefreshErrorType::SERVICE_UNAVAILABLE => __('messages.error_type_service_unavailable'),
            TokenRefreshErrorType::UNKNOWN_ERROR => __('messages.error_type_unknown_error'),
        };
    }

    /**
     * Get detailed error description for the user
     */
    private function getErrorDescription(): string
    {
        return match ($this->errorType) {
            TokenRefreshErrorType::NETWORK_TIMEOUT => __('messages.error_desc_network_timeout'),
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN => __('messages.error_desc_invalid_refresh_token'),
            TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN => __('messages.error_desc_expired_refresh_token'),
            TokenRefreshErrorType::API_QUOTA_EXCEEDED => __('messages.error_desc_api_quota_exceeded'),
            TokenRefreshErrorType::SERVICE_UNAVAILABLE => __('messages.error_desc_service_unavailable'),
            TokenRefreshErrorType::UNKNOWN_ERROR => __('messages.error_desc_unknown_error'),
        };
    }

    /**
     * Get information about next retry attempt
     */
    private function getNextRetryInfo(): string
    {
        if (!$this->errorType->isRecoverable()) {
            return __('messages.retry_no_automatic');
        }

        $maxAttempts = $this->errorType->getMaxRetryAttempts();
        
        if ($this->attemptCount >= $maxAttempts) {
            return __('messages.retry_max_attempts_reached');
        }

        $nextDelay = $this->errorType->getRetryDelay($this->attemptCount + 1);
        $remainingAttempts = $maxAttempts - $this->attemptCount;
        
        if ($nextDelay < 60) {
            return __('messages.retry_in_seconds', [
                'seconds' => $nextDelay,
                'remaining' => $remainingAttempts
            ]);
        } elseif ($nextDelay < 3600) {
            $minutes = round($nextDelay / 60);
            return __('messages.retry_in_minutes', [
                'minutes' => $minutes,
                'remaining' => $remainingAttempts
            ]);
        } else {
            $hours = round($nextDelay / 3600);
            return __('messages.retry_in_hours', [
                'hours' => $hours,
                'remaining' => $remainingAttempts
            ]);
        }
    }
}