<?php

namespace App\Notifications;

use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CloudStorageConnectionAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $provider,
        private string $alertType,
        private ?CloudStorageHealthStatus $healthStatus = null,
        private array $context = []
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $providerName = ucwords(str_replace('-', ' ', $this->provider));
        
        return match ($this->alertType) {
            'token_expiring' => $this->buildTokenExpiringMessage($notifiable, $providerName),
            'connection_unhealthy' => $this->buildConnectionUnhealthyMessage($notifiable, $providerName),
            'multiple_failures' => $this->buildMultipleFailuresMessage($notifiable, $providerName),
            default => $this->buildGenericAlertMessage($notifiable, $providerName)
        };
    }

    /**
     * Build token expiring message.
     */
    private function buildTokenExpiringMessage(object $notifiable, string $providerName): MailMessage
    {
        $expiresAt = $this->healthStatus->token_expires_at;
        $hoursUntilExpiry = $expiresAt ? now()->diffInHours($expiresAt) : 0;
        
        return (new MailMessage)
            ->subject("{$providerName} Token Will Refresh Soon")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$providerName} access token will refresh automatically in approximately {$hoursUntilExpiry} hours.")
            ->line("Refresh time: {$expiresAt}")
            ->line('The system will automatically refresh your token to maintain uninterrupted service.')
            ->action('View Connection Status', $this->getReconnectionUrl($notifiable))
            ->line('No action is required on your part - this is just an informational notice.');
    }

    /**
     * Build connection unhealthy message.
     */
    private function buildConnectionUnhealthyMessage(object $notifiable, string $providerName): MailMessage
    {
        $errorMessage = $this->healthStatus->last_error_message ?? 'Unknown error';
        $consecutiveFailures = $this->healthStatus->consecutive_failures;
        
        return (new MailMessage)
            ->subject("{$providerName} Connection Issue Detected")
            ->greeting("Hello {$notifiable->name},")
            ->line("We've detected an issue with your {$providerName} connection.")
            ->line("Error: {$errorMessage}")
            ->line("Consecutive failures: {$consecutiveFailures}")
            ->line('This may affect file uploads to your cloud storage.')
            ->action('Check Connection', $this->getDashboardUrl($notifiable))
            ->line('Please review your connection settings and reconnect if necessary.');
    }

    /**
     * Build multiple failures message.
     */
    private function buildMultipleFailuresMessage(object $notifiable, string $providerName): MailMessage
    {
        $consecutiveFailures = $this->healthStatus->consecutive_failures;
        $lastError = $this->healthStatus->last_error_message ?? 'Unknown error';
        
        return (new MailMessage)
            ->subject("{$providerName} Multiple Upload Failures")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$providerName} connection has experienced {$consecutiveFailures} consecutive failures.")
            ->line("Last error: {$lastError}")
            ->line('This indicates a persistent issue that needs attention.')
            ->action('Troubleshoot Now', $this->getDashboardUrl($notifiable))
            ->line('Please check your connection and resolve any issues to restore upload functionality.');
    }

    /**
     * Build generic alert message.
     */
    private function buildGenericAlertMessage(object $notifiable, string $providerName): MailMessage
    {
        return (new MailMessage)
            ->subject("{$providerName} Connection Alert")
            ->greeting("Hello {$notifiable->name},")
            ->line("There's an issue with your {$providerName} connection that requires attention.")
            ->line("Status: {$this->healthStatus->status}")
            ->when($this->healthStatus->last_error_message, function ($message) {
                return $message->line("Error: {$this->healthStatus->last_error_message}");
            })
            ->action('Check Dashboard', $this->getDashboardUrl($notifiable))
            ->line('Please review your connection settings.');
    }

    /**
     * Get reconnection URL based on user type.
     */
    private function getReconnectionUrl(object $notifiable): string
    {
        if ($notifiable instanceof User) {
            return match ($notifiable->role->value) {
                'admin' => route('admin.cloud-storage.index'),
                'employee' => route('employee.cloud-storage.index', ['username' => $notifiable->username]),
                default => route('admin.cloud-storage.index')
            };
        }
        
        return route('admin.cloud-storage.index');
    }

    /**
     * Get dashboard URL based on user type.
     */
    private function getDashboardUrl(object $notifiable): string
    {
        if ($notifiable instanceof User) {
            return match ($notifiable->role->value) {
                'admin' => route('admin.dashboard'),
                'employee' => route('employee.dashboard'),
                default => route('admin.dashboard')
            };
        }
        
        return route('admin.dashboard');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'provider' => $this->provider,
            'alert_type' => $this->alertType,
            'status' => $this->healthStatus->status,
            'consecutive_failures' => $this->healthStatus->consecutive_failures,
            'last_error' => $this->healthStatus->last_error_message,
            'token_expires_at' => $this->healthStatus->token_expires_at,
            'context' => $this->context,
        ];
    }
}