<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An outgoing file share: files a sender uploaded for someone to download by link.
 *
 * Lifecycle: draft (accepting files) → uploading (sender finished, files still
 * going to the cloud) → ready (link works) → expired / revoked (link dead,
 * files purged, purged_at set). A share that never finishes uploading ends as
 * failed and is purged the same way.
 */
class Share extends Model
{
    use HasFactory, HasUlids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UPLOADING = 'uploading';

    public const STATUS_READY = 'ready';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'token_hash',
        'token_encrypted',
        'title',
        'message',
        'password',
        'recipient_emails',
        'provider',
        'cloud_user_id',
        'cloud_folder_id',
        'status',
        'expires_at',
        'ready_at',
        'revoked_at',
        'purged_at',
        'first_downloaded_at',
        'download_count',
        'last_accessed_at',
    ];

    protected $hidden = [
        'token_hash',
        'token_encrypted',
        'password',
    ];

    protected $casts = [
        'token_encrypted' => 'encrypted',
        'password' => 'hashed',
        'recipient_emails' => 'array',
        'expires_at' => 'datetime',
        'ready_at' => 'datetime',
        'revoked_at' => 'datetime',
        'purged_at' => 'datetime',
        'first_downloaded_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Only the public ulid column is generated; the primary key stays an integer.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cloudUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloud_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ShareFile::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(ShareAccessLog::class);
    }

    /**
     * Shares whose link currently works.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_READY)
            ->where('expires_at', '>', now());
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_READY && $this->expires_at->isFuture();
    }

    public function hasPassword(): bool
    {
        return ! empty($this->password);
    }

    /**
     * Whether the sender can still add files.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Whether the share has ended (expired, revoked or failed) and its files are or will be purged.
     */
    public function isEnded(): bool
    {
        return in_array($this->status, [self::STATUS_EXPIRED, self::STATUS_REVOKED, self::STATUS_FAILED], true);
    }

    /**
     * The public download link for this share.
     */
    public function publicUrl(): string
    {
        return route('shares.public.show', ['token' => $this->token_encrypted]);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
