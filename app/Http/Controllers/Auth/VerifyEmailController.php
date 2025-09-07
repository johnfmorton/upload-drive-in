<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\VerificationMailFactory;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $mailFactory = app(VerificationMailFactory::class);
        
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            
            // Log successful verification
            $userRole = $mailFactory->determineContextForUser($user);
            $mailFactory->logVerificationSuccess($userRole, $user->email);
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
