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
use App\Services\EmailVerificationMetricsService;
use App\Services\DomainRulesCacheService;
use App\Services\UserLookupPerformanceService;

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
            $existingUser = null;
            $userLookupFailed = false;
            
            try {
                // Use performance-optimized user lookup service
                $userLookupService = app(UserLookupPerformanceService::class);
                $existingUser = $userLookupService->findUserByEmail($email);
                
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
            } catch (\Illuminate\Database\QueryException $e) {
                $userLookupFailed = true;
                Log::error('Database query failed during user lookup', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_type' => 'database_query_exception',
                    'sql_state' => $e->errorInfo[0] ?? null,
                    'fallback_action' => 'treat_as_new_user_apply_restrictions',
                    'security_impact' => 'fail_closed_for_security',
                    'context' => 'user_detection_database_error',
                    'timestamp' => now()->toISOString()
                ]);
                // Fall back to treating as new user (apply all restrictions for security)
                $existingUser = null;
            } catch (\Illuminate\Database\ConnectionException $e) {
                $userLookupFailed = true;
                Log::critical('Database connection failed during user lookup', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'error_type' => 'database_connection_exception',
                    'fallback_action' => 'treat_as_new_user_apply_restrictions',
                    'security_impact' => 'fail_closed_for_security',
                    'context' => 'user_detection_connection_error',
                    'timestamp' => now()->toISOString(),
                    'requires_investigation' => true
                ]);
                // Fall back to treating as new user (apply all restrictions for security)
                $existingUser = null;
            } catch (\Exception $e) {
                $userLookupFailed = true;
                Log::error('Unexpected error during user lookup', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'fallback_action' => 'treat_as_new_user_apply_restrictions',
                    'security_impact' => 'fail_closed_for_security',
                    'context' => 'user_detection_unexpected_error',
                    'timestamp' => now()->toISOString(),
                    'requires_investigation' => true
                ]);
                // Fall back to treating as new user (apply all restrictions for security)
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
        // Get domain rules for enhanced logging context using cached service
        $domainRules = null;
        $domainRulesLookupFailed = false;
        
        try {
            $domainRulesCache = app(DomainRulesCacheService::class);
            $domainRules = $domainRulesCache->getDomainRules();
        } catch (\Illuminate\Database\QueryException $e) {
            $domainRulesLookupFailed = true;
            Log::error('Database query failed during domain rules lookup for existing user', [
                'email' => $email,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_type' => 'database_query_exception',
                'sql_state' => $e->errorInfo[0] ?? null,
                'fallback_action' => 'allow_existing_user_fail_open',
                'security_impact' => 'existing_user_allowed_through',
                'context' => 'domain_rules_lookup_error',
                'timestamp' => now()->toISOString()
            ]);
            // Fall back to allowing the request (fail open for existing users)
            $domainRules = null;
        } catch (\Illuminate\Database\ConnectionException $e) {
            $domainRulesLookupFailed = true;
            Log::critical('Database connection failed during domain rules lookup for existing user', [
                'email' => $email,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'error_type' => 'database_connection_exception',
                'fallback_action' => 'allow_existing_user_fail_open',
                'security_impact' => 'existing_user_allowed_through',
                'context' => 'domain_rules_connection_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
            ]);
            // Fall back to allowing the request (fail open for existing users)
            $domainRules = null;
        } catch (\Exception $e) {
            $domainRulesLookupFailed = true;
            Log::error('Unexpected error during domain rules lookup for existing user', [
                'email' => $email,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'fallback_action' => 'allow_existing_user_fail_open',
                'security_impact' => 'existing_user_allowed_through',
                'context' => 'domain_rules_unexpected_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
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

        // Record metrics for existing user bypass
        $metricsService = app(EmailVerificationMetricsService::class);
        $metricsService->recordExistingUserBypass($user, $restrictionsThatWouldApply);

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
        // Apply existing security checks for new users using cached service
        $domainRules = null;
        $domainRulesLookupFailed = false;
        
        try {
            $domainRulesCache = app(DomainRulesCacheService::class);
            $domainRules = $domainRulesCache->getDomainRules();
        } catch (\Illuminate\Database\QueryException $e) {
            $domainRulesLookupFailed = true;
            Log::error('Database query failed during domain rules lookup for new user', [
                'email' => $email,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_type' => 'database_query_exception',
                'sql_state' => $e->errorInfo[0] ?? null,
                'fallback_action' => 'reject_new_user_fail_closed',
                'security_impact' => 'new_user_registration_blocked',
                'context' => 'domain_rules_lookup_error',
                'timestamp' => now()->toISOString()
            ]);
            // For new users, fail closed (reject)
            throw ValidationException::withMessages([
                'email' => [__('messages.registration_temporarily_unavailable')],
            ]);
        } catch (\Illuminate\Database\ConnectionException $e) {
            $domainRulesLookupFailed = true;
            Log::critical('Database connection failed during domain rules lookup for new user', [
                'email' => $email,
                'error' => $e->getMessage(),
                'error_type' => 'database_connection_exception',
                'fallback_action' => 'reject_new_user_fail_closed',
                'security_impact' => 'new_user_registration_blocked',
                'context' => 'domain_rules_connection_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
            ]);
            // For new users, fail closed (reject)
            throw ValidationException::withMessages([
                'email' => [__('messages.registration_temporarily_unavailable')],
            ]);
        } catch (\Exception $e) {
            $domainRulesLookupFailed = true;
            Log::error('Unexpected error during domain rules lookup for new user', [
                'email' => $email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'fallback_action' => 'reject_new_user_fail_closed',
                'security_impact' => 'new_user_registration_blocked',
                'context' => 'domain_rules_unexpected_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
            ]);
            // For new users, fail closed (reject)
            throw ValidationException::withMessages([
                'email' => [__('messages.registration_temporarily_unavailable')],
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

            // Record metrics for restriction enforcement
            $metricsService = app(EmailVerificationMetricsService::class);
            $metricsService->recordRestrictionEnforcement($email, 'public_registration_disabled', [
                'domain_restrictions_mode' => $domainRules->mode,
                'domain_rules_exist' => true
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

            // Record metrics for restriction enforcement
            $metricsService = app(EmailVerificationMetricsService::class);
            $metricsService->recordRestrictionEnforcement($email, 'domain_not_allowed', [
                'email_domain' => $emailDomain,
                'domain_restrictions_mode' => $domainRules->mode,
                'configured_domains' => $domainRules->rules ?? []
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
        
        $validation = null;
        $validationLookupFailed = false;
        
        try {
            $validation = EmailValidation::where('email', $email)
                ->where('verification_code', $code)
                ->where('expires_at', '>', now())
                ->first();
        } catch (\Illuminate\Database\QueryException $e) {
            $validationLookupFailed = true;
            Log::error('Database query failed during validation lookup', [
                'email' => $email,
                'verification_code' => $code,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_type' => 'database_query_exception',
                'sql_state' => $e->errorInfo[0] ?? null,
                'fallback_action' => 'treat_as_invalid_verification',
                'security_impact' => 'verification_blocked_for_security',
                'context' => 'verification_lookup_error',
                'timestamp' => now()->toISOString()
            ]);
            $validation = null;
        } catch (\Illuminate\Database\ConnectionException $e) {
            $validationLookupFailed = true;
            Log::critical('Database connection failed during validation lookup', [
                'email' => $email,
                'verification_code' => $code,
                'error' => $e->getMessage(),
                'error_type' => 'database_connection_exception',
                'fallback_action' => 'treat_as_invalid_verification',
                'security_impact' => 'verification_blocked_for_security',
                'context' => 'verification_connection_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
            ]);
            $validation = null;
        } catch (\Exception $e) {
            $validationLookupFailed = true;
            Log::error('Unexpected error during validation lookup', [
                'email' => $email,
                'verification_code' => $code,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'fallback_action' => 'treat_as_invalid_verification',
                'security_impact' => 'verification_blocked_for_security',
                'context' => 'verification_unexpected_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
            ]);
            $validation = null;
        }

        if (!$validation) {
            // Enhanced logging for verification failure
            $failureReason = $validationLookupFailed ? 'database_lookup_failed' : 'invalid_or_expired_code';
            $mailFactory->logVerificationFailure('unknown', $failureReason, $email);
            
            Log::warning('Email verification failed', [
                'email' => $email,
                'verification_code' => $code,
                'failure_reason' => $failureReason,
                'validation_lookup_failed' => $validationLookupFailed,
                'context' => 'email_verification_failure',
                'timestamp' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            
            $errorMessage = $validationLookupFailed 
                ? 'Unable to verify email at this time. Please try again later.'
                : __('messages.account_deletion_verification_invalid');
            
            return redirect()->route('home')
                ->with('error', $errorMessage);
        }

        try {
            $validation->update([
                'verified_at' => now()
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database query failed during validation update', [
                'email' => $email,
                'verification_code' => $code,
                'validation_id' => $validation->id,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_type' => 'database_query_exception',
                'sql_state' => $e->errorInfo[0] ?? null,
                'context' => 'verification_update_error',
                'timestamp' => now()->toISOString()
            ]);
            // Continue with verification process - the update failure doesn't prevent login
        } catch (\Illuminate\Database\ConnectionException $e) {
            Log::critical('Database connection failed during validation update', [
                'email' => $email,
                'verification_code' => $code,
                'validation_id' => $validation->id,
                'error' => $e->getMessage(),
                'error_type' => 'database_connection_exception',
                'context' => 'verification_update_connection_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
            ]);
            // Continue with verification process - the update failure doesn't prevent login
        } catch (\Exception $e) {
            Log::error('Unexpected error during validation update', [
                'email' => $email,
                'verification_code' => $code,
                'validation_id' => $validation->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'context' => 'verification_update_unexpected_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
            ]);
            // Continue with verification process - the update failure doesn't prevent login
        }

        // Find existing user or create new client user
        $user = null;
        $userLookupFailed = false;
        
        try {
            $user = \App\Models\User::where('email', $email)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            $userLookupFailed = true;
            Log::error('Database query failed during user lookup in verification', [
                'email' => $email,
                'verification_code' => $code,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_type' => 'database_query_exception',
                'sql_state' => $e->errorInfo[0] ?? null,
                'fallback_action' => 'create_new_client_user',
                'security_impact' => 'potential_duplicate_user_creation',
                'context' => 'verification_user_lookup_error',
                'timestamp' => now()->toISOString()
            ]);
            // Fall back to creating new user (graceful degradation)
            $user = null;
        } catch (\Illuminate\Database\ConnectionException $e) {
            $userLookupFailed = true;
            Log::critical('Database connection failed during user lookup in verification', [
                'email' => $email,
                'verification_code' => $code,
                'error' => $e->getMessage(),
                'error_type' => 'database_connection_exception',
                'fallback_action' => 'create_new_client_user',
                'security_impact' => 'potential_duplicate_user_creation',
                'context' => 'verification_connection_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
            ]);
            // Fall back to creating new user (graceful degradation)
            $user = null;
        } catch (\Exception $e) {
            $userLookupFailed = true;
            Log::error('Unexpected error during user lookup in verification', [
                'email' => $email,
                'verification_code' => $code,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'fallback_action' => 'create_new_client_user',
                'security_impact' => 'potential_duplicate_user_creation',
                'context' => 'verification_unexpected_error',
                'timestamp' => now()->toISOString(),
                'requires_investigation' => true
            ]);
            // Fall back to creating new user (graceful degradation)
            $user = null;
        }
        
        if (!$user) {
            // Create new client user if none exists
            try {
                $user = \App\Models\User::create([
                    'name' => explode('@', $email)[0],
                    'email' => $email,
                    'password' => \Illuminate\Support\Str::random(32),
                    'role' => 'client'
                ]);
                
                Log::info('New client user created during verification', [
                    'email' => $email,
                    'user_id' => $user->id,
                    'user_lookup_failed' => $userLookupFailed,
                    'context' => 'verification_user_creation',
                    'timestamp' => now()->toISOString()
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('Database query failed during user creation in verification', [
                    'email' => $email,
                    'verification_code' => $code,
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_type' => 'database_query_exception',
                    'sql_state' => $e->errorInfo[0] ?? null,
                    'context' => 'verification_user_creation_error',
                    'timestamp' => now()->toISOString()
                ]);
                
                return redirect()->route('home')
                    ->with('error', 'Unable to complete verification. Please try again later.');
            } catch (\Illuminate\Database\ConnectionException $e) {
                Log::critical('Database connection failed during user creation in verification', [
                    'email' => $email,
                    'verification_code' => $code,
                    'error' => $e->getMessage(),
                    'error_type' => 'database_connection_exception',
                    'context' => 'verification_user_creation_connection_error',
                    'timestamp' => now()->toISOString(),
                    'requires_investigation' => true
                ]);
                
                return redirect()->route('home')
                    ->with('error', 'Unable to complete verification. Please try again later.');
            } catch (\Exception $e) {
                Log::error('Unexpected error during user creation in verification', [
                    'email' => $email,
                    'verification_code' => $code,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'context' => 'verification_user_creation_unexpected_error',
                    'timestamp' => now()->toISOString(),
                    'requires_investigation' => true
                ]);
                
                return redirect()->route('home')
                    ->with('error', 'Unable to complete verification. Please try again later.');
            }
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
                $employee = null;
                $employeeLookupFailed = false;
                
                try {
                    $escapedName = str_replace(['%', '_'], ['\%', '\_'], $employeeName);
                    $employee = \App\Models\User::where('email', 'LIKE', $escapedName . '@%')
                        ->whereIn('role', [\App\Enums\UserRole::EMPLOYEE, \App\Enums\UserRole::ADMIN])
                        ->first();
                } catch (\Illuminate\Database\QueryException $e) {
                    $employeeLookupFailed = true;
                    Log::error('Database query failed during employee lookup in verification', [
                        'email' => $email,
                        'employee_name' => $employeeName,
                        'intended_url' => $intendedUrl,
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_type' => 'database_query_exception',
                        'sql_state' => $e->errorInfo[0] ?? null,
                        'fallback_action' => 'skip_employee_association',
                        'context' => 'verification_employee_lookup_error',
                        'timestamp' => now()->toISOString()
                    ]);
                    $employee = null;
                } catch (\Illuminate\Database\ConnectionException $e) {
                    $employeeLookupFailed = true;
                    Log::critical('Database connection failed during employee lookup in verification', [
                        'email' => $email,
                        'employee_name' => $employeeName,
                        'intended_url' => $intendedUrl,
                        'error' => $e->getMessage(),
                        'error_type' => 'database_connection_exception',
                        'fallback_action' => 'skip_employee_association',
                        'context' => 'verification_employee_connection_error',
                        'timestamp' => now()->toISOString(),
                        'requires_investigation' => true
                    ]);
                    $employee = null;
                } catch (\Exception $e) {
                    $employeeLookupFailed = true;
                    Log::error('Unexpected error during employee lookup in verification', [
                        'email' => $email,
                        'employee_name' => $employeeName,
                        'intended_url' => $intendedUrl,
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'fallback_action' => 'skip_employee_association',
                        'context' => 'verification_employee_unexpected_error',
                        'timestamp' => now()->toISOString(),
                        'requires_investigation' => true
                    ]);
                    $employee = null;
                }
                
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
                $adminUser = null;
                $adminLookupFailed = false;
                
                try {
                    $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
                } catch (\Illuminate\Database\QueryException $e) {
                    $adminLookupFailed = true;
                    Log::error('Database query failed during admin lookup for fallback association', [
                        'client_user_id' => $user->id,
                        'client_email' => $user->email,
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_type' => 'database_query_exception',
                        'sql_state' => $e->errorInfo[0] ?? null,
                        'fallback_action' => 'skip_admin_association',
                        'context' => 'verification_admin_lookup_error',
                        'timestamp' => now()->toISOString()
                    ]);
                    $adminUser = null;
                } catch (\Illuminate\Database\ConnectionException $e) {
                    $adminLookupFailed = true;
                    Log::critical('Database connection failed during admin lookup for fallback association', [
                        'client_user_id' => $user->id,
                        'client_email' => $user->email,
                        'error' => $e->getMessage(),
                        'error_type' => 'database_connection_exception',
                        'fallback_action' => 'skip_admin_association',
                        'context' => 'verification_admin_connection_error',
                        'timestamp' => now()->toISOString(),
                        'requires_investigation' => true
                    ]);
                    $adminUser = null;
                } catch (\Exception $e) {
                    $adminLookupFailed = true;
                    Log::error('Unexpected error during admin lookup for fallback association', [
                        'client_user_id' => $user->id,
                        'client_email' => $user->email,
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'fallback_action' => 'skip_admin_association',
                        'context' => 'verification_admin_unexpected_error',
                        'timestamp' => now()->toISOString(),
                        'requires_investigation' => true
                    ]);
                    $adminUser = null;
                }
                
                if ($adminUser) {
                    try {
                        $clientUserService = app(\App\Services\ClientUserService::class);
                        $clientUserService->associateWithCompanyUser($user, $adminUser);
                        
                        \Illuminate\Support\Facades\Log::info('Associated new client with admin user as fallback', [
                            'client_user_id' => $user->id,
                            'client_email' => $user->email,
                            'admin_user_id' => $adminUser->id,
                            'admin_email' => $adminUser->email,
                            'admin_lookup_failed' => $adminLookupFailed
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to associate client with admin user', [
                            'client_user_id' => $user->id,
                            'client_email' => $user->email,
                            'admin_user_id' => $adminUser->id,
                            'error' => $e->getMessage(),
                            'error_type' => get_class($e),
                            'context' => 'verification_admin_association_error',
                            'timestamp' => now()->toISOString()
                        ]);
                        // Continue without association - user can still access upload interface
                    }
                } else {
                    Log::warning('No admin user found for fallback association', [
                        'client_user_id' => $user->id,
                        'client_email' => $user->email,
                        'admin_lookup_failed' => $adminLookupFailed,
                        'context' => 'verification_no_admin_fallback',
                        'timestamp' => now()->toISOString()
                    ]);
                }
            }
            
            return redirect()->route('client.upload-files')
                ->with('success', 'Email verified successfully. You can now upload files.');
        }
    }
}
