<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Form request for validating admin user creation during setup.
 * 
 * Handles validation for creating the initial admin user with proper
 * security requirements and email uniqueness validation.
 */
class AdminUserRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[a-zA-Z\s\-\'\.]+$/'
            ],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            'password_confirmation' => [
                'required',
                'string'
            ]
        ];
    }

    /**
     * Get password requirements for display.
     *
     * @return array
     */
    public static function getPasswordRequirements(): array
    {
        return [
            'min_length' => 8,
            'requires_uppercase' => true,
            'requires_lowercase' => true,
            'requires_numbers' => true,
            'requires_symbols' => true,
            'check_compromised' => true,
            'requirements_text' => [
                'At least 8 characters',
                'One uppercase letter (A-Z)',
                'One lowercase letter (a-z)',
                'One number (0-9)',
                'One special character (!@#$%^&*)',
                'Must not be a commonly used password'
            ]
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Administrator name is required.',
            'name.string' => 'Administrator name must be a valid string.',
            'name.max' => 'Administrator name must not exceed 255 characters.',
            'name.min' => 'Administrator name must be at least 2 characters.',
            'name.regex' => 'Administrator name can only contain letters, spaces, hyphens, apostrophes, and periods.',
            
            'email.required' => 'Email address is required.',
            'email.string' => 'Email address must be a valid string.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email address must not exceed 255 characters.',
            'email.unique' => 'This email address is already registered.',
            
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a valid string.',
            'password.confirmed' => 'Password confirmation does not match.',
            
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.string' => 'Password confirmation must be a valid string.',
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
            'name' => 'administrator name',
            'email' => 'email address',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
        ]);
    }

    /**
     * Get the validated data formatted for user creation.
     *
     * @return array
     */
    public function getValidatedUserData(): array
    {
        $validated = $this->validated();
        
        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];
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
        \Illuminate\Support\Facades\Log::warning('Admin user creation validation failed', [
            'errors' => $validator->errors()->toArray(),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);

        parent::failedValidation($validator);
    }
}