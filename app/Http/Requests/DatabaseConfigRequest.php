<?php

namespace App\Http\Requests;

use App\Services\DatabaseSetupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Form request for validating database configuration during setup.
 * 
 * Handles validation for both MySQL and SQLite database configurations
 * with custom validation rules for connectivity testing.
 */
class DatabaseConfigRequest extends FormRequest
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
        $databaseType = $this->input('database_type', config('database.default'));
        
        $rules = [
            'database_type' => ['required', 'string', 'in:mysql,sqlite'],
        ];

        if ($databaseType === 'mysql') {
            $rules = array_merge($rules, [
                'mysql_host' => ['required', 'string', 'max:255'],
                'mysql_port' => ['required', 'integer', 'min:1', 'max:65535'],
                'mysql_database' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/'],
                'mysql_username' => ['required', 'string', 'max:32'],
                'mysql_password' => ['nullable', 'string', 'max:255'],
            ]);
        } elseif ($databaseType === 'sqlite') {
            $rules = array_merge($rules, [
                'sqlite_path' => ['nullable', 'string', 'max:255'],
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
            'database_type.required' => 'Please select a database type (MySQL or SQLite).',
            'database_type.in' => 'Database type must be either MySQL or SQLite.',
            
            // MySQL validation messages with helpful hints
            'mysql_host.required' => 'MySQL host is required. Use "localhost" for local servers or your server\'s IP address.',
            'mysql_host.max' => 'MySQL host must not exceed 255 characters.',
            'mysql_port.required' => 'MySQL port is required. The default MySQL port is 3306.',
            'mysql_port.integer' => 'MySQL port must be a valid number (e.g., 3306).',
            'mysql_port.min' => 'MySQL port must be at least 1.',
            'mysql_port.max' => 'MySQL port must not exceed 65535.',
            'mysql_database.required' => 'MySQL database name is required. This should be the name of your database.',
            'mysql_database.max' => 'MySQL database name must not exceed 64 characters.',
            'mysql_database.regex' => 'MySQL database name can only contain letters, numbers, and underscores (no spaces or special characters).',
            'mysql_username.required' => 'MySQL username is required. This is the user that has access to your database.',
            'mysql_username.max' => 'MySQL username must not exceed 32 characters.',
            'mysql_password.max' => 'MySQL password must not exceed 255 characters.',
            
            // SQLite validation messages with helpful hints
            'sqlite_path.max' => 'SQLite database path must not exceed 255 characters.',
        ];
    }

    /**
     * Get validation messages with contextual help.
     *
     * @return array<string, array>
     */
    public function getEnhancedMessages(): array
    {
        return [
            'mysql_host' => [
                'message' => 'MySQL host is required',
                'hint' => 'Use "localhost" for local development, or your server\'s IP/hostname for remote connections',
                'examples' => ['localhost', '127.0.0.1', 'mysql.example.com']
            ],
            'mysql_port' => [
                'message' => 'MySQL port must be a valid number between 1 and 65535',
                'hint' => 'The default MySQL port is 3306. Check with your hosting provider if unsure',
                'examples' => ['3306', '3307', '33060']
            ],
            'mysql_database' => [
                'message' => 'Database name is required and must contain only letters, numbers, and underscores',
                'hint' => 'This is the name of the database you created in your MySQL server',
                'examples' => ['upload_drive_in', 'myapp_production', 'website_db']
            ],
            'mysql_username' => [
                'message' => 'MySQL username is required',
                'hint' => 'This is the MySQL user that has access to your database',
                'examples' => ['root', 'app_user', 'website_admin']
            ],
            'mysql_password' => [
                'message' => 'Password is optional but recommended for security',
                'hint' => 'Leave empty only if your MySQL user has no password (not recommended for production)',
                'security_note' => 'Use a strong password for production environments'
            ]
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
            'database_type' => 'database type',
            'mysql_host' => 'MySQL host',
            'mysql_port' => 'MySQL port',
            'mysql_database' => 'MySQL database',
            'mysql_username' => 'MySQL username',
            'mysql_password' => 'MySQL password',
            'sqlite_path' => 'SQLite database path',
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
            $this->validateDatabaseConnectivity($validator);
        });
    }

    /**
     * Validate database connectivity based on the provided configuration.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    protected function validateDatabaseConnectivity(Validator $validator): void
    {
        $databaseType = $this->input('database_type');
        $databaseSetupService = app(DatabaseSetupService::class);

        try {
            if ($databaseType === 'mysql') {
                $this->validateMySQLConnectivity($validator, $databaseSetupService);
            } elseif ($databaseType === 'sqlite') {
                $this->validateSQLiteConnectivity($validator, $databaseSetupService);
            }
        } catch (\Exception $e) {
            $validator->errors()->add('database_connectivity', 
                'Database connectivity test failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Validate MySQL connectivity.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  \App\Services\DatabaseSetupService  $databaseSetupService
     * @return void
     */
    protected function validateMySQLConnectivity(Validator $validator, DatabaseSetupService $databaseSetupService): void
    {
        $config = [
            'host' => $this->input('mysql_host'),
            'port' => $this->input('mysql_port'),
            'database' => $this->input('mysql_database'),
            'username' => $this->input('mysql_username'),
            'password' => $this->input('mysql_password', ''),
        ];

        try {
            $result = $databaseSetupService->testMySQLConnection($config);
            
            if (!$result['success']) {
                $validator->errors()->add('mysql_connectivity', $result['message']);
                
                // Add specific troubleshooting hints
                if (!empty($result['troubleshooting'])) {
                    $validator->errors()->add('mysql_troubleshooting', 
                        'Troubleshooting steps: ' . implode(' • ', array_slice($result['troubleshooting'], 0, 3))
                    );
                }
            }
            
        } catch (\App\Exceptions\DatabaseSetupException $e) {
            $validator->errors()->add('mysql_connectivity', $e->getUserMessage());
            
            $troubleshootingSteps = $e->getTroubleshootingSteps();
            if (!empty($troubleshootingSteps)) {
                $validator->errors()->add('mysql_troubleshooting', 
                    'Try these steps: ' . implode(' • ', array_slice($troubleshootingSteps, 0, 3))
                );
            }
            
        } catch (\Exception $e) {
            $validator->errors()->add('mysql_connectivity', 
                'Database connection test failed. Please verify your connection settings and try again.'
            );
        }
    }

    /**
     * Validate SQLite connectivity and file permissions.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  \App\Services\DatabaseSetupService  $databaseSetupService
     * @return void
     */
    protected function validateSQLiteConnectivity(Validator $validator, DatabaseSetupService $databaseSetupService): void
    {
        $sqlitePath = $this->input('sqlite_path') ?: database_path('database.sqlite');
        
        // Temporarily update config for validation
        $originalPath = config('database.connections.sqlite.database');
        config(['database.connections.sqlite.database' => $sqlitePath]);
        
        try {
            // Validate SQLite configuration
            $validation = $databaseSetupService->validateDatabaseConfig();
            
            if (!$validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    $validator->errors()->add('sqlite_connectivity', $error);
                }
            } else {
                // Try to initialize the database
                if (!$databaseSetupService->initializeSQLiteDatabase()) {
                    $validator->errors()->add('sqlite_connectivity', 
                        'Unable to initialize SQLite database. Please check file permissions.'
                    );
                }
            }
        } finally {
            // Restore original config
            config(['database.connections.sqlite.database' => $originalPath]);
        }
    }

    /**
     * Get the validated data with proper formatting.
     *
     * @return array
     */
    public function getValidatedDatabaseConfig(): array
    {
        $validated = $this->validated();
        $databaseType = $validated['database_type'];

        if ($databaseType === 'mysql') {
            return [
                'type' => 'mysql',
                'config' => [
                    'host' => $validated['mysql_host'],
                    'port' => $validated['mysql_port'],
                    'database' => $validated['mysql_database'],
                    'username' => $validated['mysql_username'],
                    'password' => $validated['mysql_password'] ?? '',
                ]
            ];
        } elseif ($databaseType === 'sqlite') {
            return [
                'type' => 'sqlite',
                'config' => [
                    'database' => $validated['sqlite_path'] ?: database_path('database.sqlite'),
                ]
            ];
        }

        return [];
    }
}