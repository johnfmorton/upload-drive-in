<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudStorageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Auth;
use App\Services\GoogleDriveService;
use App\Services\CloudStorageManager;

class CloudStorageController extends Controller
{
    protected GoogleDriveService $driveService;
    protected CloudStorageManager $storageManager;

    public function __construct(GoogleDriveService $driveService, CloudStorageManager $storageManager)
    {
        $this->driveService = $driveService;
        $this->storageManager = $storageManager;
    }

    /**
     * Display the cloud storage configuration page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $currentFolderId = $user->google_drive_root_folder_id;
        $currentFolderName = '';

        // Check which settings are defined in environment (root folder is now user-specific only)
        $googleDriveEnvSettings = [
            'client_id' => CloudStorageSetting::isDefinedInEnvironment('google-drive', 'client_id'),
            'client_secret' => CloudStorageSetting::isDefinedInEnvironment('google-drive', 'client_secret'),
            'root_folder_id' => false, // Always false - root folder is user-specific only
        ];

        try {
            // Use CloudStorageManager to check if user has any valid connection
            $provider = $this->storageManager->getUserProvider($user);
            if ($provider && $provider->hasValidConnection($user)) {
                // For Google Drive, we can still get folder name using the legacy service
                if ($provider->getProviderName() === 'google-drive' && !empty($currentFolderId)) {
                    $service = $this->driveService->getDriveService($user);
                    $folder = $service->files->get($currentFolderId, ['fields' => 'id,name']);
                    $currentFolderName = $folder->getName();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch folder name', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }

        return view('admin.cloud-storage.index', compact(
            'currentFolderId', 
            'currentFolderName',
            'googleDriveEnvSettings'
        ));
    }

    /**
     * Update Microsoft Teams configuration settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMicrosoftTeams(Request $request)
    {
        $validated = $request->validate([
            'microsoft_teams_client_id' => ['required', 'string'],
            'microsoft_teams_client_secret' => ['nullable', 'string'],
            'microsoft_teams_root_folder_id' => ['required', 'string'],
        ]);

        try {
            // Save Microsoft Teams credentials into .env
            $this->updateEnvironmentValue('MICROSOFT_TEAMS_CLIENT_ID', $validated['microsoft_teams_client_id']);
            if (!empty($validated['microsoft_teams_client_secret'])) {
                $this->updateEnvironmentValue('MICROSOFT_TEAMS_CLIENT_SECRET', $validated['microsoft_teams_client_secret']);
            }
            $this->updateEnvironmentValue('MICROSOFT_TEAMS_ROOT_FOLDER_ID', $validated['microsoft_teams_root_folder_id']);

            // Clear config cache to apply new environment values
            Artisan::call('config:clear');

            Log::info('Microsoft Teams environment variables updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Microsoft Teams environment variables', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update Dropbox configuration settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDropbox(Request $request)
    {
        $validated = $request->validate([
            'dropbox_client_id' => ['required', 'string'],
            'dropbox_client_secret' => ['nullable', 'string'],
            'dropbox_root_folder' => ['required', 'string'],
        ]);

        try {
            // Save Dropbox credentials into .env
            $this->updateEnvironmentValue('DROPBOX_CLIENT_ID', $validated['dropbox_client_id']);
            if (!empty($validated['dropbox_client_secret'])) {
                $this->updateEnvironmentValue('DROPBOX_CLIENT_SECRET', $validated['dropbox_client_secret']);
            }
            $this->updateEnvironmentValue('DROPBOX_ROOT_FOLDER', $validated['dropbox_root_folder']);

            // Clear config cache to apply new environment values
            Artisan::call('config:clear');

            Log::info('Dropbox environment variables updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Dropbox environment variables', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update Google Drive configuration settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleDrive(Request $request)
    {
        $validated = $request->validate([
            'google_drive_client_id' => ['required', 'string'],
            'google_drive_client_secret' => ['nullable', 'string'],
        ]);

        try {
            // Save Google Drive credentials into .env
            $this->updateEnvironmentValue('GOOGLE_DRIVE_CLIENT_ID', $validated['google_drive_client_id']);
            if (!empty($validated['google_drive_client_secret'])) {
                $this->updateEnvironmentValue('GOOGLE_DRIVE_CLIENT_SECRET', $validated['google_drive_client_secret']);
            }

            // Clear config cache to apply new environment values
            Artisan::call('config:clear');

            Log::info('Google Drive environment variables updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Google Drive environment variables', [
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update the default storage provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDefault(Request $request)
    {
        $validated = $request->validate([
            'default_provider' => ['required', 'string', 'in:google-drive,microsoft-teams,dropbox'],
        ]);

        try {
            // Save default provider into .env
            $this->updateEnvironmentValue('CLOUD_STORAGE_DEFAULT', $validated['default_provider']);

            // Clear config cache so new default is applied
            Artisan::call('config:clear');

            Log::info('Default storage provider environment variable updated successfully', [
                'provider' => $validated['default_provider'],
            ]);

            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update default storage provider environment variable', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Save Google Drive client ID and secret.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleDriveCredentials(Request $request)
    {
        $validated = $request->validate([
            'google_drive_client_id' => ['required', 'string'],
            'google_drive_client_secret' => ['nullable', 'string'],
        ]);

        try {
            // Check if values are defined in environment
            if (CloudStorageSetting::isDefinedInEnvironment('google-drive', 'client_id')) {
                return redirect()->back()->with('error', 'Client ID is configured via environment variables and cannot be changed here.');
            }

            if (!empty($validated['google_drive_client_secret']) && 
                CloudStorageSetting::isDefinedInEnvironment('google-drive', 'client_secret')) {
                return redirect()->back()->with('error', 'Client Secret is configured via environment variables and cannot be changed here.');
            }

            // Save to database
            CloudStorageSetting::setValue('google-drive', 'client_id', $validated['google_drive_client_id']);
            
            if (!empty($validated['google_drive_client_secret'])) {
                CloudStorageSetting::setValue('google-drive', 'client_secret', $validated['google_drive_client_secret'], true);
            }

            // Clear config cache
            Artisan::call('config:clear');
            
            Log::info('Google Drive credentials updated successfully');
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Google Drive credentials', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Save credentials and redirect user to Google Drive OAuth.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAndConnectGoogleDrive(Request $request)
    {
        $clientId = CloudStorageSetting::getEffectiveValue('google-drive', 'client_id');
        if (empty($clientId)) {
            return redirect()->back()->with('error', __('messages.client_id').' '.__('messages.error_generic'));
        }

        try {
            $user = Auth::user();
            
            // Use CloudStorageManager to get the appropriate provider
            $provider = $this->storageManager->getProvider('google-drive');
            $isReconnection = $provider->hasValidConnection($user);
            
            $authUrl = $provider->getAuthUrl($user);
            
            Log::info('Initiating Google Drive OAuth flow via CloudStorageManager', [
                'user_id' => $user->id,
                'is_reconnection' => $isReconnection,
                'provider' => $provider->getProviderName()
            ]);
            
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Failed to initiate Google Drive connection', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update Google Drive root folder ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogleDriveRootFolder(Request $request)
    {
        $validated = $request->validate([
            'google_drive_root_folder_id' => ['nullable', 'string'],
        ]);

        try {
            $user = Auth::user();
            
            // Save to user's database record
            $user->google_drive_root_folder_id = $validated['google_drive_root_folder_id'] ?? null;
            $user->save();
            
            Log::info('Google Drive root folder updated successfully', [
                'user_id' => $user->id,
                'folder_id' => $validated['google_drive_root_folder_id']
            ]);
            return redirect()->back()->with('success', __('messages.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update Google Drive root folder', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Update configuration values.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    protected function updateConfig(string $key, $value): void
    {
        $path = config_path('cloud-storage.php');
        $config = include $path;

        // Update nested array using dot notation
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$config;

        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current[$lastKey] = $value;

        // Write back to file
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($path, $content);

        // Clear config cache
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path);
        }
    }

    // Add method to update .env file with key and value
    private function updateEnvironmentValue(string $key, string $value): void
    {
        $path = base_path('.env');
        if (File::exists($path)) {
            $content = File::get($path);
            $escapedValue = '"' . str_replace('"', '\\"', $value) . '"';
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$escapedValue}",
                    $content
                );
            } else {
                $content .= PHP_EOL . "{$key}={$escapedValue}";
            }
            File::put($path, $content);
        }
    }

    /**
     * Handle the OAuth callback for Google Drive.
     */
    public function callback(Request $request)
    {
        if ($request->has('code')) {
            try {
                $this->driveService->handleCallback(Auth::user(), $request->input('code'));
                return redirect()->route('admin.cloud-storage.index')
                    ->with('success', __('messages.google_drive_connected'));
            } catch (\Exception $e) {
                Log::error('Failed to handle Google Drive OAuth callback', ['error' => $e->getMessage()]);
                return redirect()->back()->with('error', __('messages.settings_update_failed'));
            }
        }
        return redirect()->back()->with('error', __('messages.settings_update_failed'));
    }

