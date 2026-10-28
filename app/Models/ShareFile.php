<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One file in an outgoing share. It is staged on the local disk (local_path)
 * until UploadShareFileToCloud copies it to the share's provider
 * (provider_file_id) and removes the local copy.
 */
class ShareFile extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'share_id',
        'original_filename',
        'mime_type',
        'file_size',
        'local_path',
        'provider_file_id',
        'status',
        'last_error',
        'download_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }

    public function isUploaded(): bool
    {
        return $this->status === self::STATUS_UPLOADED;
    }
}
