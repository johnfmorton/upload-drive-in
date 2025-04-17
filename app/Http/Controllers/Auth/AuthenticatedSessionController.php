<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use App\Enums\UserRole;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Check if the user is an admin and has 2FA enabled
        $user = Auth::user();
        if ($user && $user->isAdmin()) {
            if ($user->two_factor_enabled) {
            // Store the intended URL before redirecting to 2FA verification
                session(['url.intended' => route('admin.dashboard')]);

            return redirect()->route('admin.2fa.verify')
                ->with('warning', 'Please verify your two-factor authentication code.');
        }

            // If admin but no 2FA, redirect to admin dashboard
            return redirect()->route('admin.dashboard');
        }

        // For non-admin users, redirect to the default home
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Clear 2FA verification status
        Session::forget('two_factor_verified');
        Session::forget('url.intended');

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Log the user in via a signed token URL.
     */
    public function loginViaToken(Request $request, User $user): RedirectResponse
    {
        // The 'signed' middleware in the route definition already verifies the signature.
        // We just need to make sure the user exists and is a client.

        if (!$user || $user->role !== UserRole::CLIENT) {
            // Optional: Log this attempt
            Log::warning("Attempt to use login token for invalid user or non-client user: {$user->id}");
            return redirect()->route('home')->with('error', 'Invalid login link.');
        }

        // Log the user in
        Auth::login($user);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // Redirect to the intended page for clients (e.g., file upload)
        return redirect()->route('upload-files')
            ->with('success', 'Logged in successfully.');
    }
}
