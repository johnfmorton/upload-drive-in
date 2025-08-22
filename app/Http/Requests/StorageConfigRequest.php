<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Google\Client;
use Exception;

/**
 * Form request for validating cloud storage configuration during setup.
 * 
 * Handles validation for Google Drive credentials and connection testing
 * with proper security validation and API connectivity checks.
 */
class StorageConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // During setup, no authentication is required
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $provider = $this->input('storage_provider', 'google-drive');
        
        $rules = [
            'storage_provider' => ['required', 'string', 'in:google-drive'],
        ];

        if ($provider === 'google-drive') {
            $rules = array_merge($rules, [
                'google_client_id' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[0-9]+-[a-zA-Z0-9]+\.apps\.googleusercontent\.com$/'
                ],
                'google_client_secret' => [
                    'required',
                    'string',
                    'max:255',
                    'min:24',
                    'regex:/^[a-zA-Z0-9_\-\/\+]+$/'
                ],

            ]);
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'storage_provider.required' => 'Storage provider is required.',
            'storage_provider.in' => 'Storage provider must be Google Drive.',
            
            // Google Drive validation messages
            'google_client_id.required' => 'Google Drive Client ID is required.',
            'google_client_id.max' => 'Google Drive Client ID must not exceed 255 characters.',
            'google_client_id.regex' => 'Google Drive Client ID format is invalid. It should end with .apps.googleusercontent.com',
            
            'google_client_secret.required' => 'Google Drive Client Secret is required.',
            'google_client_secret.max' => 'Google Drive Client Secret must not exceed 255 characters.',
            'google_client_secret.min' => 'Google Drive Client Secret must be at least 24 characters.',
            'google_client_secret.regex' => 'Google Drive Client Secret contains invalid characters.',
            

        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'storage_provider' => 'storage provider',
            'google_client_id' => 'Google Drive Client ID',
            'google_client_secret' => 'Google Drive Client Secret',

        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateStorageConnectivity($validator);
        });
    }

    /**
     * Validate storage connectivity based on the provided configuration.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    protected function validateStorageConnectivity(Validator $validator): void
    {
        $provider = $this->input('storage_provider');

        try {
            if ($provider === 'google-drive') {
                $this->validateGoogleDriveConnectivity($validator);
            }
        } catch (Exception $e) {
            $validator->errors()->add('storage_connectivity', 
                'Storage connectivity test failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Validate Google Drive connectivity.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    protected function validateGoogleDriveConnectivity(Validator $validator): void
    {
        $clientId = $this->input('google_client_id');
        $clientSecret = $this->input('google_client_secret');
        $redirectUri = route('google-drive.unified-callback');

        try {
            // Test Google API client initialization
            $client = new Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri($redirectUri);
            $client->addScope(\Google\Service\Drive::DRIVE_FILE);
            $client->addScope(\Google\Service\Drive::DRIVE);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            // Try to create an auth URL to validate the configuration
            $authUrl = $client->createAuthUrl();
            
            if (empty($authUrl) || !filter_var($authUrl, FILTER_VALIDATE_URL)) {
                $validator->errors()->add('google_connectivity', 
                    'Unable to generate Google Drive authorization URL. Please check your credentials.'
                );
                return;
            }

            // Additional validation: check if the client ID and secret combination is valid
            // by attempting to create a valid auth URL with specific state
            $client->setState('test_validation');
            $testAuthUrl = $client->createAuthUrl();
            
            if (empty($testAuthUrl) || !str_contains($testAuthUrl, 'accounts.google.com')) {
                $validator->errors()->add('google_connectivity', 
                    'Google Drive credentials appear to be invalid. Please verify your Client ID and Client Secret.'
                );
            }

        } catch (Exception $e) {
            $validator->errors()->add('google_connectivity', 
                'Google Drive configuration test failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get the validated data formatted for storage configuration.
     *
     * @return array
     */
    public function getValidatedStorageConfig(): array
    {
        $validated = $this->validated();
        $provider = $validated['storage_provider'];

        if ($provider === 'google-drive') {
            return [
                'provider' => 'google-drive',
                'config' => [
                    'client_id' => $validated['google_client_id'],
                    'client_secret' => $validated['google_client_secret'],
                    'redirect_uri' => route('google-drive.unified-callback'),
                ]
            ];
        }

        return [];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'google_client_id' => trim($this->google_client_id ?? ''),
            'google_client_secret' => trim($this->google_client_secret ?? ''),

        ]);
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // Log validation failures for security monitoring
        \Illuminate\Support\Facades\Log::warning('Cloud storage configuration validation failed', [
            'errors' => $validator->errors()->toArray(),
            'provider' => $this->input('storage_provider'),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);

        parent::failedValidation($validator);
    }
}