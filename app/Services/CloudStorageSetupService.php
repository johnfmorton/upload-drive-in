<?php

namespace App\Services;

use App\Exceptions\CloudStorageSetupException;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * Service class for handling cloud storage setup and configuration.
 * Manages provider validation, credential testing, and configuration storage.
 */
class CloudStorageSetupService
{
    /**
     * Test Google Drive connection with provided credentials
     *
     * @param string $clientId Google Drive Client ID
     * @param string $clientSecret Google Drive Client Secret
     * @return bool True if connection is successful
     * @throws CloudStorageSetupException If connection test fails
     */
    public function testGoogleDriveConnection(string $clientId, string $clientSecret): bool
    {
        // Validate credential format first
        $validationErrors = $this->validateRequiredFields('google-drive', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ]);
        
        if (!empty($validationErrors)) {
            throw CloudStorageSetupException::invalidGoogleDriveCredentials($validationErrors);
        }

        try {
            $client = new Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->addScope(Drive::DRIVE_FILE);
            $client->addScope(Drive::DRIVE);
            $client->setAccessType('offline');
            $client->setPrompt('consent');
            
            // Set redirect URI for testing
            $redirectUri = $this->generateRedirectUri();
            $client->setRedirectUri($redirectUri);
            
            // Test if we can create the client with valid credentials
            $authUrl = $client->createAuthUrl();
            
            // Validate the generated auth URL
            if (empty($authUrl) || !filter_var($authUrl, FILTER_VALIDATE_URL)) {
                throw CloudStorageSetupException::oauthConfigurationError(
                    'google-drive',
                    'Unable to generate valid authorization URL'
                );
            }
            
            // Additional validation: check if URL contains expected parameters
            $parsedUrl = parse_url($authUrl);
            parse_str($parsedUrl['query'] ?? '', $queryParams);
            
            if (empty($queryParams['client_id']) || $queryParams['client_id'] !== $clientId) {
                throw CloudStorageSetupException::oauthConfigurationError(
                    'google-drive',
                    'Client ID mismatch in authorization URL'
                );
            }
            
            Log::info('Google Drive connection test successful', [
                'client_id' => substr($clientId, 0, 20) . '...',
                'redirect_uri' => $redirectUri
            ]);
            
            return true;
            
        } catch (ConnectException $e) {
            throw CloudStorageSetupException::networkError('google-drive', $e->getMessage());
        } catch (RequestException $e) {
            if ($e->getCode() === 429) {
                throw CloudStorageSetupException::apiQuotaExceeded('google-drive');
            }
            throw CloudStorageSetupException::googleDriveConnectionFailed($e->getMessage(), [
                'http_code' => $e->getCode()
            ]);
        } catch (CloudStorageSetupException $e) {
            throw $e;
        } catch (Exception $e) {
            throw CloudStorageSetupException::googleDriveConnectionFailed($e->getMessage(), [
                'exception_type' => get_class($e)
            ]);
        }
    }

    /**
     * Store Google Drive configuration securely
     *
     * @param array $config Configuration array containing client_id and client_secret
     * @return void
     * @throws CloudStorageSetupException If configuration storage fails
     */
    public function storeGoogleDriveConfig(array $config): void
    {
        $validationErrors = $this->validateRequiredFields('google-drive', $config);
        
        if (!empty($validationErrors)) {
            throw CloudStorageSetupException::invalidGoogleDriveCredentials($validationErrors);
        }
        
        try {
            // Update environment file with new credentials
            $this->updateEnvironmentFile([
                'GOOGLE_DRIVE_CLIENT_ID' => $config['client_id'],
                'GOOGLE_DRIVE_CLIENT_SECRET' => $config['client_secret'],
                'CLOUD_STORAGE_DEFAULT' => 'google-drive'
            ]);
            
            // Update runtime configuration
            Config::set('services.google.client_id', $config['client_id']);
            Config::set('services.google.client_secret', $config['client_secret']);
            Config::set('cloud-storage.default', 'google-drive');
            Config::set('cloud-storage.providers.google-drive.client_id', $config['client_id']);
            Config::set('cloud-storage.providers.google-drive.client_secret', $config['client_secret']);
            
            Log::info('Google Drive configuration stored successfully', [
                'client_id' => substr($config['client_id'], 0, 20) . '...'
            ]);
            
        } catch (Exception $e) {
            throw CloudStorageSetupException::environmentUpdateFailed($e->getMessage());
        }
    }

    /**
     * Generate redirect URI for OAuth setup
     *
     * @return string The redirect URI for Google Drive OAuth
     */
    public function generateRedirectUri(): string
    {
        return config('app.url') . '/admin/cloud-storage/google-drive/callback';
    }

    /**
     * Validate required fields for a specific provider
     *
     * @param string $provider The cloud storage provider name
     * @param array $config Configuration array to validate
     * @return array Array of validation errors (empty if valid)
     * @throws Exception If provider is not supported
     */
    public function validateRequiredFields(string $provider, array $config): array
    {
        $errors = [];
        
        switch ($provider) {
            case 'google-drive':
                if (empty($config['client_id'])) {
                    $errors['client_id'] = 'Google Drive Client ID is required';
                }
                
                if (empty($config['client_secret'])) {
                    $errors['client_secret'] = 'Google Drive Client Secret is required';
                }
                
                // Validate Client ID format (should end with .apps.googleusercontent.com)
                if (!empty($config['client_id']) && !str_ends_with($config['client_id'], '.apps.googleusercontent.com')) {
                    $errors['client_id'] = 'Invalid Google Drive Client ID format';
                }
                
                break;
                
            case 'microsoft-teams':
                if (empty($config['client_id'])) {
                    $errors['client_id'] = 'Microsoft Teams Client ID is required';
                }
                
                if (empty($config['client_secret'])) {
                    $errors['client_secret'] = 'Microsoft Teams Client Secret is required';
                }
                
                break;
                
            case 'dropbox':
                if (empty($config['app_key'])) {
                    $errors['app_key'] = 'Dropbox App Key is required';
                }
                
                if (empty($config['app_secret'])) {
                    $errors['app_secret'] = 'Dropbox App Secret is required';
                }
                
                break;
                
            default:
                throw new Exception("Unsupported cloud storage provider: {$provider}");
        }
        
        return $errors;
    }

    /**
     * Get supported cloud storage providers
     *
     * @return array Array of supported providers with their display names
     */
    public function getSupportedProviders(): array
    {
        return [
            'google-drive' => 'Google Drive',
            'microsoft-teams' => 'Microsoft Teams',
            'dropbox' => 'Dropbox'
        ];
    }

    /**
     * Check if a provider is currently configured
     *
     * @param string $provider The provider to check
     * @return bool True if provider is configured, false otherwise
     */
    public function isProviderConfigured(string $provider): bool
    {
        switch ($provider) {
            case 'google-drive':
                return !empty(config('services.google.client_id')) && 
                       !empty(config('services.google.client_secret'));
                       
            case 'microsoft-teams':
                return !empty(config('cloud-storage.providers.microsoft-teams.client_id')) && 
                       !empty(config('cloud-storage.providers.microsoft-teams.client_secret'));
                       
            case 'dropbox':
                return !empty(config('cloud-storage.providers.dropbox.app_key')) && 
                       !empty(config('cloud-storage.providers.dropbox.app_secret'));
                       
            default:
                return false;
        }
    }

    /**
     * Update environment file with new configuration values
     *
     * @param array $values Key-value pairs to update in .env file
     * @return void
     * @throws CloudStorageSetupException If .env file cannot be updated
     */
    private function updateEnvironmentFile(array $values): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            throw CloudStorageSetupException::environmentUpdateFailed('.env file not found');
        }
        
        if (!is_writable($envPath)) {
            throw CloudStorageSetupException::environmentUpdateFailed('.env file is not writable');
        }
        
        try {
            $envContent = File::get($envPath);
            
            foreach ($values as $key => $value) {
                // Escape special characters in the value
                $escapedValue = $this->escapeEnvironmentValue($value);
                
                // Check if the key already exists
                if (preg_match("/^{$key}=.*$/m", $envContent)) {
                    // Update existing key
                    $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$escapedValue}", $envContent);
                } else {
                    // Add new key at the end
                    $envContent .= "\n{$key}={$escapedValue}";
                }
            }
            
            if (!File::put($envPath, $envContent)) {
                throw CloudStorageSetupException::environmentUpdateFailed('Failed to write to .env file');
            }
            
            Log::info('Environment file updated successfully', ['keys' => array_keys($values)]);
            
        } catch (Exception $e) {
            if ($e instanceof CloudStorageSetupException) {
                throw $e;
            }
            throw CloudStorageSetupException::environmentUpdateFailed($e->getMessage());
        }
    }

    /**
     * Escape environment variable value for .env file
     *
     * @param string $value The value to escape
     * @return string The escaped value
     */
    private function escapeEnvironmentValue(string $value): string
    {
        // If value contains spaces or special characters, wrap in quotes
        if (preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }
        
        return $value;
    }

    /**
     * Get provider configuration template
     *
     * @param string $provider The provider name
     * @return array Configuration template with field definitions
     */
    public function getProviderConfigTemplate(string $provider): array
    {
        switch ($provider) {
            case 'google-drive':
                return [
                    'client_id' => [
                        'label' => 'Client ID',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'your-client-id.apps.googleusercontent.com',
                        'help' => 'Get this from Google Cloud Console'
                    ],
                    'client_secret' => [
                        'label' => 'Client Secret',
                        'type' => 'password',
                        'required' => true,
                        'placeholder' => 'Your Google Drive Client Secret',
                        'help' => 'Get this from Google Cloud Console'
                    ]
                ];
                
            case 'microsoft-teams':
                return [
                    'client_id' => [
                        'label' => 'Application (client) ID',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                        'help' => 'Get this from Azure App Registration'
                    ],
                    'client_secret' => [
                        'label' => 'Client Secret',
                        'type' => 'password',
                        'required' => true,
                        'placeholder' => 'Your Microsoft Teams Client Secret',
                        'help' => 'Get this from Azure App Registration'
                    ]
                ];
                
            case 'dropbox':
                return [
                    'app_key' => [
                        'label' => 'App Key',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'Your Dropbox App Key',
                        'help' => 'Get this from Dropbox App Console'
                    ],
                    'app_secret' => [
                        'label' => 'App Secret',
                        'type' => 'password',
                        'required' => true,
                        'placeholder' => 'Your Dropbox App Secret',
                        'help' => 'Get this from Dropbox App Console'
                    ]
                ];
                
            default:
                return [];
        }
    }
}