<?php

namespace App\Events;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @deprecated This event is kept for reference and development purposes.
 * Individual file upload notifications have been replaced by batch notifications.
 * See BatchUploadComplete event for the current implementation.
 */
class FileUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param int $fileUploadId The ID of the uploaded file record.
     * @param int $userId The ID of the user who uploaded the file.
     */
    public function __construct(
        public int $fileUploadId,
        public int $userId
    ) {
        // Constructor body is now empty
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
