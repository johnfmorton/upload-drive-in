<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CloudStorageSetting extends Model
{
    protected $fillable = [
        'provider',
        'key',
        'value',
        'encrypted',
    ];

    protected $casts = [
        'encrypted' => 'boolean',
    ];

    /**
     * Get the decrypted value if encrypted, otherwise return raw value.
     */
    public function getDecryptedValueAttribute(): ?string
    {
        if ($this->encrypted && $this->value) {
            try {
                return Crypt::decryptString($this->value);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return $this->value;
    }

    /**
     * Set the value, encrypting it if needed.
     */
    public function setEncryptedValue(string $value, bool $encrypt = false): void
    {
        $this->encrypted = $encrypt;
        $this->value = $encrypt ? Crypt::encryptString($value) : $value;
    }

    /**
     * Get a setting value for a provider and key.
     */
    public static function getValue(string $provider, string $key): ?string
    {
        $setting = static::where('provider', $provider)
            ->where('key', $key)
            ->first();

        return $setting?->decrypted_value;
    }

    /**
     * Set a setting value for a provider and key.
     */
    public static function setValue(string $provider, string $key, ?string $value, bool $encrypt = false): void
    {
        $setting = static::updateOrCreate(
            ['provider' => $provider, 'key' => $key],
            []
        );

        if ($value === null) {
            $setting->delete();
            return;
        }

        $setting->setEncryptedValue($value, $encrypt);
        $setting->save();
    }

    /**
     * Get the environment key for a provider and setting key.
     */
    private static function getEnvironmentKey(string $provider, string $key): string
    {
        $providerKey = str_replace('-', '_', strtoupper($provider));
        $settingKey = strtoupper($key);
        
        return "{$providerKey}_{$settingKey}";
    }

    /**
     * Check if a setting is defined in environment variables.
     */
    public static function isDefinedInEnvironment(string $provider, string $key): bool
    {
        $envKey = static::getEnvironmentKey($provider, $key);
        $envValue = env($envKey);
        return $envValue !== null && $envValue !== '';
    }

    /**
     * Get the effective value (environment first, then database).
     */
    public static function getEffectiveValue(string $provider, string $key): ?string
    {
        $envKey = static::getEnvironmentKey($provider, $key);
        $envValue = env($envKey);
        
        if ($envValue !== null && $envValue !== '') {
            return $envValue;
        }

        return static::getValue($provider, $key);
    }

    /**
     * Get all effective configuration for a provider.
     */
    public static function getProviderConfig(string $provider): array
    {
        $config = [];
        
        // Define the keys we need for each provider
        $providerKeys = [
            'google-drive' => ['client_id', 'client_secret', 'root_folder_id'],
            'microsoft-teams' => ['client_id', 'client_secret', 'root_folder_id'],
            'dropbox' => ['client_id', 'client_secret', 'root_folder'],
        ];

        if (!isset($providerKeys[$provider])) {
            return $config;
        }

        foreach ($providerKeys[$provider] as $key) {
            $config[$key] = static::getEffectiveValue($provider, $key);
        }

        return $config;
    }
}
