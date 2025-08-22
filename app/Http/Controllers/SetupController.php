<?php

namespace App\Http\Controllers;

use App\Exceptions\CloudStorageSetupException;
use App\Exceptions\DatabaseSetupException;
use App\Exceptions\SetupException;
use App\Http\Requests\AdminUserRequest;
use App\Http\Requests\DatabaseConfigRequest;
use App\Http\Requests\StorageConfigRequest;
use App\Services\AssetValidationService;
use App\Services\AuditLogService;
use App\Services\CloudStorageSetupService;
use App\Services\DatabaseSetupService;
use App\Services\SetupErrorHandlingService;
use App\Services\SetupSecurityService;
use App\Services\SetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controller for handling the initial setup wizard flow.
 * 
 * This controller manages the multi-step setup process including:
 * - Welcome screen with system checks
 * - Database configuration and setup
 * - Admin user creation
 * - Cloud storage configuration
 * - Setup completion
 */
class SetupController extends Controller
{
    public function __construct(
        private SetupService $setupService,
        private AssetValidationService $assetValidationService,
        private DatabaseSetupService $databaseSetupService,
        private CloudStorageSetupService $cloudStorageSetupService,
        private SetupErrorHandlingService $errorHandlingService,
        private SetupSecurityService $securityService,
        private AuditLogService $auditLogService
    ) {}

    /**
     * Display the welcome screen with system checks.
     * 
     * Shows initial welcome message and performs basic system requirement checks
     * to ensure the application can be properly configured.
     */
    public function welcome(): View|RedirectResponse
    {
        // Detect and handle any setup interruptions or recovery needs
        $recoveryInfo = $this->setupService->detectAndResumeSetup();
        
        if ($recoveryInfo['interrupted']) {
            Log::info('Setup interruption detected and recovery attempted', $recoveryInfo);
        }

        // Check asset requirements first
        if (!$this->assetValidationService->areAssetRequirementsMet()) {
            Log::info('Asset requirements not met, redirecting to asset instructions');
            return redirect()->route('setup.assets');
        }

        // Perform system checks
        $systemChecks = $this->performSystemChecks();
        
        // Get detailed setup progress
        $progressDetails = $this->setupService->getDetailedProgress();
        $progress = $progressDetails['progress_percentage'];
        $currentStep = $progressDetails['current_step'];
        
        // Create or validate secure setup session
        $sessionValidation = $this->setupService->validateSetupSession();
        if (!$sessionValidation['valid']) {
            $this->setupService->createSecureSetupSession();
        }

        // Mark step as started for timing
        $this->setupService->markStepStarted('welcome');
        
        Log::info('Setup wizard welcome screen accessed', [
            'current_step' => $currentStep,
            'progress' => $progress,
            'system_checks' => $systemChecks,
            'recovery_info' => $recoveryInfo
        ]);

        return view('setup.welcome', [
            'systemChecks' => $systemChecks['checks'],
            'progress' => $progress,
            'currentStep' => $currentStep,
            'progressDetails' => $progressDetails,
            'canProceed' => $systemChecks['overall_status'],
            'recoveryInfo' => $recoveryInfo,
            'systemChecksSummary' => $systemChecks,
            'hasCriticalIssues' => !$systemChecks['overall_status']
        ]);
    }

