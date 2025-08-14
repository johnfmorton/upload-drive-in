<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\ListUsers;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ListUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_list_command_signature_is_recognized(): void
    {
        $exitCode = Artisan::call('user:list');
        
        // Command should execute successfully (exit code 0)
        $this->assertEquals(0, $exitCode);
    }

    public function test_users_list_command_alias_is_recognized(): void
    {
        $exitCode = Artisan::call('users:list');
        
        // Command should execute successfully (exit code 0)
        $this->assertEquals(0, $exitCode);
    }

    public function test_both_aliases_execute_same_underlying_functionality(): void
    {
        // Create test users
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
        ]);
        
        $employee = User::factory()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $admin->id,
        ]);

        // Execute user:list command
        $exitCode1 = Artisan::call('user:list');
        $output1 = Artisan::output();

        // Execute users:list command
        $exitCode2 = Artisan::call('users:list');
        $output2 = Artisan::output();

        // Both should have same exit code
        $this->assertEquals($exitCode1, $exitCode2);
        $this->assertEquals(0, $exitCode1);
        $this->assertEquals(0, $exitCode2);

        // Both should contain the same user information
        $this->assertStringContainsString('Admin User', $output1);
        $this->assertStringContainsString('admin@example.com', $output1);
        $this->assertStringContainsString('Employee User', $output1);
        $this->assertStringContainsString('employee@example.com', $output1);

        $this->assertStringContainsString('Admin User', $output2);
        $this->assertStringContainsString('admin@example.com', $output2);
        $this->assertStringContainsString('Employee User', $output2);
        $this->assertStringContainsString('employee@example.com', $output2);

        // Both should show total count
        $this->assertStringContainsString('Total users: 2', $output1);
        $this->assertStringContainsString('Total users: 2', $output2);
    }

    public function test_role_option_works_identically_for_both_aliases(): void
    {
        // Create test users
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
        ]);
        
        $employee = User::factory()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $admin->id,
        ]);

        // Test --role option with user:list
        $exitCode1 = Artisan::call('user:list', ['--role' => 'admin']);
        $output1 = Artisan::output();

        // Test --role option with users:list
        $exitCode2 = Artisan::call('users:list', ['--role' => 'admin']);
        $output2 = Artisan::output();

        // Both should have same exit code
        $this->assertEquals($exitCode1, $exitCode2);
        $this->assertEquals(0, $exitCode1);

        // Both should only show admin user
        $this->assertStringContainsString('Admin User', $output1);
        $this->assertStringNotContainsString('Employee User', $output1);
        $this->assertStringContainsString('Total users: 1', $output1);

        $this->assertStringContainsString('Admin User', $output2);
        $this->assertStringNotContainsString('Employee User', $output2);
        $this->assertStringContainsString('Total users: 1', $output2);
    }

    public function test_owner_option_works_identically_for_both_aliases(): void
    {
        // Create test users
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
        ]);
        
        $employee = User::factory()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $admin->id,
        ]);

        $client = User::factory()->create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
            'owner_id' => $admin->id,
        ]);

        // Test --owner option with user:list
        $exitCode1 = Artisan::call('user:list', ['--owner' => 'admin@example.com']);
        $output1 = Artisan::output();

        // Test --owner option with users:list
        $exitCode2 = Artisan::call('users:list', ['--owner' => 'admin@example.com']);
        $output2 = Artisan::output();

        // Both should have same exit code
        $this->assertEquals($exitCode1, $exitCode2);
        $this->assertEquals(0, $exitCode1);

        // Both should show users owned by admin (employee and client, but not admin itself)
        $this->assertStringContainsString('Employee User', $output1);
        $this->assertStringContainsString('Client User', $output1);
        // Check that admin@example.com doesn't appear in the output (admin user row)
        $this->assertStringNotContainsString('admin@example.com', $output1);
        $this->assertStringContainsString('Total users: 2', $output1);

        $this->assertStringContainsString('Employee User', $output2);
        $this->assertStringContainsString('Client User', $output2);
        // Check that admin@example.com doesn't appear in the output (admin user row)
        $this->assertStringNotContainsString('admin@example.com', $output2);
        $this->assertStringContainsString('Total users: 2', $output2);
    }

    public function test_combined_options_work_identically_for_both_aliases(): void
    {
        // Create test users
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
        ]);
        
        $employee = User::factory()->create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $admin->id,
        ]);

        $client = User::factory()->create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
            'owner_id' => $admin->id,
        ]);

        // Test combined options with user:list
        $exitCode1 = Artisan::call('user:list', [
            '--role' => 'employee',
            '--owner' => 'admin@example.com'
        ]);
        $output1 = Artisan::output();

        // Test combined options with users:list
        $exitCode2 = Artisan::call('users:list', [
            '--role' => 'employee',
            '--owner' => 'admin@example.com'
        ]);
        $output2 = Artisan::output();

        // Both should have same exit code
        $this->assertEquals($exitCode1, $exitCode2);
        $this->assertEquals(0, $exitCode1);

        // Both should only show employee owned by admin
        $this->assertStringContainsString('Employee User', $output1);
        // Check that admin@example.com and client@example.com don't appear (admin and client user rows)
        $this->assertStringNotContainsString('admin@example.com', $output1);
        $this->assertStringNotContainsString('client@example.com', $output1);
        $this->assertStringContainsString('Total users: 1', $output1);

        $this->assertStringContainsString('Employee User', $output2);
        // Check that admin@example.com and client@example.com don't appear (admin and client user rows)
        $this->assertStringNotContainsString('admin@example.com', $output2);
        $this->assertStringNotContainsString('client@example.com', $output2);
        $this->assertStringContainsString('Total users: 1', $output2);
    }

    public function test_invalid_role_error_handling_works_for_both_aliases(): void
    {
        // Test invalid role with user:list
        $exitCode1 = Artisan::call('user:list', ['--role' => 'invalid']);
        $output1 = Artisan::output();

        // Test invalid role with users:list
        $exitCode2 = Artisan::call('users:list', ['--role' => 'invalid']);
        $output2 = Artisan::output();

        // Both should return error exit code (1)
        $this->assertEquals(1, $exitCode1);
        $this->assertEquals(1, $exitCode2);

        // Both should show same error message
        $this->assertStringContainsString("Invalid role 'invalid'", $output1);
        $this->assertStringContainsString('Valid roles are: admin, employee, client', $output1);

        $this->assertStringContainsString("Invalid role 'invalid'", $output2);
        $this->assertStringContainsString('Valid roles are: admin, employee, client', $output2);
    }

    public function test_invalid_owner_error_handling_works_for_both_aliases(): void
    {
        // Test invalid owner with user:list
        $exitCode1 = Artisan::call('user:list', ['--owner' => 'nonexistent@example.com']);
        $output1 = Artisan::output();

        // Test invalid owner with users:list
        $exitCode2 = Artisan::call('users:list', ['--owner' => 'nonexistent@example.com']);
        $output2 = Artisan::output();

        // Both should return error exit code (1)
        $this->assertEquals(1, $exitCode1);
        $this->assertEquals(1, $exitCode2);

        // Both should show same error message
        $this->assertStringContainsString('Owner user with email nonexistent@example.com not found', $output1);
        $this->assertStringContainsString('Owner user with email nonexistent@example.com not found', $output2);
    }

    public function test_empty_result_handling_works_for_both_aliases(): void
    {
        // Test with filter that returns no results
        $exitCode1 = Artisan::call('user:list', ['--role' => 'client']);
        $output1 = Artisan::output();

        $exitCode2 = Artisan::call('users:list', ['--role' => 'client']);
        $output2 = Artisan::output();

        // Both should return success exit code (0)
        $this->assertEquals(0, $exitCode1);
        $this->assertEquals(0, $exitCode2);

        // Both should show "no users found" message
        $this->assertStringContainsString('No users found matching the criteria', $output1);
        $this->assertStringContainsString('No users found matching the criteria', $output2);
    }

    public function test_help_documentation_is_accessible_for_user_list(): void
    {
        $exitCode = Artisan::call('help', ['command_name' => 'user:list']);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('user:list', $output);
        $this->assertStringContainsString('List all users or filter by role and owner', $output);
        $this->assertStringContainsString('--role', $output);
        $this->assertStringContainsString('--owner', $output);
    }

    public function test_help_documentation_is_accessible_for_users_list(): void
    {
        $exitCode = Artisan::call('help', ['command_name' => 'users:list']);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('users:list', $output);
        $this->assertStringContainsString('List all users or filter by role and owner', $output);
        $this->assertStringContainsString('--role', $output);
        $this->assertStringContainsString('--owner', $output);
    }

    public function test_both_commands_appear_in_command_list(): void
    {
        $exitCode = Artisan::call('list');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('user:list', $output);
        $this->assertStringContainsString('users:list', $output);
    }
}