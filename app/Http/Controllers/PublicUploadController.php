<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationMail;
use App\Models\EmailValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\LoginVerificationMail;
use App\Models\DomainAccessRule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class PublicUploadController extends Controller
{
    /**
     * Show the public landing page or redirect to appropriate dashboard if logged in.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        if (Auth::check()) {
            $user = Auth::user();
            Log::info('User role check', ['role' => $user->role]);

            if ($user->isAdmin()) {
                return redirect('/admin/dashboard');
            } elseif ($user->isClient()) {
                return redirect('/client/dashboard');
            } elseif ($user->isEmployee()) {
                return redirect('/employee/' . $user->username . '/dashboard');
            }
        }

        return view('email-validation-form');
    }

    /**
     * Validate the email address.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function validateEmail(Request $request)
    {
        Log::info('Email validation attempt', [
            'email' => $request->email
        ]);

        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email', 'max:255'],
            ]);

            // Check if public registration is allowed
            $domainRules = DomainAccessRule::first();
            Log::info('Domain rules check', [
                'rules_exist' => (bool)$domainRules,
                'public_registration' => $domainRules ? $domainRules->allow_public_registration : true
            ]);

            if ($domainRules && !$domainRules->allow_public_registration) {
                Log::warning('Public registration attempt when disabled', [
                    'email' => $validated['email']
                ]);
                throw ValidationException::withMessages([
                    'email' => [__('messages.public_registration_disabled')],
                ]);
            }

            // Check if the email domain is allowed
            if ($domainRules && !$domainRules->isEmailAllowed($validated['email'])) {
                Log::warning('Email domain not allowed', [
                    'email' => $validated['email'],
                    'mode' => $domainRules->mode
                ]);
                throw ValidationException::withMessages([
                    'email' => [__('messages.email_domain_not_allowed')],
                ]);
            }

            $email = $validated['email'];
            $verificationCode = Str::random(32);

            Log::info('Creating email validation record', [
                'email' => $email
            ]);

            // Create or update email validation record
            $validation = EmailValidation::updateOrCreate(
                ['email' => $email],
                [
                    'verification_code' => $verificationCode,
                    'expires_at' => now()->addHours(24)
                ]
            );

            // Generate verification URL
            $verificationUrl = route('verify-email', [
                'code' => $verificationCode,
                'email' => $email
            ]);

            // Use the correct mail class for login verification
            Mail::to($email)->send(new LoginVerificationMail($verificationUrl));

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully.'
            ]);

        } catch (ValidationException $e) {
            Log::warning('Validation failed', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->errors()['email'][0] ?? 'Invalid email address.'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in email validation', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * Verify the email address.
     *
     * @param  string  $code
     * @param  string  $email
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyEmail($code, $email)
    {
        $validation = EmailValidation::where('email', $email)
            ->where('verification_code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$validation) {
            return redirect()->route('home')
                ->with('error', 'Invalid or expired verification link.');
        }

        $validation->update([
            'verified_at' => now()
        ]);

        // Find existing user or create new client user
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user) {
            // Create new client user if none exists
            $user = \App\Models\User::create([
                'name' => explode('@', $email)[0],
                'email' => $email,
                'password' => \Illuminate\Support\Str::random(32),
                'role' => 'client'
            ]);
        }

        // Log the user in
        \Illuminate\Support\Facades\Auth::login($user);

        // Check if there's an intended URL in the session
        $intendedUrl = session('intended_url');
        if ($intendedUrl) {
            session()->forget('intended_url');
            return redirect($intendedUrl)
                ->with('success', 'Email verified successfully.');
        }

        // Redirect based on user role
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('success', 'Email verified successfully.');
        } elseif ($user->isEmployee()) {
            return redirect()->route('employee.dashboard', ['username' => $user->username])
                ->with('success', 'Email verified successfully.');
        } else {
            // Default redirect for client users
            return redirect()->route('client.upload-files')
                ->with('success', 'Email verified successfully. You can now upload files.');
        }
    }
}
