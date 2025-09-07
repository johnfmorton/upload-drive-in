<?php

namespace App\Http\Controllers;

use App\Models\EmailValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Models\DomainAccessRule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Services\VerificationMailFactory;

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
        Log::info('Email validation attempt initiated', [
            'email' => $request->email,
            'has_intended_url' => !empty($request->intended_url),
            'intended_url' => $request->intended_url,
            'context' => 'public_upload_form',
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email', 'max:255'],
                'intended_url' => ['nullable', 'string', 'url'],
            ]);

            $email = $validated['email'];
            
            // Step 1: Check for existing user FIRST (before applying restrictions)
            try {
                $existingUser = \App\Models\User::where('email', $email)->first();
                
                // Log the user detection result
                if ($existingUser) {
                    Log::info('Existing user detected during validation', [
                        'email' => $email,
                        'user_id' => $existingUser->id,
                        'user_role' => $existingUser->role->value,
                        'user_created_at' => $existingUser->created_at->toISOString(),
                        'detection_successful' => true,
                        'will_bypass_restrictions' => true,
                        'context' => 'user_detection',
                        'timestamp' => now()->toISOString()
                    ]);
                } else {
                    Log::info('No existing user found during validation', [
                        'email' => $email,
                        'detection_successful' => true,
                        'will_apply_restrictions' => true,
                        'context' => 'user_detection',
                        'timestamp' => now()->toISOString()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to check for existing user during validation', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'fallback_action' => 'treat_as_new_user_apply_restrictions',
                    'security_impact' => 'fail_closed_for_new_users',
                    'context' => 'user_detection_error',
                    'timestamp' => now()->toISOString()
                ]);
                // Fall back to treating as new user (apply all restrictions)
                $existingUser = null;
            }
            
            if ($existingUser) {
                // Existing user - bypass all registration restrictions
                return $this->sendVerificationEmailToExistingUser($existingUser, $email, $validated);
            } else {
                // New user - apply all registration restrictions
                return $this->handleNewUserRegistration($email, $validated);
            }

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
     * Send verification email to existing user, bypassing all registration restrictions.
     *
     * @param  \App\Models\User  $user
     * @param  string  $email
     * @param  array  $validated
     * @return \Illuminate\Http\JsonResponse
     */
    private function sendVerificationEmailToExistingUser(\App\Models\User $user, string $email, array $validated)
    {
        // Get domain rules for enhanced logging context
        try {
            $domainRules = DomainAccessRule::first();
        } catch (\Exception $e) {
            Log::error('Failed to load domain rules', [
                'error' => $e->getMessage()
            ]);
            // Fall back to allowing the request (fail open for existing users)
            $domainRules = null;
        }
        
        // Determine which restrictions would have been applied to a new user
        $restrictionsThatWouldApply = [];
        $domainAllowed = true;
        
        if ($domainRules) {
            if (!$domainRules->allow_public_registration) {
                $restrictionsThatWouldApply[] = 'public_registration_disabled';
            }
            
            if (!$domainRules->isEmailAllowed($email)) {
                $restrictionsThatWouldApply[] = 'domain_not_allowed';
                $domainAllowed = false;
            }
        }
        
        // Enhanced structured logging for existing user bypass
        Log::info('Existing user bypassing registration restrictions', [
            'email' => $email,
            'user_id' => $user->id,
            'user_role' => $user->role->value,
            'user_created_at' => $user->created_at->toISOString(),
            'restrictions_bypassed' => $restrictionsThatWouldApply,
            'restrictions_count' => count($restrictionsThatWouldApply),
            'security_settings' => [
                'public_registration_enabled' => $domainRules?->allow_public_registration ?? true,
                'domain_restrictions_mode' => $domainRules?->mode ?? 'none',
                'domain_rules_exist' => (bool)$domainRules,
                'email_domain_would_be_allowed' => $domainAllowed,
            ],
            'bypass_reason' => 'existing_user_detected',
            'context' => 'existing_user_login',
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        // Store intended URL if provided
        if (!empty($validated['intended_url'])) {
            session(['intended_url' => $validated['intended_url']]);
            Log::info('Storing intended URL from form', [
                'intended_url' => $validated['intended_url']
            ]);
        }

        // Create verification record and send email
        return $this->createVerificationAndSendEmail($email, $user);
    }

    /**
     * Handle new user registration with all security restrictions applied.
     *
     * @param  string  $email
     * @param  array  $validated
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleNewUserRegistration(string $email, array $validated)
    {
        // Apply existing security checks for new users
        try {
            $domainRules = DomainAccessRule::first();
        } catch (\Exception $e) {
            Log::error('Failed to load domain rules', [
                'error' => $e->getMessage()
            ]);
            // For new users, fail closed (reject)
            throw ValidationException::withMessages([
                'email' => ['Unable to process registration at this time. Please try again later.'],
            ]);
        }
        
        Log::info('Domain rules loaded for new user validation', [
            'email' => $email,
            'user_exists' => false,
            'domain_rules_loaded' => (bool)$domainRules,
            'security_settings' => [
                'public_registration_enabled' => $domainRules?->allow_public_registration ?? true,
                'domain_restrictions_mode' => $domainRules?->mode ?? 'none',
                'domain_rules_count' => $domainRules ? count($domainRules->rules ?? []) : 0,
            ],
            'context' => 'new_user_security_check',
            'timestamp' => now()->toISOString()
        ]);

        // Check public registration setting
        if ($domainRules && !$domainRules->allow_public_registration) {
            Log::warning('New user blocked by registration restrictions', [
                'email' => $email,
                'user_exists' => false,
                'restriction_type' => 'public_registration_disabled',
                'restriction_enforced' => true,
                'security_settings' => [
                    'public_registration_enabled' => false,
                    'domain_restrictions_mode' => $domainRules->mode,
                    'domain_rules_exist' => true,
                ],
                'context' => 'new_user_registration',
                'timestamp' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'comparison_note' => 'existing_users_would_bypass_this_restriction'
            ]);
            throw ValidationException::withMessages([
                'email' => [__('messages.public_registration_disabled')],
            ]);
        }

        // Check domain restrictions
        if ($domainRules && !$domainRules->isEmailAllowed($email)) {
            $emailDomain = substr(strrchr($email, '@'), 1);
            
            Log::warning('New user blocked by registration restrictions', [
                'email' => $email,
                'email_domain' => $emailDomain,
                'user_exists' => false,
                'restriction_type' => 'domain_not_allowed',
                'restriction_enforced' => true,
                'security_settings' => [
                    'public_registration_enabled' => $domainRules->allow_public_registration,
                    'domain_restrictions_mode' => $domainRules->mode,
                    'domain_rules_exist' => true,
                    'configured_domains' => $domainRules->rules ?? [],
                ],
                'context' => 'new_user_registration',
                'timestamp' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'comparison_note' => 'existing_users_would_bypass_this_restriction'
            ]);
            throw ValidationException::withMessages([
                'email' => [__('messages.email_domain_not_allowed')],
            ]);
        }

        Log::info('New user registration allowed', [
            'email' => $email,
            'user_exists' => false,
            'restrictions_applied' => 'none',
            'security_settings' => [
                'public_registration_enabled' => $domainRules?->allow_public_registration ?? true,
                'domain_restrictions_mode' => $domainRules?->mode ?? 'none',
                'domain_rules_exist' => (bool)$domainRules,
            ],
            'context' => 'new_user_registration',
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip()
        ]);

        // Store intended URL if provided
        if (!empty($validated['intended_url'])) {
            session(['intended_url' => $validated['intended_url']]);
            Log::info('Storing intended URL from form', [
                'intended_url' => $validated['intended_url']
            ]);
        }

        // Create verification record and send email (no existing user)
        return $this->createVerificationAndSendEmail($email, null);
    }

    /**
     * Create verification record and send appropriate email.
     *
     * @param  string  $email
     * @param  \App\Models\User|null  $existingUser
     * @return \Illuminate\Http\JsonResponse
     */
    private function createVerificationAndSendEmail(string $email, ?\App\Models\User $existingUser)
    {
        $verificationCode = Str::random(32);

        Log::info('Creating email validation record', [
            'email' => $email,
            'user_exists' => (bool)$existingUser
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

        // Use VerificationMailFactory to select appropriate template
        $mailFactory = app(VerificationMailFactory::class);
        
        // For public upload context, we prioritize existing user roles but fallback to client
        if ($existingUser) {
            $verificationMail = $mailFactory->createForUser($existingUser, $verificationUrl);
            $detectedContext = $mailFactory->determineContextForUser($existingUser);
        } else {
            // For unknown users in public upload context, use client template as default
            $verificationMail = $mailFactory->createForContext('client', $verificationUrl);
            $detectedContext = 'client';
        }
        
        // Enhanced logging for template selection
        Log::info('Email verification template selected for public upload', [
            'email' => $email,
            'user_exists' => (bool)$existingUser,
            'user_details' => $existingUser ? [
                'user_id' => $existingUser->id,
                'user_role' => $existingUser->role->value,
                'user_created_at' => $existingUser->created_at->toISOString(),
            ] : null,
            'template_selection' => [
                'detected_context' => $detectedContext,
                'mail_class' => get_class($verificationMail),
                'fallback_used' => !$existingUser,
                'template_reason' => $existingUser ? 'role_based_existing_user' : 'default_client_template'
            ],
            'verification_details' => [
                'verification_code_length' => strlen($verificationCode),
                'expires_at' => $validation->expires_at->toISOString(),
                'verification_url_generated' => !empty($verificationUrl)
            ],
            'context' => 'template_selection',
            'timestamp' => now()->toISOString()
        ]);

        try {
            Mail::to($email)->send($verificationMail);
            
            // Log successful email sending
            $mailFactory->logEmailSent($detectedContext, $email);
            
            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully.'
            ]);
        } catch (\Exception $mailException) {
            // Log email sending failure
            $mailFactory->logEmailSendError($detectedContext, $mailException->getMessage(), $email);
            
            Log::error('Failed to send verification email', [
                'email' => $email,
                'error' => $mailException->getMessage(),
                'context' => 'public_upload'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.'
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
        $mailFactory = app(VerificationMailFactory::class);
        
        $validation = EmailValidation::where('email', $email)
            ->where('verification_code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$validation) {
            // Enhanced logging for verification failure
            $mailFactory->logVerificationFailure('unknown', 'Invalid or expired verification code', $email);
            
            Log::warning('Email verification failed - invalid or expired code', [
                'email' => $email,
                'verification_code' => $code,
                'failure_reason' => 'invalid_or_expired_code',
                'context' => 'email_verification_failure',
                'timestamp' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            
            return redirect()->route('home')
                ->with('error', __('messages.account_deletion_verification_invalid'));
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
        
        // Enhanced logging for successful verification by role
        $userRole = $mailFactory->determineContextForUser($user);
        $mailFactory->logVerificationSuccess($userRole, $email);
        
        // Check if there's an intended URL in the session
        $intendedUrl = session('intended_url');
        
        // Additional structured logging for verification completion
        Log::info('Email verification completed successfully', [
            'email' => $email,
            'user_id' => $user->id,
            'user_role' => $user->role->value,
            'verification_context' => $userRole,
            'user_was_existing' => $user->created_at < $validation->created_at,
            'verification_code_used' => $code,
            'intended_url_present' => !empty($intendedUrl),
            'intended_url' => $intendedUrl,
            'context' => 'email_verification_success',
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        if ($intendedUrl) {
            session()->forget('intended_url');
            
            // Check if the intended URL is for a specific employee upload page
            if (preg_match('/\/upload\/([^\/]+)$/', $intendedUrl, $matches)) {
                $employeeName = $matches[1];
                
                // Find the employee by extracting name from email
                $escapedName = str_replace(['%', '_'], ['\%', '\_'], $employeeName);
                $employee = \App\Models\User::where('email', 'LIKE', $escapedName . '@%')
                    ->whereIn('role', [\App\Enums\UserRole::EMPLOYEE, \App\Enums\UserRole::ADMIN])
                    ->first();
                
                if ($employee && $user->isClient()) {
                    // Create client-company user relationship using the service
                    $clientUserService = app(\App\Services\ClientUserService::class);
                    $clientUserService->associateWithCompanyUser($user, $employee);
                    
                    \Illuminate\Support\Facades\Log::info('Created client-company relationship during email verification', [
                        'client_user_id' => $user->id,
                        'client_email' => $user->email,
                        'company_user_id' => $employee->id,
                        'company_user_email' => $employee->email,
                        'intended_url' => $intendedUrl
                    ]);
                }
            }
            
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
            // If this is a new client user with no company relationships, associate with admin
            if ($user->isClient() && $user->companyUsers()->count() === 0) {
                $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
                if ($adminUser) {
                    $clientUserService = app(\App\Services\ClientUserService::class);
                    $clientUserService->associateWithCompanyUser($user, $adminUser);
                    
                    \Illuminate\Support\Facades\Log::info('Associated new client with admin user as fallback', [
                        'client_user_id' => $user->id,
                        'client_email' => $user->email,
                        'admin_user_id' => $adminUser->id,
                        'admin_email' => $adminUser->email
                    ]);
                }
            }
            
            return redirect()->route('client.upload-files')
                ->with('success', 'Email verified successfully. You can now upload files.');
        }
    }
}
