<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a Google Drive OAuth token for a specific user.
 *
 * @property int $user_id
 * @property string $access_token
 * @property string|null $refresh_token
 * @property \Illuminate\Support\Carbon|null $expires_at
 */
class GoogleDriveToken extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'google_drive_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns this token.
     *
     * @return BelongsTo<User,GoogleDriveToken>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
