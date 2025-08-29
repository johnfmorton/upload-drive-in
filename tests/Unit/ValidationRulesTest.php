<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    public function test_name_validation_rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];
        
        // Test valid names
        $validNames = [
            'John Doe',
            'Jane Smith-Johnson',
            'María García',
            'O\'Connor',
            str_repeat('a', 255), // Exactly 255 characters
        ];
        
        foreach ($validNames as $name) {
            $validator = Validator::make(['name' => $name], $rules);
            $this->assertFalse(
                $validator->fails(),
                "Name '{$name}' should be valid but validation failed: " . 
                implode(', ', $validator->errors()->all())
            );
        }
        
        // Test invalid names
        $invalidNames = [
            null,
            '',
            123,
            [],
            str_repeat('a', 256), // Too long
        ];
        
        foreach ($invalidNames as $name) {
            $validator = Validator::make(['name' => $name], $rules);
            $this->assertTrue(
                $validator->fails(),
                "Name '" . json_encode($name) . "' should be invalid but validation passed"
            );
        }
    }

    public function test_email_validation_rules()
    {
        $rules = [
            'email' => 'required|string|lowercase|email|max:255',
        ];
        
        // Test valid emails
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'admin+tag@company.org',
            'user123@test-domain.com',
        ];
        
        foreach ($validEmails as $email) {
            $validator = Validator::make(['email' => $email], $rules);
            $this->assertFalse(
                $validator->fails(),
                "Email '{$email}' should be valid but validation failed: " . 
                implode(', ', $validator->errors()->all())
            );
        }
        
        // Test invalid emails
        $invalidEmails = [
            null,
            '',
            'invalid-email',
            '@domain.com',
            'user@',
            'user name@domain.com',
            123,
            [],
            str_repeat('a', 250) . '@example.com', // Too long
        ];
        
        foreach ($invalidEmails as $email) {
            $validator = Validator::make(['email' => $email], $rules);
            $this->assertTrue(
                $validator->fails(),
                "Email '" . json_encode($email) . "' should be invalid but validation passed"
            );
        }
    }

    public function test_action_validation_rules()
    {
        $rules = [
            'action' => 'required|in:create,create_and_invite',
        ];
        
        // Test valid actions
        $validActions = [
            'create',
            'create_and_invite',
        ];
        
        foreach ($validActions as $action) {
            $validator = Validator::make(['action' => $action], $rules);
            $this->assertFalse(
                $validator->fails(),
                "Action '{$action}' should be valid but validation failed: " . 
                implode(', ', $validator->errors()->all())
            );
        }
        
        // Test invalid actions
        $invalidActions = [
            null,
            '',
            'invalid_action',
            'create_user',
            'send_invite',
            'CREATE',
            'Create',
            123,
            [],
            'create_and_send',
        ];
        
        foreach ($invalidActions as $action) {
            $validator = Validator::make(['action' => $action], $rules);
            $this->assertTrue(
                $validator->fails(),
                "Action '" . json_encode($action) . "' should be invalid but validation passed"
            );
        }
    }

    public function test_combined_validation_rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255',
            'action' => 'required|in:create,create_and_invite',
        ];
        
        // Test valid complete data
        $validData = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'action' => 'create',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@company.org',
                'action' => 'create_and_invite',
            ],
        ];
        
        foreach ($validData as $data) {
            $validator = Validator::make($data, $rules);
            $this->assertFalse(
                $validator->fails(),
                "Data should be valid but validation failed: " . 
                implode(', ', $validator->errors()->all())
            );
        }
        
        // Test invalid complete data
        $invalidData = [
            // Missing name
            [
                'email' => 'john@example.com',
                'action' => 'create',
            ],
            // Missing email
            [
                'name' => 'John Doe',
                'action' => 'create',
            ],
            // Missing action
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            // Invalid email format
            [
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'action' => 'create',
            ],
            // Invalid action
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'action' => 'invalid_action',
            ],
        ];
        
        foreach ($invalidData as $data) {
            $validator = Validator::make($data, $rules);
            $this->assertTrue(
                $validator->fails(),
                "Data should be invalid but validation passed: " . json_encode($data)
            );
        }
    }

    public function test_email_lowercase_transformation()
    {
        $rules = [
            'email' => 'required|string|email|max:255',
        ];
        
        $mixedCaseEmails = [
            'Test@Example.COM',
            'USER@DOMAIN.ORG',
            'Admin@Company.Co.UK',
        ];
        
        foreach ($mixedCaseEmails as $email) {
            $validator = Validator::make(['email' => $email], $rules);
            $this->assertFalse(
                $validator->fails(),
                "Mixed case email '{$email}' should be valid"
            );
        }
    }

    public function test_validation_error_messages()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255',
            'action' => 'required|in:create,create_and_invite',
        ];
        
        // Test missing required fields
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('name'));
        $this->assertTrue($errors->has('email'));
        $this->assertTrue($errors->has('action'));
    }

    public function test_array_validation_rules()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'action' => ['required', 'in:create,create_and_invite'],
        ];
        
        $validData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'action' => 'create',
        ];
        
        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());
    }

    public function test_edge_case_validation()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255',
            'action' => 'required|in:create,create_and_invite',
        ];
        
        // Test edge cases
        $edgeCases = [
            // Exactly at limits
            [
                'name' => str_repeat('a', 255),
                'email' => str_repeat('a', 243) . '@example.com', // 255 total
                'action' => 'create',
            ],
            // Unicode characters
            [
                'name' => 'José María García-López',
                'email' => 'jose@example.com',
                'action' => 'create_and_invite',
            ],
        ];
        
        foreach ($edgeCases as $data) {
            $validator = Validator::make($data, $rules);
            $this->assertFalse(
                $validator->fails(),
                "Edge case should be valid but validation failed: " . 
                implode(', ', $validator->errors()->all())
            );
        }
    }
}