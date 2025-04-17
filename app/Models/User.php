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
        'password',
        'role',
        'receive_upload_notifications',
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
        'two_factor_enabled' => 'boolean',
        'two_factor_recovery_codes' => 'array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
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
        // Ensure this method is only called for client users
        if (!$this->isClient()) {
            // Or handle this case as appropriate, maybe throw an exception
            return '#'; // Or return an empty string or throw an exception
        }

        // Generate a signed URL that is valid for a specific duration (e.g., 7 days)
        return URL::temporarySignedRoute(
            'login.via.token',
            now()->addDays(7),
            ['user' => $this->id] // Pass the user model instance or just the ID
        );
    }
}
