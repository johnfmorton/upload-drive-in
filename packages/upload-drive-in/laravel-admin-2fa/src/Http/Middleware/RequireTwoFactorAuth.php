<?php

namespace UploadDriveIn\LaravelAdmin2FA\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireTwoFactorAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return $next($request);
        }

        // Skip 2FA check for these routes
        $excludedRoutes = [
            'admin.2fa.setup',
            'admin.2fa.enable',
            'admin.2fa.verify',
            'admin.2fa.verify.store',
            'logout',
            'login',
        ];

        if (in_array($request->route()->getName(), $excludedRoutes)) {
            return $next($request);
        }

        // If 2FA is enabled but not verified in this session
        if ($user->two_factor_enabled && !session('two_factor_verified')) {
            // Store the intended URL in the session
            session(['url.intended' => $request->url()]);
            return redirect()->route('admin.2fa.verify')
                ->with('warning', 'Please verify your two-factor authentication code to continue.');
        }

        // If 2FA is not enabled and it's enforced
        if (!$user->two_factor_enabled && config('admin-2fa.enforce_admin_2fa', true)) {
            return redirect()->route('admin.2fa.setup')
                ->with('warning', 'Two-factor authentication is required for admin users.');
        }

        return $next($request);
    }
}
