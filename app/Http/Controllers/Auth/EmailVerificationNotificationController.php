<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\VerificationMailFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $user = $request->user();
        
        // Generate verification URL using Laravel's built-in method
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );

        // Use VerificationMailFactory to select appropriate template based on user role
        $mailFactory = app(VerificationMailFactory::class);
        $verificationMail = $mailFactory->createForUser($user, $verificationUrl);
        
        // Log template selection for debugging
        Log::info('Email verification template selected for verification notification', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role->value,
            'mail_class' => get_class($verificationMail),
            'context' => 'email_verification_notification',
        ]);

        try {
            Mail::to($user->email)->send($verificationMail);
            
            // Log successful email sending using factory method
            $userRole = $mailFactory->determineContextForUser($user);
            $mailFactory->logEmailSent($userRole, $user->email);
            
            return back()->with('status', 'verification-link-sent');
        } catch (\Exception $e) {
            // Log email sending failure using factory method
            $userRole = $mailFactory->determineContextForUser($user);
            $mailFactory->logEmailSendError($userRole, $e->getMessage(), $user->email);
            
            Log::error('Failed to send email verification notification', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Failed to send verification email. Please try again.');
        }
    }
}
