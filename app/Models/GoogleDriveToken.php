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
        'token_type',
        'expires_at',
        'scopes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'scopes' => 'array',
    ];

    /**
     * Get the user that owns the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the token has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get the token as an array suitable for Google Client.
     */
    public function toGoogleToken(): array
    {
        return [
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'token_type' => $this->token_type,
            'expires_in' => max(0, $this->expires_at->diffInSeconds(now())),
            'scope' => implode(' ', $this->scopes),
            'created' => $this->created_at->timestamp,
        ];
    }
}
