<?php

namespace UploadDriveIn\LaravelAdmin2FA\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

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
            $user->two_factor_secret = $this->google2fa->generateSecretKey();
            $user->save();
        }

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret
        );

        return View::first(['admin-2fa::setup', 'laravel-admin-2fa::setup'], compact('qrCodeUrl'));
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

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

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

        if ($this->google2fa->verifyKey($user->two_factor_secret, $request->code)) {
            session(['two_factor_verified' => true]);
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['code' => 'The provided code is invalid.']);
    }
}
