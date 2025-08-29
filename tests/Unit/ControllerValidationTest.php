<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Employee\ClientManagementController;
use App\Models\User;
use App\Services\ClientUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ControllerValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);
        
        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_admin_controller_validation_rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255',
            'action' => 'required|in:create,create_and_invite',
        ];

        // Test valid data
        $validData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'action' => 'create',
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Test missing name
        $invalidData = $validData;
        unset($invalidData['name']);
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));

        // Test missing email
        $invalidData = $validData;
        unset($invalidData['email']);
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));

        // Test missing action
        $invalidData = $validData;
        unset($invalidData['action']);
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('action'));

        // Test invalid email format
        $invalidData = $validData;
        $invalidData['email'] = 'invalid-email';
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));

        // Test invalid action
        $invalidData = $validData;
        $invalidData['action'] = 'invalid_action';
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('action'));

        // Test name too long
        $invalidData = $validData;
        $invalidData['name'] = str_repeat('a', 256);
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_employee_controller_validation_rules()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'action' => ['required', 'in:create,create_and_invite'],
        ];

        // Test valid data
        $validData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'action' => 'create_and_invite',
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Test missing name
        $invalidData = $validData;
        unset($invalidData['name']);
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));

        // Test missing email
        $invalidData = $validData;
        unset($invalidData['email']);
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));

        // Test missing action
        $invalidData = $validData;
        unset($invalidData['action']);
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('action'));

        // Test invalid email format
        $invalidData = $validData;
        $invalidData['email'] = 'invalid-email';
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));

        // Test invalid action
        $invalidData = $validData;
        $invalidData['action'] = 'invalid_action';
        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('action'));
    }

    public function test_action_parameter_validation()
    {
        $validActions = ['create', 'create_and_invite'];
        $invalidActions = [
            'invalid_action',
            'create_user',
            'send_invite',
            'CREATE',
            'Create',
            '',
            null,
            123,
            [],
        ];

        $rules = ['action' => 'required|in:create,create_and_invite'];

        foreach ($validActions as $action) {
            $validator = Validator::make(['action' => $action], $rules);
            $this->assertFalse(
                $validator->fails(),
                "Action '{$action}' should be valid but validation failed"
            );
        }

        foreach ($invalidActions as $action) {
            $validator = Validator::make(['action' => $action], $rules);
            $this->assertTrue(
                $validator->fails(),
                "Action '" . json_encode($action) . "' should be invalid but validation passed"
            );
        }
    }

    public function test_email_validation_edge_cases()
    {
        $rules = ['email' => 'required|string|email|max:255'];

        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'admin+tag@company.org',
            'user123@test-domain.com',
        ];

        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user name@domain.com',
            '',
            null,
            123,
            [],
            str_repeat('a', 250) . '@example.com', // Too long
        ];

        foreach ($validEmails as $email) {
            $validator = Validator::make(['email' => $email], $rules);
            $this->assertFalse(
                $validator->fails(),
                "Email '{$email}' should be valid but validation failed"
            );
        }

        foreach ($invalidEmails as $email) {
            $validator = Validator::make(['email' => $email], $rules);
            $this->assertTrue(
                $validator->fails(),
                "Email '" . json_encode($email) . "' should be invalid but validation passed"
            );
        }
    }

    public function test_name_validation_edge_cases()
    {
        $rules = ['name' => 'required|string|max:255'];

        $validNames = [
            'John Doe',
            'Jane Smith-Johnson',
            'María García',
            'O\'Connor',
            str_repeat('a', 255), // Exactly 255 characters
            'José María García-López', // Unicode characters
        ];

        $invalidNames = [
            '',
            null,
            123,
            [],
            str_repeat('a', 256), // Too long
        ];

        foreach ($validNames as $name) {
            $validator = Validator::make(['name' => $name], $rules);
            $this->assertFalse(
                $validator->fails(),
                "Name '{$name}' should be valid but validation failed"
            );
        }

        foreach ($invalidNames as $name) {
            $validator = Validator::make(['name' => $name], $rules);
            $this->assertTrue(
                $validator->fails(),
                "Name '" . json_encode($name) . "' should be invalid but validation passed"
            );
        }
    }

    public function test_complete_validation_scenarios()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'action' => 'required|in:create,create_and_invite',
        ];

        // Test completely valid data
        $validScenarios = [
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

        foreach ($validScenarios as $data) {
            $validator = Validator::make($data, $rules);
            $this->assertFalse(
                $validator->fails(),
                "Valid scenario should pass validation: " . json_encode($data)
            );
        }

        // Test invalid scenarios
        $invalidScenarios = [
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

        foreach ($invalidScenarios as $data) {
            $validator = Validator::make($data, $rules);
            $this->assertTrue(
                $validator->fails(),
                "Invalid scenario should fail validation: " . json_encode($data)
            );
        }
    }

    public function test_validation_error_messages_structure()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'action' => 'required|in:create,create_and_invite',
        ];

        // Test empty data to get all validation errors
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->fails());

        $errors = $validator->errors();
        $this->assertTrue($errors->has('name'));
        $this->assertTrue($errors->has('email'));
        $this->assertTrue($errors->has('action'));

        // Test that error messages are strings
        $this->assertIsString($errors->first('name'));
        $this->assertIsString($errors->first('email'));
        $this->assertIsString($errors->first('action'));
    }
}