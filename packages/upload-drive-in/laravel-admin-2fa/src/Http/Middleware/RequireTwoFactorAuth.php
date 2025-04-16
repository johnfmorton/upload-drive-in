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
            'admin.settings.edit',    // Allow access to settings without 2FA
            'admin.settings.update',  // Allow access to settings without 2FA
        ];

        if (in_array($request->route()->getName(), $excludedRoutes)) {
            return $next($request);
        }

        // Only enforce 2FA verification if it's already enabled
        if ($user->two_factor_enabled) {
            // If 2FA is enabled but not verified in this session
            if (!session('two_factor_verified')) {
                // Store the intended URL in the session
                session(['url.intended' => $request->url()]);
                return redirect()->route('admin.2fa.verify');
            }
        }
        // If 2FA is not enabled, allow access
        else {
            // Only enforce 2FA setup if specifically configured
            if (config('admin-2fa.enforce_admin_2fa', true)) {
                // Show a warning message but don't force redirect
                session()->flash('warning', 'Two-factor authentication is recommended for enhanced security.');
            }
        }

        return $next($request);
    }
}
