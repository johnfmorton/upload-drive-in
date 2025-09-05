<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TokenExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $provider;
    public string $reconnectUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $provider)
    {
        $this->user = $user;
        $this->provider = $provider;
        $this->reconnectUrl = $this->generateReconnectUrl($provider);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.token_expired_subject', ['provider' => $this->getProviderDisplayName()]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.token-expired',
            with: [
                'user' => $this->user,
                'provider' => $this->provider,
                'providerName' => $this->getProviderDisplayName(),
                'reconnectUrl' => $this->reconnectUrl,
                'supportEmail' => config('mail.support_email') ?: config('mail.from.address') ?: 'noreply@example.com',
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
}