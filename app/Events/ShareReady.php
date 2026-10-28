<?php

namespace App\Events;

use App\Models\Share;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Every file in a share has reached cloud storage, so its link now works.
 */
class ShareReady
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Share $share
    ) {
        //
    }
}
