<?php

namespace UploadDriveIn\LaravelAdmin2FA\Traits;

use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;

trait HasTwoFactorAuth
{
    public function initializeTwoFactorAuth(): void
    {
        $this->two_factor_secret = (new Google2FA())->generateSecretKey();
        $this->two_factor_recovery_codes = array_map(
            fn() => Str::random(10),
            range(1, config('admin-2fa.recovery_codes_count', 8))
        );
        $this->save();
    }

    public function enableTwoFactorAuth(): void
    {
        $this->two_factor_enabled = true;
        $this->save();
    }

    public function disableTwoFactorAuth(): void
    {
        $this->two_factor_enabled = false;
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->save();
    }

    public function verifyTwoFactorCode(string $code): bool
    {
        return (new Google2FA())->verifyKey($this->two_factor_secret, $code);
    }

    public function verifyRecoveryCode(string $code): bool
    {
        if (!$this->two_factor_recovery_codes) {
            return false;
        }

        $codes = $this->two_factor_recovery_codes;

        if (($key = array_search($code, $codes)) !== false) {
            unset($codes[$key]);
            $this->two_factor_recovery_codes = array_values($codes);
            $this->save();
            return true;
        }

        return false;
    }

    public function generateNewRecoveryCodes(): void
    {
        $this->two_factor_recovery_codes = array_map(
            fn() => Str::random(10),
            range(1, config('admin-2fa.recovery_codes_count', 8))
        );
        $this->save();
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'two_factor_enabled' => 'boolean',
            'two_factor_recovery_codes' => 'array',
            'two_factor_confirmed_at' => 'datetime',
        ]);
    }
}
