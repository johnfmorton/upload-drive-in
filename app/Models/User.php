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
    protected $appends = ['login_url', 'reset_url'];

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
     * Get the reset_url attribute for the user.
     *
     * @return string
     */
    public function getResetUrlAttribute(): string
    {
        $token = Password::broker()->createToken($this);
        return route('password.reset', ['token' => $token, 'email' => $this->email]);
    }

    /**
     * Get the user's Google Drive token.
     */
    public function googleDriveToken(): HasOne
    {
        return $this->hasOne(GoogleDriveToken::class);
    }

    /**
     * Check if the user has connected their Google Drive account.
     */
    public function hasGoogleDriveConnected(): bool
    {
        return $this->googleDriveToken()->exists();
    }
}
