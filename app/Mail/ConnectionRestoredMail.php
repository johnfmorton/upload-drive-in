<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConnectionRestoredMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $provider;
    public string $dashboardUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $provider)
    {
        $this->user = $user;
        $this->provider = $provider;
        $this->dashboardUrl = $this->generateDashboardUrl();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.connection_restored_subject', ['provider' => $this->getProviderDisplayName()]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.connection-restored',
            with: [
                'user' => $this->user,
                'provider' => $this->provider,
                'providerName' => $this->getProviderDisplayName(),
                'dashboardUrl' => $this->dashboardUrl,
                'supportEmail' => config('mail.support_email') ?: config('mail.from.address') ?: 'noreply@example.com',
            ],
        );
    }

    /**
     * Generate the dashboard URL
     */
    private function generateDashboardUrl(): string
    {
        $baseUrl = config('app.url');
        
        // Determine the appropriate dashboard based on user role
        if ($this->user->role === \App\Enums\UserRole::ADMIN) {
            return $baseUrl . '/admin/dashboard';
        } elseif ($this->user->role === \App\Enums\UserRole::EMPLOYEE) {
            return $baseUrl . '/employee/dashboard';
        } else {
            return $baseUrl . '/client/dashboard';
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