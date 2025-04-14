<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class BatchUploadComplete
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<int> $fileUploadIds Array of FileUpload model IDs in the batch.
     * @param int $userId The ID of the user who uploaded the files.
     */
    public function __construct(
        public array $fileUploadIds,
        public int $userId
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    // public function broadcastOn(): array
    // {
    //     // Define broadcasting channels if needed in the future
    //     return [];
    // }
}
