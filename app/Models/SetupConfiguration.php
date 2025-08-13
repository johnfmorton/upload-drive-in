<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetupConfiguration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a configuration value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $config = static::where('key', $key)->first();
        
        if (!$config) {
            return $default;
        }
        
        // Try to decode JSON, return raw value if not JSON
        $decoded = json_decode($config->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $config->value;
    }

    /**
     * Set a configuration value by key.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public static function setValue(string $key, mixed $value): static
    {
        // Encode arrays and objects as JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Check if a configuration key exists.
     *
     * @param string $key
     * @return bool
     */
    public static function hasKey(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Remove a configuration by key.
     *
     * @param string $key
     * @return bool
     */
    public static function removeKey(string $key): bool
    {
        return static::where('key', $key)->delete() > 0;
    }

    /**
     * Check if setup is complete.
     *
     * @return bool
     */
    public static function isSetupComplete(): bool
    {
        return static::getValue('setup_complete', false) === true;
    }

    /**
     * Mark setup as complete.
     *
     * @return static
     */
    public static function markSetupComplete(): static
    {
        return static::setValue('setup_complete', true);
    }

    /**
     * Check if initial admin has been created.
     *
     * @return bool
     */
    public static function hasInitialAdmin(): bool
    {
        return static::getValue('initial_admin_created', false) === true;
    }

    /**
     * Mark initial admin as created.
     *
     * @return static
     */
    public static function markInitialAdminCreated(): static
    {
        return static::setValue('initial_admin_created', true);
    }

    /**
     * Get the current setup step.
     *
     * @return string
     */
    public static function getCurrentSetupStep(): string
    {
        return static::getValue('current_setup_step', 'welcome');
    }

    /**
     * Set the current setup step.
     *
     * @param string $step
     * @return static
     */
    public static function setCurrentSetupStep(string $step): static
    {
        return static::setValue('current_setup_step', $step);
    }
}
