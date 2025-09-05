<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a Google Drive OAuth token for a specific user.
 *
 * @property int $user_id
 * @property string $access_token
 * @property string|null $refresh_token
 * @property string $token_type
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property array|null $scopes
 * @property \Illuminate\Support\Carbon|null $last_refresh_attempt_at
 * @property int $refresh_failure_count
 * @property \Illuminate\Support\Carbon|null $last_successful_refresh_at
 * @property \Illuminate\Support\Carbon|null $proactive_refresh_scheduled_at
 * @property int $health_check_failures
 * @property bool $requires_user_intervention
 * @property \Illuminate\Support\Carbon|null $last_notification_sent_at
 * @property int $notification_failure_count
 */
class GoogleDriveToken extends Model
{
    use HasFactory;
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
        'last_refresh_attempt_at',
        'refresh_failure_count',
        'last_successful_refresh_at',
        'proactive_refresh_scheduled_at',
        'health_check_failures',
        'requires_user_intervention',
        'last_notification_sent_at',
        'notification_failure_count',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'scopes' => 'array',
        'last_refresh_attempt_at' => 'datetime',
        'last_successful_refresh_at' => 'datetime',
        'proactive_refresh_scheduled_at' => 'datetime',
        'last_notification_sent_at' => 'datetime',
        'requires_user_intervention' => 'boolean',
        'refresh_failure_count' => 'integer',
        'health_check_failures' => 'integer',
        'notification_failure_count' => 'integer',
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

    /**
     * Check if the token is expiring soon (within specified minutes).
     */
    public function isExpiringSoon(int $minutes = 15): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->subMinutes($minutes)->isPast();
    }

    /**
     * Check if the token can be refreshed.
     */
    public function canBeRefreshed(): bool
    {
        // Token can be refreshed if:
        // 1. We have a refresh token
        // 2. User intervention is not required
        // 3. We haven't exceeded maximum failure attempts (5)
        return !empty($this->refresh_token) 
            && !$this->requires_user_intervention 
            && $this->refresh_failure_count < 5;
    }

    /**
     * Check if proactive refresh should be scheduled.
     */
    public function shouldScheduleProactiveRefresh(): bool
    {
        // Schedule proactive refresh if:
        // 1. Token is expiring soon (within 30 minutes)
        // 2. No proactive refresh is already scheduled
        // 3. Token can be refreshed
        return $this->isExpiringSoon(30) 
            && !$this->proactive_refresh_scheduled_at 
            && $this->canBeRefreshed();
    }

    /**
     * Mark a refresh failure and update tracking fields.
     */
    public function markRefreshFailure(\Exception $error): void
    {
        $this->increment('refresh_failure_count');
        $this->update([
            'last_refresh_attempt_at' => now(),
            'requires_user_intervention' => $this->refresh_failure_count >= 5,
        ]);

        // Log the failure for debugging
        \Log::warning('Google Drive token refresh failed', [
            'user_id' => $this->user_id,
            'failure_count' => $this->refresh_failure_count,
            'error' => $error->getMessage(),
            'requires_intervention' => $this->requires_user_intervention,
        ]);
    }

    /**
     * Mark a successful refresh and reset failure tracking.
     */
    public function markRefreshSuccess(): void
    {
        $this->update([
            'last_refresh_attempt_at' => now(),
            'last_successful_refresh_at' => now(),
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
            'proactive_refresh_scheduled_at' => null,
        ]);

        \Log::info('Google Drive token refresh successful', [
            'user_id' => $this->user_id,
            'expires_at' => $this->expires_at,
        ]);
    }
}
