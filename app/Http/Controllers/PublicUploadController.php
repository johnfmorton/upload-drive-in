<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationMail;
use App\Models\EmailValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\LoginVerificationMail;

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
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->email;
        $verificationCode = Str::random(32);

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