    /**
     * Disconnect Google Drive for the current user.
     */
    public function disconnect()
    {
        try {
            $user = Auth::user();
            $provider = $this->storageManager->getProvider('google-drive');
            $provider->disconnect($user);
            
            Log::info('User disconnected from Google Drive via CloudStorageManager', [
                'user_id' => $user->id,
                'provider' => $provider->getProviderName()
            ]);
            
            return redirect()->route('admin.cloud-storage.index')
                ->with('success', __('messages.google_drive_disconnected'));
        } catch (\Exception $e) {
            Log::error('Failed to disconnect Google Drive', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Get cloud storage status for the dashboard widget.
     */
    public function getStatus()
    {
        try {
            $user = Auth::user();
            $healthService = app(\App\Services\CloudStorageHealthService::class);
            
            // Get health status for all providers using consolidated status
            $providersHealth = $healthService->getAllProvidersHealth($user);
            
            // Get available providers from storage manager
            $availableProviders = $this->storageManager->getAvailableProviders();
            
            // Get pending uploads count for each provider
            $pendingUploads = \App\Models\FileUpload::where(function($query) use ($user) {
                $query->where('company_user_id', $user->id)
                      ->orWhere('uploaded_by_user_id', $user->id);
            })
            ->whereNull('google_drive_file_id')
            ->whereNull('cloud_storage_error_type')
            ->get()
            ->groupBy('cloud_storage_provider')
            ->map(fn($uploads) => $uploads->count())
            ->toArray();
            
            // Get failed uploads count for each provider
            $failedUploads = \App\Models\FileUpload::where(function($query) use ($user) {
                $query->where('company_user_id', $user->id)
                      ->orWhere('uploaded_by_user_id', $user->id);
            })
            ->whereNotNull('cloud_storage_error_type')
            ->get()
            ->groupBy('cloud_storage_provider')
            ->map(fn($uploads) => $uploads->count())
            ->toArray();
            
            return response()->json([
                'success' => true,
                'providers' => $providersHealth,
                'available_providers' => $availableProviders,
                'pending_uploads' => $pendingUploads,
                'failed_uploads' => $failedUploads,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get cloud storage status', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Unable to retrieve cloud storage status. Please try again.',
                'message' => 'Failed to get status'
            ], 500);
        }
    }

    /**
     * Reconnect a cloud storage provider.
     */
    public function reconnectProvider(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:google-drive,dropbox,onedrive'
        ]);

        try {
            $user = Auth::user();
            $provider = $validated['provider'];
            
            Log::info('Initiating provider reconnection', [
                'user_id' => $user->id,
                'provider' => $provider
            ]);
            
            try {
                $providerInstance = $this->storageManager->getProvider($provider);
                $authUrl = $providerInstance->getAuthUrl($user);
                
                Log::info('Generated reconnection URL via CloudStorageManager', [
                    'user_id' => $user->id,
                    'provider' => $provider
                ]);
                
                return response()->json(['redirect_url' => $authUrl]);
                
            } catch (\Exception $providerException) {
                Log::warning('Failed to get provider for reconnection', [
                    'provider' => $provider,
                    'user_id' => $user->id,
                    'error' => $providerException->getMessage()
                ]);
                return response()->json(['error' => 'Provider not available or configured'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to reconnect provider', [
                'provider' => $validated['provider'],
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to initiate reconnection'], 500);
        }
    }

    /**
     * Test connection to a cloud storage provider.
     */
    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:google-drive,dropbox,onedrive'
        ]);

        try {
            $user = Auth::user();
            $provider = $validated['provider'];
            $healthService = app(\App\Services\CloudStorageHealthService::class);
            
            // Perform comprehensive health check using new proactive validation logic
            $healthStatus = $healthService->checkConnectionHealth($user, $provider);
            
            // Use consolidated status for consistent messaging
            $consolidatedStatus = $healthStatus->consolidated_status ?? 'connection_issues';
            $isHealthy = $consolidatedStatus === 'healthy';
            
            $message = match ($consolidatedStatus) {
                'healthy' => 'Connection test successful - your ' . ucfirst(str_replace('-', ' ', $provider)) . ' integration is working properly',
                'authentication_required' => 'Authentication required - please reconnect your ' . ucfirst(str_replace('-', ' ', $provider)) . ' account',
                'connection_issues' => 'Connection issues detected - ' . ($healthStatus->last_error_message ?? 'unable to connect to ' . ucfirst(str_replace('-', ' ', $provider))),
                'not_connected' => 'Account not connected - please set up your ' . ucfirst(str_replace('-', ' ', $provider)) . ' integration',
                default => 'Connection test failed - ' . ($healthStatus->last_error_message ?? 'unknown error')
            };
            
            return response()->json([
                'success' => $isHealthy,
                'message' => $message,
                'status' => $healthStatus->status,
                'consolidated_status' => $consolidatedStatus,
                'status_message' => $healthStatus->getConsolidatedStatusMessage(),
                'requires_reconnection' => $healthStatus->requires_reconnection ?? false,
                'last_successful_operation' => $healthStatus->last_successful_operation_at?->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to test connection', [
                'user_id' => Auth::id(),
                'provider' => $validated['provider'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Connection test failed due to an unexpected error. Please try again.',
                'message' => 'Connection test failed'
            ], 500);
        }
    }

    /**
     * Get available providers for selection.
     */
    public function getAvailableProviders()
    {
        try {
            $availableProviders = $this->storageManager->getAvailableProviders();
            $providersWithCapabilities = [];
            
            foreach ($availableProviders as $providerName) {
                try {
                    $provider = $this->storageManager->getProvider($providerName);
                    $capabilities = $provider->getCapabilities();
                    
                    $providersWithCapabilities[] = [
                        'name' => $providerName,
                        'display_name' => ucfirst(str_replace('-', ' ', $providerName)),
                        'capabilities' => $capabilities,
                        'auth_type' => $provider->getAuthenticationType(),
                        'storage_model' => $provider->getStorageModel(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('Failed to get provider capabilities', [
                        'provider' => $providerName,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'providers' => $providersWithCapabilities,
                'default_provider' => $this->storageManager->getDefaultProvider()->getProviderName(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get available providers', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve available providers'
            ], 500);
        }
    }

    /**
     * Set user's preferred provider.
     */
    public function setUserProvider(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string'
        ]);

        try {
            $user = Auth::user();
            $provider = $validated['provider'];
            
            // Validate that the provider is available
            $availableProviders = $this->storageManager->getAvailableProviders();
            if (!in_array($provider, $availableProviders)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Provider not available'
                ], 400);
            }
            
            // Switch user to the new provider
            $this->storageManager->switchUserProvider($user, $provider);
            
            Log::info('User switched to new provider', [
                'user_id' => $user->id,
                'provider' => $provider
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Provider preference updated successfully',
                'provider' => $provider
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to set user provider', [
                'user_id' => Auth::id(),
                'provider' => $validated['provider'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to update provider preference'
            ], 500);
        }
    }

    /**
     * Display the provider management interface.
     */
    public function providerManagement()
    {
        try {
            $user = Auth::user();
            $configService = app(\App\Services\CloudConfigurationService::class);
            $healthService = app(\App\Services\CloudStorageHealthService::class);
            
            // Get all available providers with their configurations
            $availableProviders = $this->storageManager->getAvailableProviders();
            $providersData = [];
            
            foreach ($availableProviders as $providerName) {
                try {
                    $provider = $this->storageManager->getProvider($providerName);
                    $config = $configService->getProviderConfig($providerName);
                    $healthStatus = $healthService->checkConnectionHealth($user, $providerName);
                    
                    $providersData[$providerName] = [
                        'name' => $providerName,
                        'display_name' => ucfirst(str_replace('-', ' ', $providerName)),
                        'capabilities' => $provider->getCapabilities(),
                        'auth_type' => $provider->getAuthenticationType(),
                        'storage_model' => $provider->getStorageModel(),
                        'max_file_size' => $provider->getMaxFileSize(),
                        'supported_file_types' => $provider->getSupportedFileTypes(),
                        'configuration' => $config,
                        'health_status' => $healthStatus,
                        'is_configured' => $configService->isProviderConfigured($providerName),
                        'has_connection' => $provider->hasValidConnection($user),
                    ];
                } catch (\Exception $e) {
                    Log::warning('Failed to get provider data', [
                        'provider' => $providerName,
                        'error' => $e->getMessage()
                    ]);
                    
                    $providersData[$providerName] = [
                        'name' => $providerName,
                        'display_name' => ucfirst(str_replace('-', ' ', $providerName)),
                        'error' => 'Failed to load provider data',
                        'is_configured' => false,
                        'has_connection' => false,
                    ];
                }
            }
            
            // Get current user's preferred provider
            $userProvider = $this->storageManager->getUserProvider($user);
            $currentProvider = $userProvider ? $userProvider->getProviderName() : null;
            
            // Get default system provider
            $defaultProvider = $this->storageManager->getDefaultProvider()->getProviderName();
            
            return view('admin.cloud-storage.provider-management', compact(
                'providersData',
                'currentProvider',
                'defaultProvider'
            ));
        } catch (\Exception $e) {
            Log::error('Failed to load provider management interface', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.cloud-storage.index')
                ->with('error', 'Failed to load provider management interface');
        }
    }

    /**
     * Update provider configuration.
     */
    public function updateProviderConfig(Request $request, string $provider)
    {
        try {
            $configService = app(\App\Services\CloudConfigurationService::class);
            
            // Validate that the provider exists
            $availableProviders = $this->storageManager->getAvailableProviders();
            if (!in_array($provider, $availableProviders)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Provider not found'
                ], 404);
            }
            
            // Get provider instance for validation
            $providerInstance = $this->storageManager->getProvider($provider);
            
            // Validate configuration based on provider requirements
            $configData = $request->all();
            $validationResult = $providerInstance->validateConfiguration($configData);
            
            if (!empty($validationResult)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration validation failed',
                    'validation_errors' => $validationResult
                ], 422);
            }
            
            // Update configuration
            $configService->setProviderConfig($provider, $configData);
            
            Log::info('Provider configuration updated', [
                'provider' => $provider,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Provider configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update provider configuration', [
                'provider' => $provider,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to update provider configuration'
            ], 500);
        }
    }

    /**
     * Validate provider configuration without saving.
     */
    public function validateProviderConfig(Request $request, string $provider)
    {
        try {
            // Validate that the provider exists
            $availableProviders = $this->storageManager->getAvailableProviders();
            if (!in_array($provider, $availableProviders)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Provider not found'
                ], 404);
            }
            
            // Get provider instance for validation
            $providerInstance = $this->storageManager->getProvider($provider);
            
            // Validate configuration
            $configData = $request->all();
            $validationResult = $providerInstance->validateConfiguration($configData);
            
            return response()->json([
                'success' => empty($validationResult),
                'validation_errors' => $validationResult,
                'message' => empty($validationResult) 
                    ? 'Configuration is valid' 
                    : 'Configuration validation failed'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to validate provider configuration', [
                'provider' => $provider,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to validate configuration'
            ], 500);
        }
    }

    /**
     * Get detailed provider information.
     */
    public function getProviderDetails(string $provider)
    {
        try {
            $user = Auth::user();
            
            // Validate that the provider exists
            $availableProviders = $this->storageManager->getAvailableProviders();
            if (!in_array($provider, $availableProviders)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Provider not found'
                ], 404);
            }
            
            $providerInstance = $this->storageManager->getProvider($provider);
            $configService = app(\App\Services\CloudConfigurationService::class);
            $healthService = app(\App\Services\CloudStorageHealthService::class);
            
            // Get comprehensive provider information
            $providerData = [
                'name' => $provider,
                'display_name' => ucfirst(str_replace('-', ' ', $provider)),
                'capabilities' => $providerInstance->getCapabilities(),
                'auth_type' => $providerInstance->getAuthenticationType(),
                'storage_model' => $providerInstance->getStorageModel(),
                'max_file_size' => $providerInstance->getMaxFileSize(),
                'supported_file_types' => $providerInstance->getSupportedFileTypes(),
                'configuration' => $configService->getProviderConfig($provider),
                'is_configured' => $configService->isProviderConfigured($provider),
                'has_connection' => $providerInstance->hasValidConnection($user),
            ];
            
            // Get health status if provider is configured
            if ($providerData['is_configured']) {
                $healthStatus = $healthService->checkConnectionHealth($user, $provider);
                $providerData['health_status'] = [
                    'status' => $healthStatus->status,
                    'consolidated_status' => $healthStatus->consolidated_status,
                    'last_check' => $healthStatus->last_check_at?->toISOString(),
                    'last_error' => $healthStatus->last_error_message,
                    'requires_reconnection' => $healthStatus->requires_reconnection ?? false,
                ];
            }
            
            return response()->json([
                'success' => true,
                'provider' => $providerData
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get provider details', [
                'provider' => $provider,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get provider details'
            ], 500);
        }
    }
}
