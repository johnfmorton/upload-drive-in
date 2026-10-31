<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of someone opening a share link, downloading from it (one file,
 * or all of them as a zip), or entering a wrong password.
 */
class ShareAccessLog extends Model
{
    public const EVENT_VIEW = 'view';

    public const EVENT_DOWNLOAD = 'download';

    public const EVENT_ZIP = 'zip';

    public const EVENT_BAD_PASSWORD = 'bad_password';

    public const UPDATED_AT = null;

    protected $fillable = [
        'share_id',
        'share_file_id',
        'event',
        'ip',
        'user_agent',
    ];

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }

    public function shareFile(): BelongsTo
    {
        return $this->belongsTo(ShareFile::class);
    }
}
