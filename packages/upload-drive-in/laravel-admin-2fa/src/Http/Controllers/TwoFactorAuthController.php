<?php

namespace UploadDriveIn\LaravelAdmin2FA\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TwoFactorAuthController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function setup(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_secret) {
            // Initialize 2FA which will generate both secret and recovery codes
            $user->initializeTwoFactorAuth();
        } elseif (!$user->two_factor_recovery_codes) {
            // If we have a secret but no recovery codes, generate them
            $user->generateNewRecoveryCodes();
        }

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret
        );

        // Get recovery codes - they're automatically cast to array by Laravel due to $casts in the User model
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];

        return View::first(
            ['admin-2fa::setup', 'laravel-admin-2fa::setup'],
            [
                'qrCodeUrl' => $qrCodeUrl,
                'secret' => $user->two_factor_secret,
                'recoveryCodes' => $recoveryCodes,
            ]
        );
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();

        if ($this->google2fa->verifyKey($user->two_factor_secret, $request->code)) {
            $user->two_factor_enabled = true;
            $user->two_factor_confirmed_at = now();
            $user->save();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Two-factor authentication has been enabled. Please log in again to continue.');
        }

        return back()->withErrors(['code' => 'The provided code is invalid.']);
    }

    public function disable(Request $request)
    {
        $user = $request->user();
        $user->disableTwoFactorAuth();

        return redirect()->back()
            ->with('status', 'Two-factor authentication has been disabled.');
    }

    public function showVerifyForm()
    {
        return View::first(['admin-2fa::verify', 'laravel-admin-2fa::verify']);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();
        $code = $request->code;

        // First try regular 2FA code
        if ($this->google2fa->verifyKey($user->two_factor_secret, $code)) {
            session(['two_factor_verified' => true]);
            return redirect()->intended(route('admin.dashboard'));
        }

        // If regular code fails, try recovery code
        if ($user->verifyRecoveryCode($code)) {
            session(['two_factor_verified' => true]);
            return redirect()->intended(route('admin.dashboard'))
                ->with('warning', 'You used a recovery code to sign in. Recovery codes can only be used once. Please generate new recovery codes in your profile settings.');
        }

        return back()->withErrors(['code' => 'The provided code is invalid.']);
    }
}
