<?php

namespace App\Events;

use App\Models\Share;
use App\Models\ShareFile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Someone downloaded from a share for the first time: one file, or all of
 * them as a zip ($file null).
 */
class ShareFirstDownloaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Share $share,
        public ?ShareFile $file
    ) {
        //
    }
}
