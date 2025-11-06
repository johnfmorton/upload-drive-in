<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\URL;
use UploadDriveIn\LaravelAdmin2FA\Traits\HasTwoFactorAuth;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Password;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasTwoFactorAuth;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'receive_upload_notifications',
        'owner_id',
        'google_drive_root_folder_id',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'last_login_at',
        'preferred_cloud_provider',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
        'receive_upload_notifications' => 'boolean',
        'google_drive_root_folder_id' => 'string',
        'two_factor_enabled' => 'boolean',
        'two_factor_recovery_codes' => 'array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['login_url', 'upload_url'];

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
    }

    /**
     * Determine if the user is an employee.
     *
     * @return bool
     */
    public function isEmployee(): bool
    {
        return $this->role === UserRole::EMPLOYEE;
    }

    public function canLoginWithPassword(): bool
    {
        return $this->role->canLoginWithPassword();
    }

    /**
     * Generate a temporary signed URL for client login.
     *
     * @return string
     */
    public function getLoginUrl(): string
    {
        // Generate a signed URL that is valid for a specific duration (e.g., 7 days)
        return URL::temporarySignedRoute(
            'login.via.token',
            now()->addDays(7),
            ['user' => $this->id] // Pass the user model instance or just the ID
        );
    }

    /** @return BelongsTo<User,User> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<User> */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'owner_id');
    }

    /**
     * Get the login_url attribute for the user.
     *
     * @return string
     */
    public function getLoginUrlAttribute(): string
    {
        return url()->temporarySignedRoute(
            'login.via.token',
            now()->addDays(7),
            ['user' => $this->id]
        );
    }

    /**
     * Generate a password reset URL for the user.
     * Note: This creates a new token each time it's called.
     *
     * @return string
     */
    public function generateResetUrl(): string
    {
        $token = Password::broker()->createToken($this);
        return route('password.reset', ['token' => $token, 'email' => $this->email]);
    }

    /**
     * Get the reset_url attribute for the user.
     * This method is kept for backward compatibility but should be used sparingly.
     *
     * @return string
     */
    public function getResetUrlAttribute(): string
    {
        return $this->generateResetUrl();
    }

    /**
     * Get the user's Google Drive token.
     */
    public function googleDriveToken(): HasOne
    {
        return $this->hasOne(GoogleDriveToken::class);
    }

    /**
     * Get the user's cloud storage health statuses.
     */
    public function cloudStorageHealthStatuses(): HasMany
    {
        return $this->hasMany(CloudStorageHealthStatus::class);
    }

    /**
     * Check if the user has connected their Google Drive account.
     */
    public function hasGoogleDriveConnected(): bool
    {
        $token = $this->googleDriveToken;
        
        if (!$token) {
            return false;
        }
        
        // User is connected if:
        // 1. Token hasn't expired, OR
        // 2. Token has expired but can be refreshed (has refresh token and doesn't require user intervention)
        return !$token->hasExpired() || $token->canBeRefreshed();
    }

    /**
     * Get the client users associated with this company user.
     */
    public function clientUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user_relationships', 'company_user_id', 'client_user_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the company users associated with this client user.
     */
    public function companyUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user_relationships', 'client_user_id', 'company_user_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the primary company user for this client user.
     */
    public function primaryCompanyUser()
    {
        return $this->companyUsers()
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * Get all client user relationships where this user is the company user.
     */
    public function companyUserRelationships(): HasMany
    {
        return $this->hasMany(ClientUserRelationship::class, 'company_user_id');
    }

    /**
     * Get all client user relationships where this user is the client user.
     */
    public function clientUserRelationships(): HasMany
    {
        return $this->hasMany(ClientUserRelationship::class, 'client_user_id');
    }

    /**
     * Get the personal upload URL for this user (admin or employee).
     *
     * @return string|null
     */
    public function getUploadUrl(): ?string
    {
        if (!$this->isAdmin() && !$this->isEmployee()) {
            return null;
        }

        // Extract name from email (everything before @)
        $name = explode('@', $this->email)[0];
        
        return route('upload.employee', ['name' => $name]);
    }

    /**
     * Get the upload_url attribute for the user.
     *
     * @return string|null
     */
    public function getUploadUrlAttribute(): ?string
    {
        return $this->getUploadUrl();
    }

    /**
     * Get clients where this user is the primary contact.
     *
     * @return BelongsToMany
     */
    public function primaryContactClients(): BelongsToMany
    {
        return $this->clientUsers()
            ->wherePivot('is_primary', true);
    }

    /**
     * Check if this user is the primary contact for a specific client.
     *
     * @param User $client
     * @return bool
     */
    public function isPrimaryContactFor(User $client): bool
    {
        return $this->clientUsers()
            ->wherePivot('is_primary', true)
            ->where('users.id', $client->id)
            ->exists();
    }
}
