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
    public function index()
    {
        // If user is logged in, redirect to upload files page
        if (Auth::check()) {
            return redirect()->route('upload-files');
        }

        return view('email-validation-form');
    }

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

    public function verifyEmail(Request $request)
    {
        $validation = EmailValidation::where('email', $request->email)
            ->where('verification_code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$validation) {
            return redirect()->route('home')
                ->with('error', 'Invalid or expired verification link.');
        }

        $validation->update([
            'verified_at' => now()
        ]);

        // Create a user account if it doesn't exist
        $user = \App\Models\User::firstOrCreate(
            ['email' => $request->email],
            [
                'name' => explode('@', $request->email)[0],
                'password' => \Illuminate\Support\Str::random(32),
                'role' => 'client'
            ]
        );

        // Log the user in
        \Illuminate\Support\Facades\Auth::login($user);

        return redirect()->route('upload-files')
            ->with('success', 'Email verified successfully. You can now upload files.');
    }
}