    /**
     * Display the asset build instructions screen.
     * 
     * Shows instructions for building frontend assets when the Vite manifest
     * is missing or build directory is empty.
     */
    public function showAssetBuildInstructions(): View
    {
        try {
            // Get asset validation results
            $assetStatus = $this->assetValidationService->getAssetBuildStatus();
            $buildInstructions = $this->assetValidationService->getBuildInstructions();
            $missingRequirements = $this->assetValidationService->getMissingAssetRequirements();
            
            // Get setup progress
            $progress = $this->setupService->getSetupProgress();
            $currentStep = 'assets';
            
            Log::info('Asset build instructions screen accessed', [
                'current_step' => $currentStep,
                'asset_ready' => $assetStatus['ready'],
                'missing_count' => count($missingRequirements)
            ]);

            return view('setup.assets', [
                'assetStatus' => $assetStatus,
                'buildInstructions' => $buildInstructions,
                'missingRequirements' => $missingRequirements,
                'progress' => $progress,
                'currentStep' => $currentStep,
                'canProceed' => $assetStatus['ready']
            ]);

        } catch (\Exception $e) {
            $errorInfo = $this->errorHandlingService->handleSetupException($e, 'asset_validation');
            
            Log::error('Failed to display asset build instructions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('setup.assets', [
                'assetStatus' => ['ready' => false, 'checks' => [], 'missing' => []],
                'buildInstructions' => $this->assetValidationService->getBuildInstructions(),
                'missingRequirements' => [],
                'progress' => $this->setupService->getSetupProgress(),
                'currentStep' => 'assets',
                'canProceed' => false,
                'error' => $errorInfo['user_message']
            ]);
        }
    }

    /**
     * Check asset build status via AJAX.
     * 
     * Provides real-time status checking for asset build completion
     * to allow users to verify their build process without page refresh.
     */
    public function checkAssetBuildStatus(): \Illuminate\Http\JsonResponse
    {
        try {
            // Get current asset status
            $assetStatus = $this->assetValidationService->getAssetBuildStatus();
            $missingRequirements = $this->assetValidationService->getMissingAssetRequirements();
            
            Log::info('Asset build status checked via AJAX', [
                'ready' => $assetStatus['ready'],
                'missing_count' => count($missingRequirements)
            ]);

            return response()->json([
                'success' => true,
                'ready' => $assetStatus['ready'],
                'status' => $assetStatus,
                'missing' => $missingRequirements,
                'message' => $assetStatus['ready'] 
                    ? 'Assets are ready! You can proceed to the next step.' 
                    : 'Assets are not ready. Please complete the build process.',
                'next_step_url' => $assetStatus['ready'] ? route('setup.welcome') : null
            ]);

        } catch (\Exception $e) {
            Log::error('Asset build status check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'ready' => false,
                'message' => 'Unable to check asset status: ' . $e->getMessage(),
                'error' => 'Asset validation failed'
            ], 500);
        }
    }

    /**
     * Display the database configuration form.
     * 
     * Shows database setup options for both MySQL and SQLite configurations
     * with dynamic form fields based on database type selection.
     */
    public function showDatabaseForm(): View
    {
        // Check if we should be on this step
        $currentStep = $this->setupService->getSetupStep();
        if ($currentStep !== 'database' && $currentStep !== 'welcome') {
            return redirect()->route('setup.step', ['step' => $currentStep]);
        }

        // Get current database configuration
        $databaseType = $this->databaseSetupService->detectDatabaseType();
        $databaseStatus = $this->databaseSetupService->getDatabaseStatus();
        
        // Get setup progress
        $progress = $this->setupService->getSetupProgress();

        Log::info('Database configuration form displayed', [
            'database_type' => $databaseType,
            'database_status' => $databaseStatus,
            'current_step' => $currentStep
        ]);

        return view('setup.database', [
            'databaseType' => $databaseType,
            'databaseStatus' => $databaseStatus,
            'progress' => $progress,
            'currentStep' => $currentStep,
            'mysqlConfig' => config('database.connections.mysql'),
            'sqliteConfig' => config('database.connections.sqlite')
        ]);
    }

    /**
     * Handle database configuration and setup.
     * 
     * Processes the database configuration form, validates connectivity,
     * and runs migrations to set up the database schema.
     */
    public function configureDatabase(DatabaseConfigRequest $request): RedirectResponse
    {
        try {
            // Validate setup session
            $sessionValidation = $this->setupService->validateSetupSession();
            if (!$sessionValidation['valid']) {
                // Instead of redirecting to welcome, create a new session and continue
                Log::warning('Setup session invalid during database configuration, creating new session', [
                    'violations' => $sessionValidation['violations'],
                    'step' => 'database'
                ]);
                
                $this->setupService->createSecureSetupSession();
                
                // Log the session recreation for security purposes
                $this->securityService->logSecurityEvent('setup_session_recreated', [
                    'original_violations' => $sessionValidation['violations'],
                    'step' => 'database',
                    'action' => 'database_configuration'
                ]);
            }

            // Get validated database configuration
            $databaseConfig = $request->getValidatedDatabaseConfig();
            
            // Additional security validation
            $securityValidation = $this->setupService->validateSetupInput('database', $databaseConfig['config']);
            if (!empty($securityValidation['violations'])) {
                return back()
                    ->withErrors(['security' => 'Security validation failed'])
                    ->with('security_violations', $securityValidation['violations'])
                    ->withInput();
            }
            
            Log::info('Processing database configuration', [
                'type' => $databaseConfig['type']
            ]);

            // Update environment configuration securely
            $envUpdateResult = $this->setupService->updateDatabaseEnvironment($databaseConfig['config']);
            if (!$envUpdateResult['success']) {
                throw new \Exception('Failed to update environment: ' . $envUpdateResult['message']);
            }

            // Initialize database based on type
            if ($databaseConfig['type'] === 'sqlite') {
                $this->databaseSetupService->initializeSQLiteDatabase();
            } elseif ($databaseConfig['type'] === 'mysql') {
                $result = $this->databaseSetupService->testMySQLConnection($databaseConfig['config']);
                if (!$result['success']) {
                    throw new \Exception('MySQL connection test failed: ' . $result['message']);
                }
            }

            // Run database migrations
            $this->databaseSetupService->runMigrations();

            // Mark database step as complete
            $this->setupService->updateSetupStep('database', true);

            // Log database setup completion for audit purposes (if admin user exists)
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
            if ($adminUser) {
                $this->auditLogService->logSetupStepCompletion('database', $adminUser, $request, [
                    'database_type' => $databaseConfig['type']
                ]);
            }

            Log::info('Database configuration completed successfully', [
                'type' => $databaseConfig['type']
            ]);

            // Get step completion details for visual feedback
            $completionDetails = $this->setupService->getStepCompletionDetails('database');
            
            // Redirect to next step
            $nextStep = $this->setupService->getSetupStep();
            return redirect()->route('setup.step', ['step' => $nextStep])
                ->with('success', 'Database configured successfully!')
                ->with('step_completed', [
                    'step' => 'database',
                    'details' => $completionDetails,
                    'next_step' => $nextStep,
                    'progress' => $this->setupService->getSetupProgress()
                ]);

        } catch (DatabaseSetupException $e) {
            $errorInfo = $this->errorHandlingService->handleSetupException($e, 'database_setup');
            
            return back()
                ->withErrors(['database_setup' => $errorInfo['user_message']])
                ->with('setup_error', $errorInfo)
                ->withInput();
                
        } catch (\Exception $e) {
            $errorInfo = $this->errorHandlingService->handleSetupException($e, 'database_setup');
            
            return back()
                ->withErrors(['database_setup' => $errorInfo['user_message']])
                ->with('setup_error', $errorInfo)
                ->withInput();
        }
    }

    /**
     * Display the admin user creation form.
     * 
     * Shows the form for creating the initial administrator account
     * with proper validation and security requirements.
     */
    public function showAdminForm(): View
    {
        // Check if we should be on this step
        $currentStep = $this->setupService->getSetupStep();
        if ($currentStep !== 'admin') {
            return redirect()->route('setup.step', ['step' => $currentStep]);
        }

        // Get setup progress
        $progress = $this->setupService->getSetupProgress();

        Log::info('Admin user creation form displayed', [
            'current_step' => $currentStep,
            'progress' => $progress
        ]);

        return view('setup.admin', [
            'progress' => $progress,
            'currentStep' => $currentStep,
            'passwordRequirements' => AdminUserRequest::getPasswordRequirements()
        ]);
    }

    /**
     * Handle admin user creation.
     * 
     * Processes the admin user creation form, validates the input,
     * and creates the initial administrator account with proper role assignment.
     */
    public function createAdmin(AdminUserRequest $request): RedirectResponse
    {
        // DEBUG: Log that we reached the controller
        \Illuminate\Support\Facades\Log::info('CONTROLLER REACHED: createAdmin method called', [
            'request_method' => $request->method(),
            'request_url' => $request->url(),
            'has_csrf_token' => $request->has('_token'),
            'csrf_token_length' => $request->has('_token') ? strlen($request->input('_token')) : 0,
        ]);
        
        try {
            // Validate setup session
            $sessionValidation = $this->setupService->validateSetupSession();
            if (!$sessionValidation['valid']) {
                // Instead of redirecting to welcome, create a new session and continue
                Log::warning('Setup session invalid during admin creation, creating new session', [
                    'violations' => $sessionValidation['violations'],
                    'step' => 'admin'
                ]);
                
                $this->setupService->createSecureSetupSession();
                
                // Log the session recreation for security purposes
                $this->securityService->logSecurityEvent('setup_session_recreated', [
                    'original_violations' => $sessionValidation['violations'],
                    'step' => 'admin',
                    'action' => 'admin_user_creation'
                ]);
            }

            // Get validated user data
            $userData = $request->getValidatedUserData();
            
            // Additional security validation
            $securityValidation = $this->setupService->validateSetupInput('admin_user', $userData);
            if (!empty($securityValidation['violations'])) {
                return back()
                    ->withErrors(['security' => 'Security validation failed'])
                    ->with('security_violations', $securityValidation['violations'])
                    ->withInput($request->except('password', 'password_confirmation'));
            }
            
            Log::info('Processing admin user creation', [
                'email' => $userData['email'],
                'name' => $userData['name']
            ]);

            // Create the initial admin user
            $adminUser = $this->setupService->createInitialAdmin($userData);

            // Log admin user creation for audit purposes
            $this->auditLogService->logSetupStepCompletion('admin', $adminUser, $request, [
                'admin_user_id' => $adminUser->id,
                'admin_email' => $adminUser->email,
                'admin_name' => $adminUser->name
            ]);

            Log::info('Admin user created successfully', [
                'user_id' => $adminUser->id,
                'email' => $adminUser->email,
                'name' => $adminUser->name
            ]);

            // Get step completion details for visual feedback
            $completionDetails = $this->setupService->getStepCompletionDetails('admin');
            
            // Redirect to next step
            $nextStep = $this->setupService->getSetupStep();
            return redirect()->route('setup.step', ['step' => $nextStep])
                ->with('success', 'Administrator account created successfully!')
                ->with('step_completed', [
                    'step' => 'admin',
                    'details' => $completionDetails,
                    'next_step' => $nextStep,
                    'progress' => $this->setupService->getSetupProgress()
                ]);

        } catch (\Exception $e) {
            $errorInfo = $this->errorHandlingService->handleSetupException($e, 'admin_creation');
            
            return back()
                ->withErrors(['admin_creation' => $errorInfo['user_message']])
                ->with('setup_error', $errorInfo)
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Display the cloud storage configuration form.
     * 
     * Shows the form for configuring cloud storage providers (Google Drive)
     * with credential input and connection testing capabilities.
     */
    public function showStorageForm(): View
    {
        // Check if we should be on this step
        $currentStep = $this->setupService->getSetupStep();
        if ($currentStep !== 'storage') {
            return redirect()->route('setup.step', ['step' => $currentStep]);
        }

        // Get setup progress
        $progress = $this->setupService->getSetupProgress();

        // Get current Google Drive configuration
        $googleConfig = [
            'client_id' => Config::get('services.google.client_id', ''),
            'client_secret' => Config::get('services.google.client_secret', ''),
            'redirect_uri' => route('google-drive.unified-callback')
        ];

        Log::info('Cloud storage configuration form displayed', [
            'current_step' => $currentStep,
            'progress' => $progress,
            'has_google_config' => !empty($googleConfig['client_id'])
        ]);

        return view('setup.storage', [
            'progress' => $progress,
            'currentStep' => $currentStep,
            'googleConfig' => $googleConfig,
            'supportedProviders' => $this->getSupportedStorageProviders()
        ]);
    }

    /**
     * Handle cloud storage configuration.
     * 
     * Processes the cloud storage configuration form, validates credentials,
     * and stores the configuration securely in environment variables.
     */
    public function configureStorage(StorageConfigRequest $request): RedirectResponse
    {
        try {
            // Validate setup session
            $sessionValidation = $this->setupService->validateSetupSession();
            if (!$sessionValidation['valid']) {
                // Instead of redirecting to welcome, create a new session and continue
                Log::warning('Setup session invalid during storage configuration, creating new session', [
                    'violations' => $sessionValidation['violations'],
                    'step' => 'storage'
                ]);
                
                $this->setupService->createSecureSetupSession();
                
                // Log the session recreation for security purposes
                $this->securityService->logSecurityEvent('setup_session_recreated', [
                    'original_violations' => $sessionValidation['violations'],
                    'step' => 'storage',
                    'action' => 'storage_configuration'
                ]);
            }

            // Get validated storage configuration
            $storageConfig = $request->getValidatedStorageConfig();
            
            // Additional security validation
            $securityValidation = $this->setupService->validateSetupInput('storage', $storageConfig['config']);
            if (!empty($securityValidation['violations'])) {
                return back()
                    ->withErrors(['security' => 'Security validation failed'])
                    ->with('security_violations', $securityValidation['violations'])
                    ->withInput();
            }
            
            Log::info('Processing cloud storage configuration', [
                'provider' => $storageConfig['provider']
            ]);

            // Test and store the configuration
            if ($storageConfig['provider'] === 'google-drive') {
                // Test connection first
                $this->cloudStorageSetupService->testGoogleDriveConnection(
                    $storageConfig['config']['client_id'],
                    $storageConfig['config']['client_secret']
                );
                
                // Store configuration securely
                $envUpdateResult = $this->setupService->updateStorageEnvironment($storageConfig['config']);
                if (!$envUpdateResult['success']) {
                    throw new \Exception('Failed to update storage environment: ' . $envUpdateResult['message']);
                }
            }

            // Mark storage step as complete
            $this->setupService->updateSetupStep('storage', true);

            // Log storage setup completion for audit purposes
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
            if ($adminUser) {
                $this->auditLogService->logSetupStepCompletion('storage', $adminUser, $request, [
                    'provider' => $storageConfig['provider']
                ]);
            }

            Log::info('Cloud storage configuration completed successfully', [
                'provider' => $storageConfig['provider']
            ]);

            // Get step completion details for visual feedback
            $completionDetails = $this->setupService->getStepCompletionDetails('storage');
            
            // Redirect to next step
            $nextStep = $this->setupService->getSetupStep();
            return redirect()->route('setup.step', ['step' => $nextStep])
                ->with('success', 'Cloud storage configured successfully!')
                ->with('step_completed', [
                    'step' => 'storage',
                    'details' => $completionDetails,
                    'next_step' => $nextStep,
                    'progress' => $this->setupService->getSetupProgress()
                ]);

        } catch (CloudStorageSetupException $e) {
            $errorInfo = $this->errorHandlingService->handleSetupException($e, 'storage_setup');
            
            return back()
                ->withErrors(['storage_setup' => $errorInfo['user_message']])
                ->with('setup_error', $errorInfo)
                ->withInput();
                
        } catch (\Exception $e) {
            $errorInfo = $this->errorHandlingService->handleSetupException($e, 'storage_setup');
            
            return back()
                ->withErrors(['storage_setup' => $errorInfo['user_message']])
                ->with('setup_error', $errorInfo)
                ->withInput();
        }
    }

    /**
     * Display the setup completion screen.
     * 
     * Shows the final setup completion screen with success messaging
     * and summary of configured components.
     */
    public function showComplete(): View
    {
        // Check if we should be on this step
        $currentStep = $this->setupService->getSetupStep();
        if ($currentStep !== 'complete') {
            return redirect()->route('setup.step', ['step' => $currentStep]);
        }

        // Get setup progress and summary
        $progress = $this->setupService->getSetupProgress();
        $setupSummary = $this->getSetupSummary();

        Log::info('Setup completion screen displayed', [
            'current_step' => $currentStep,
            'progress' => $progress,
            'setup_summary' => $setupSummary
        ]);

        return view('setup.complete', [
            'progress' => $progress,
            'currentStep' => $currentStep,
            'setupSummary' => $setupSummary,
            'nextSteps' => $this->getNextSteps()
        ]);
    }

    /**
     * Test database connection via AJAX.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testDatabaseConnection(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'database_type' => 'required|string|in:mysql,sqlite',
                'host' => 'required_if:database_type,mysql|string',
                'port' => 'required_if:database_type,mysql|integer|min:1|max:65535',
                'database' => 'required|string',
                'username' => 'required_if:database_type,mysql|string',
                'password' => 'nullable|string',
                'sqlite_path' => 'nullable|string'
            ]);

            $databaseType = $request->input('database_type');
            
            if ($databaseType === 'mysql') {
                $config = [
                    'host' => $request->input('host'),
                    'port' => $request->input('port'),
                    'database' => $request->input('database'),
                    'username' => $request->input('username'),
                    'password' => $request->input('password', '')
                ];

                $result = $this->databaseSetupService->testMySQLConnection($config);
                
                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'details' => $result['details'] ?? [],
                    'troubleshooting' => $result['troubleshooting'] ?? [],
                    'hosting_instructions' => $result['hosting_instructions'] ?? []
                ]);
                
            } elseif ($databaseType === 'sqlite') {
                $sqlitePath = $request->input('sqlite_path') ?: database_path('database.sqlite');
                
                // Temporarily update config for testing
                $originalPath = config('database.connections.sqlite.database');
                config(['database.connections.sqlite.database' => $sqlitePath]);
                
                try {
                    $this->databaseSetupService->initializeSQLiteDatabase();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'SQLite database initialized successfully!',
                        'details' => [
                            'database_path' => $sqlitePath,
                            'file_exists' => file_exists($sqlitePath),
                            'file_writable' => is_writable($sqlitePath),
                            'directory_writable' => is_writable(dirname($sqlitePath))
                        ]
                    ]);
                    
                } finally {
                    // Restore original config
                    config(['database.connections.sqlite.database' => $originalPath]);
                }
            }

        } catch (DatabaseSetupException $e) {
            Log::error('Database connection test failed', [
                'error' => $e->getMessage(),
                'database_type' => $request->input('database_type'),
                'host' => $request->input('host'),
                'database' => $request->input('database'),
                'context' => $e->getContext()
            ]);

            $response = [
                'success' => false,
                'message' => $e->getUserMessage(),
                'technical_error' => $e->getMessage(),
                'troubleshooting' => $e->getTroubleshootingSteps()
            ];

            // Add detailed information if available
            if (property_exists($e, 'details') && !empty($e->details)) {
                $response['details'] = $e->details['details'] ?? [];
                $response['hosting_instructions'] = $e->details['hosting_instructions'] ?? [];
            }

            return response()->json($response, 400);

        } catch (\Exception $e) {
            Log::error('Database connection test failed with unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'database_type' => $request->input('database_type'),
                'host' => $request->input('host'),
                'database' => $request->input('database')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while testing the database connection.',
                'technical_error' => $e->getMessage(),
                'troubleshooting' => [
                    'Check the application logs for detailed error information',
                    'Verify all required PHP extensions are installed',
                    'Ensure proper file permissions are set',
                    'Contact your system administrator if the problem persists'
                ]
            ], 500);
        }
    }

    /**
     * Test cloud storage connection via AJAX.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testStorageConnection(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'client_id' => 'required|string',
                'client_secret' => 'required|string'
            ]);

            $clientId = $request->input('client_id');
            $clientSecret = $request->input('client_secret');

            $success = $this->cloudStorageSetupService->testGoogleDriveConnection($clientId, $clientSecret);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Google Drive credentials are valid!' : 'Invalid Google Drive credentials'
            ]);

        } catch (\Exception $e) {
            Log::error('Google Drive connection test failed', [
                'error' => $e->getMessage(),
                'client_id' => substr($request->input('client_id', ''), 0, 20) . '...'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get fresh CSRF token via AJAX.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshCsrfToken(): \Illuminate\Http\JsonResponse
    {
        try {
            // Generate a fresh CSRF token
            $token = csrf_token();
            
            return response()->json([
                'success' => true,
                'token' => $token
            ])->header('X-CSRF-TOKEN', $token);
        } catch (\Exception $e) {
            Log::error('CSRF token refresh failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to refresh security token'
            ], 500);
        }
    }

    /**
     * Validate email availability via AJAX.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateEmail(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $email = $request->input('email');
            
            // Basic email format validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'available' => false,
                    'message' => 'Please enter a valid email address format'
                ]);
            }

            // Check if email already exists
            $exists = \App\Models\User::where('email', $email)->exists();

            if ($exists) {
                return response()->json([
                    'available' => false,
                    'message' => 'This email address is already registered'
                ]);
            }

            // Additional email validation checks
            $domain = substr(strrchr($email, "@"), 1);
            if (empty($domain)) {
                return response()->json([
                    'available' => false,
                    'message' => 'Email address must include a valid domain'
                ]);
            }

            return response()->json([
                'available' => true,
                'message' => 'Email address is available'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'available' => false,
                'message' => 'Please enter a valid email address'
            ], 422);

        } catch (\Exception $e) {
            Log::error('Email validation failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'available' => false,
                'message' => 'Unable to validate email address. Please try again.'
            ], 500);
        }
    }

    /**
     * Validate database field via AJAX.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateDatabaseField(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'field' => 'required|string|in:mysql_host,mysql_port,mysql_database,mysql_username,mysql_password',
                'value' => 'nullable|string'
            ]);

            $field = $request->input('field');
            $value = $request->input('value', '');

            $validation = $this->databaseSetupService->validateField($field, $value);

            return response()->json([
                'valid' => $validation['valid'],
                'message' => $validation['message'],
                'suggestion' => $validation['suggestion'] ?? ''
            ]);

        } catch (\Exception $e) {
            Log::error('Database field validation failed', [
                'error' => $e->getMessage(),
                'field' => $request->input('field'),
                'value' => $request->input('value')
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'Unable to validate field',
                'suggestion' => ''
            ]);
        }
    }

    /**
     * Get database configuration hints and examples via AJAX.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatabaseConfigHints(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $hints = $this->databaseSetupService->getFieldHints();
            $templates = $this->databaseSetupService->getConfigurationTemplates();

            return response()->json([
                'success' => true,
                'hints' => $hints,
                'templates' => $templates
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get database configuration hints', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load configuration hints'
            ]);
        }
    }

    /**
     * Complete the setup process.
     * 
     * Finalizes the setup by marking it as complete and redirecting
     * to the admin dashboard with success messaging.
     */
    public function complete(): RedirectResponse
    {
        try {
            // Verify all steps are actually complete
            if (!$this->verifySetupCompletion()) {
                Log::warning('Setup completion attempted but not all steps are complete');
                return redirect()->route('setup.welcome')
                    ->withErrors(['setup_incomplete' => 'Setup is not complete. Please finish all required steps.']);
            }

            // Get the admin user for audit logging
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
            
            // Mark setup as complete
            $this->setupService->markSetupComplete();

            // Perform cleanup after successful completion
            $this->setupService->cleanupAfterCompletion();

            // Log setup completion for audit purposes
            if ($adminUser) {
                $setupSummary = $this->getSetupSummary();
                $this->auditLogService->logSetupCompletion($adminUser, request(), $setupSummary);
            }

            // Clear any cached configuration
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');

            Log::info('Setup wizard completed successfully', [
                'completed_at' => now()->toISOString(),
                'setup_progress' => $this->setupService->getSetupProgress(),
                'admin_user_id' => $adminUser?->id
            ]);

            // Redirect to admin dashboard with success message
            return redirect()->route('admin.dashboard')
                ->with('success', 'Setup completed successfully! Welcome to Upload Drive-in.');

        } catch (\Exception $e) {
            Log::error('Setup completion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'setup_completion' => 'An unexpected error occurred while completing setup: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Perform system requirement checks.
     * 
     * @return array System check results
     */
    private function performSystemChecks(): array
    {
        $checks = [
            'php_version' => [
                'name' => 'PHP Version',
                'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'pass' : 'fail',
                'message' => 'PHP 8.1+ required (current: ' . PHP_VERSION . ')',
                'required' => true
            ],
            'storage_writable' => [
                'name' => 'Storage Directory',
                'status' => is_writable(storage_path()) ? 'pass' : 'fail',
                'message' => 'Storage directory must be writable',
                'required' => true
            ],
            'env_writable' => [
                'name' => 'Environment File',
                'status' => is_writable(base_path('.env')) ? 'pass' : 'fail',
                'message' => '.env file must be writable for configuration updates',
                'required' => true
            ],
            'curl_extension' => [
                'name' => 'cURL Extension',
                'status' => extension_loaded('curl') ? 'pass' : 'fail',
                'message' => 'cURL extension required for API integrations',
                'required' => true
            ],
            'openssl_extension' => [
                'name' => 'OpenSSL Extension',
                'status' => extension_loaded('openssl') ? 'pass' : 'fail',
                'message' => 'OpenSSL extension required for secure connections',
                'required' => true
            ],
            'pdo_extension' => [
                'name' => 'PDO Extension',
                'status' => extension_loaded('pdo') ? 'pass' : 'fail',
                'message' => 'PDO extension required for database connections',
                'required' => true
            ]
        ];

        // Check database-specific extensions
        $databaseType = $this->databaseSetupService->detectDatabaseType();
        if ($databaseType === 'mysql') {
            $checks['pdo_mysql'] = [
                'name' => 'PDO MySQL Extension',
                'status' => extension_loaded('pdo_mysql') ? 'pass' : 'fail',
                'message' => 'PDO MySQL extension required for MySQL connections',
                'required' => true
            ];
        } elseif ($databaseType === 'sqlite') {
            $checks['pdo_sqlite'] = [
                'name' => 'PDO SQLite Extension',
                'status' => extension_loaded('pdo_sqlite') ? 'pass' : 'fail',
                'message' => 'PDO SQLite extension required for SQLite connections',
                'required' => true
            ];
        }

        // Determine overall status
        $requiredChecks = array_filter($checks, fn($check) => $check['required']);
        $passedRequired = array_filter($requiredChecks, fn($check) => $check['status'] === 'pass');
        $overallStatus = count($passedRequired) === count($requiredChecks);

        return [
            'checks' => $checks,
            'overall_status' => $overallStatus,
            'passed_count' => count(array_filter($checks, fn($check) => $check['status'] === 'pass')),
            'total_count' => count($checks),
            'required_passed' => count($passedRequired),
            'required_total' => count($requiredChecks)
        ];
    }





    /**
     * Get supported storage providers.
     * 
     * @return array Supported storage providers
     */
    private function getSupportedStorageProviders(): array
    {
        return [
            'google-drive' => [
                'name' => 'Google Drive',
                'description' => 'Store files in Google Drive with automatic folder organization',
                'icon' => 'google-drive',
                'setup_url' => 'https://console.developers.google.com/',
                'documentation_url' => 'https://developers.google.com/drive/api/quickstart/php'
            ]
        ];
    }

    /**
     * Test Google Drive configuration.
     * 
     * @param array $config Google Drive configuration
     * @throws \Exception If configuration test fails
     */
    private function testGoogleDriveConfiguration(array $config): void
    {
        try {
            $client = new \Google\Client();
            $client->setClientId($config['client_id']);
            $client->setClientSecret($config['client_secret']);
            $client->setRedirectUri($config['redirect_uri']);
            $client->addScope(\Google\Service\Drive::DRIVE_FILE);
            $client->addScope(\Google\Service\Drive::DRIVE);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            // Test by creating an auth URL
            $authUrl = $client->createAuthUrl();
            
            if (empty($authUrl) || !filter_var($authUrl, FILTER_VALIDATE_URL)) {
                throw new \Exception('Unable to generate Google Drive authorization URL');
            }

            Log::info('Google Drive configuration test successful', [
                'client_id' => substr($config['client_id'], 0, 20) . '...',
                'redirect_uri' => $config['redirect_uri']
            ]);

        } catch (\Exception $e) {
            Log::error('Google Drive configuration test failed', [
                'error' => $e->getMessage(),
                'client_id' => substr($config['client_id'], 0, 20) . '...'
            ]);
            throw new \Exception('Google Drive configuration test failed: ' . $e->getMessage());
        }
    }

    /**
     * Update environment configuration with storage settings.
     * 
     * @param array $storageConfig Storage configuration array
     */
    private function updateStorageEnvironment(array $storageConfig): void
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            Log::warning('Environment file does not exist, skipping storage environment update');
            return;
        }

        $envContent = file_get_contents($envPath);
        
        if ($storageConfig['provider'] === 'google-drive') {
            $config = $storageConfig['config'];
            
            // Update Google Drive configuration
            $envContent = $this->updateEnvValue($envContent, 'GOOGLE_DRIVE_CLIENT_ID', $config['client_id']);
            $envContent = $this->updateEnvValue($envContent, 'GOOGLE_DRIVE_CLIENT_SECRET', $config['client_secret']);
            $envContent = $this->updateEnvValue($envContent, 'CLOUD_STORAGE_DEFAULT', 'google-drive');
        }

        file_put_contents($envPath, $envContent);
        
        Log::info('Environment file updated with storage configuration', [
            'provider' => $storageConfig['provider']
        ]);
    }

    /**
     * Get setup summary for the completion screen.
     * 
     * @return array Setup summary information
     */
    private function getSetupSummary(): array
    {
        $databaseType = $this->databaseSetupService->detectDatabaseType();
        $databaseStatus = $this->databaseSetupService->getDatabaseStatus();
        
        return [
            'database' => [
                'type' => ucfirst($databaseType),
                'status' => $databaseStatus['connected'] ? 'Connected' : 'Not Connected',
                'tables_created' => $databaseStatus['tables_exist'] ? 'Yes' : 'No'
            ],
            'admin_user' => [
                'created' => $this->setupService->isAdminUserCreated() ? 'Yes' : 'No',
                'email' => $this->getAdminUserEmail()
            ],
            'cloud_storage' => [
                'provider' => 'Google Drive',
                'configured' => $this->setupService->isCloudStorageConfigured() ? 'Yes' : 'No',
                'client_id' => $this->getMaskedClientId()
            ],
            'application' => [
                'url' => config('app.url'),
                'environment' => config('app.env'),
                'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled'
            ]
        ];
    }

    /**
     * Get next steps for the administrator.
     * 
     * @return array Next steps information
     */
    private function getNextSteps(): array
    {
        return [
            [
                'title' => 'Connect Google Drive',
                'description' => 'Connect your Google Drive account to start receiving files',
                'action' => 'Go to Cloud Storage Settings',
                'url' => route('admin.cloud-storage.index'),
                'icon' => 'cloud'
            ],
            [
                'title' => 'Create Employee Accounts',
                'description' => 'Add employee accounts to help manage client uploads',
                'action' => 'Manage Users',
                'url' => route('admin.users.index'),
                'icon' => 'users'
            ],
            [
                'title' => 'Configure Upload Settings',
                'description' => 'Customize file upload limits and allowed file types',
                'action' => 'Upload Settings',
                'url' => route('admin.settings.index'),
                'icon' => 'settings'
            ],
            [
                'title' => 'Test File Upload',
                'description' => 'Test the upload process with a sample file',
                'action' => 'Upload Test File',
                'url' => route('admin.file-manager.index'),
                'icon' => 'upload'
            ]
        ];
    }

    /**
     * Verify that all setup steps are actually complete.
     * 
     * @return bool True if setup is complete, false otherwise
     */
    private function verifySetupCompletion(): bool
    {
        try {
            // Check database
            if (!$this->setupService->isDatabaseConfigured()) {
                return false;
            }

            // Check admin user
            if (!$this->setupService->isAdminUserCreated()) {
                return false;
            }

            // Check cloud storage
            if (!$this->setupService->isCloudStorageConfigured()) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Setup completion verification failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get the admin user email for display.
     * 
     * @return string|null Admin user email or null if not found
     */
    private function getAdminUserEmail(): ?string
    {
        try {
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
            return $adminUser?->email;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get masked Google Client ID for display.
     * 
     * @return string Masked client ID
     */
    private function getMaskedClientId(): string
    {
        $clientId = Config::get('services.google.client_id', '');
        if (empty($clientId)) {
            return 'Not configured';
        }

        // Show first 12 characters and last 4 characters
        if (strlen($clientId) > 16) {
            return substr($clientId, 0, 12) . '...' . substr($clientId, -4);
        }

        return $clientId;
    }

    /**
     * Update a specific environment variable value.
     * 
     * @param string $envContent Current environment file content
     * @param string $key Environment variable key
     * @param string $value New value
     * @return string Updated environment content
     */
    private function updateEnvValue(string $envContent, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*$/m";
        $replacement = "{$key}=" . (str_contains($value, ' ') ? "\"{$value}\"" : $value);
        
        if (preg_match($pattern, $envContent)) {
            return preg_replace($pattern, $replacement, $envContent);
        } else {
            return $envContent . "\n{$replacement}";
        }
    }

    /**
     * Get setup recovery information.
     * 
     * Returns detailed information about setup state, backups, and recovery options.
     * Used for debugging and recovery operations.
     */
    public function getRecoveryInfo(): \Illuminate\Http\JsonResponse
    {
        try {
            $recoveryInfo = $this->setupService->getRecoveryInfo();
            $backups = $this->setupService->getAvailableBackups();
            
            return response()->json([
                'success' => true,
                'recovery_info' => $recoveryInfo,
                'available_backups' => $backups,
                'auto_detect_step' => $this->setupService->autoDetectCurrentStep(),
                'current_step' => $this->setupService->getSetupStep(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get setup recovery info', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve recovery information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore setup state from backup.
     * 
     * Restores the setup state from a specified backup file.
     * Used for recovery from corrupted or interrupted setup.
     */
    public function restoreFromBackup(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $backupFile = $request->input('backup_file');
            
            if (empty($backupFile)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Backup file path is required'
                ], 400);
            }

            $success = $this->setupService->restoreStateFromBackup($backupFile);
            
            if ($success) {
                Log::info('Setup state restored from backup', [
                    'backup_file' => $backupFile,
                    'restored_at' => now()->toISOString()
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Setup state restored successfully from backup',
                    'current_step' => $this->setupService->getSetupStep()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to restore from backup. The backup file may be corrupted or invalid.'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to restore setup state from backup', [
                'error' => $e->getMessage(),
                'backup_file' => $request->input('backup_file'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to restore from backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force setup recovery and resumption.
     * 
     * Manually triggers setup recovery process to detect and fix
     * any interruptions or state corruption.
     */
    public function forceRecovery(): \Illuminate\Http\JsonResponse
    {
        try {
            $recoveryInfo = $this->setupService->detectAndResumeSetup();
            
            Log::info('Manual setup recovery triggered', $recoveryInfo);
            
            return response()->json([
                'success' => true,
                'message' => 'Setup recovery completed',
                'recovery_info' => $recoveryInfo,
                'current_step' => $this->setupService->getSetupStep()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to perform setup recovery', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to perform recovery: ' . $e->getMessage()
            ], 500);
        }
    }
}