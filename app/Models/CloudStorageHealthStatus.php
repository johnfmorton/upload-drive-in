<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudStorageHealthStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'status',
        'last_successful_operation_at',
        'consecutive_failures',
        'last_error_type',
        'last_error_message',
        'token_expires_at',
        'requires_reconnection',
        'provider_specific_data',
    ];

    protected $casts = [
        'provider_specific_data' => 'array',
        'last_successful_operation_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'requires_reconnection' => 'boolean',
        'consecutive_failures' => 'integer',
    ];

    /**
     * Get the user that owns this health status.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the connection is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    /**
     * Check if the connection is degraded.
     */
    public function isDegraded(): bool
    {
        return $this->status === 'degraded';
    }

    /**
     * Check if the connection is unhealthy.
     */
    public function isUnhealthy(): bool
    {
        return $this->status === 'unhealthy';
    }

    /**
     * Check if the connection is disconnected.
     */
    public function isDisconnected(): bool
    {
        return $this->status === 'disconnected';
    }

    /**
     * Get a user-friendly status message.
     */
    public function getStatusMessage(): string
    {
        return match ($this->status) {
            'healthy' => 'Connection is working properly',
            'degraded' => 'Connection has some issues but is functional',
            'unhealthy' => 'Connection has significant problems',
            'disconnected' => 'Connection is not established',
            default => 'Unknown status',
        };
    }

    /**
     * Get the time since last successful operation in human readable format.
     */
    public function getTimeSinceLastSuccess(): ?string
    {
        if (!$this->last_successful_operation_at) {
            return null;
        }

        return $this->last_successful_operation_at->diffForHumans();
    }

    /**
     * Check if token is expiring soon (within 24 hours).
     */
    public function isTokenExpiringSoon(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isBefore(now()->addDay());
    }

    /**
     * Check if token has expired.
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }
}