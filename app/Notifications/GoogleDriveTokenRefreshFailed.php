<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GoogleDriveTokenRefreshFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $failedUsers,
        private int $totalFailed
    ) {}

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
        $message = (new MailMessage)
            ->subject('Google Drive Token Refresh Failed')
            ->line("Failed to refresh Google Drive tokens for {$this->totalFailed} user(s).")
            ->line('This may cause file upload failures until tokens are manually refreshed.');

        if (count($this->failedUsers) <= 5) {
            $message->line('Affected users:');
            foreach ($this->failedUsers as $user) {
                $message->line("- {$user['name']} (ID: {$user['id']})");
            }
        } else {
            $message->line("Too many failed users to list. Check the application logs for details.");
        }

        $message->line('Please check the application logs and consider re-authenticating affected users.')
                ->action('View Admin Dashboard', url('/admin'));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'failed_users' => $this->failedUsers,
            'total_failed' => $this->totalFailed,
        ];
    }
}