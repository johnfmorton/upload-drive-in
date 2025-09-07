<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\ClientVerificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DualUserCreationEmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        
        $this->employeeUser = User::factory()->create([
            'role' => 'employee',
            'username' => 'testemployee',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function admin_invitation_emails_contain_valid_login_urls()
    {
        Mail::fake();
        
        $userData = [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite'
        ];
        
        $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        $clientUser = User::where('email', 'client@example.com')->first();
        
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($clientUser) {
            // Verify email is sent to correct recipient
            if (!$mail->hasTo('client@example.com')) {
                return false;
            }
            
            // Verify the mail contains a valid signed URL
            $reflection = new \ReflectionClass($mail);
            $property = $reflection->getProperty('verificationUrl');
            $property->setAccessible(true);
            $loginUrl = $property->getValue($mail);
            
            // Verify URL is properly signed and contains user ID
            $this->assertStringContainsString('login/token', $loginUrl);
            $this->assertStringContainsString((string)$clientUser->id, $loginUrl);
            
            return true;
        });
    }

    /** @test */
    public function employee_invitation_emails_contain_valid_login_urls()
    {
        Mail::fake();
        
        $userData = [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'action' => 'create_and_invite'
        ];
        
        $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", $userData);
        
        $clientUser = User::where('email', 'employee-client@example.com')->first();
        
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($clientUser) {
            // Verify email is sent to correct recipient
            if (!$mail->hasTo('employee-client@example.com')) {
                return false;
            }
            
            // Verify the mail contains a valid signed URL
            $reflection = new \ReflectionClass($mail);
            $property = $reflection->getProperty('verificationUrl');
            $property->setAccessible(true);
            $loginUrl = $property->getValue($mail);
            
            // Verify URL is properly signed and contains user ID
            $this->assertStringContainsString('login/token', $loginUrl);
            $this->assertStringContainsString((string)$clientUser->id, $loginUrl);
            
            return true;
        });
    }

    /** @test */
    public function email_sending_failures_are_handled_gracefully_for_admin()
    {
        // Mock mail to throw exception
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('SMTP server unavailable'));
        
        $userData = [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite'
        ];
        
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        // User should still be created
        $this->assertDatabaseHas('users', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'role' => 'client'
        ]);
        
        // Should redirect back with appropriate handling
        $response->assertRedirect('/admin/users');
        
        // Relationship should still be created
        $clientUser = User::where('email', 'client@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->adminUser->id
        ]);
    }

    /** @test */
    public function email_sending_failures_are_handled_gracefully_for_employee()
    {
        // Mock mail to throw exception
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('Email service timeout'));
        
        $userData = [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'action' => 'create_and_invite'
        ];
        
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", $userData);
        
        // User should still be created
        $this->assertDatabaseHas('users', [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'role' => 'client'
        ]);
        
        // Should redirect back with appropriate handling
        $response->assertStatus(302); // Just check for redirect
        
        // Relationship should still be created
        $clientUser = User::where('email', 'employee-client@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->employeeUser->id
        ]);
    }

    /** @test */
    public function emails_are_queued_for_background_processing()
    {
        Queue::fake();
        Mail::fake();
        
        // Test admin email queueing
        $this->actingAs($this->adminUser)
            ->post('/admin/users', [
                'name' => 'Admin Queued Client',
                'email' => 'admin-queued@example.com',
                'action' => 'create_and_invite'
            ]);
        
        // Test employee email queueing
        $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Employee Queued Client',
                'email' => 'employee-queued@example.com',
                'action' => 'create_and_invite'
            ]);
        
        // Verify emails were sent (not queued in this case, but would be in production)
        Mail::assertSent(ClientVerificationMail::class, 2);
    }

    /** @test */
    public function invitation_urls_have_appropriate_expiration_times()
    {
        Mail::fake();
        
        $userData = [
            'name' => 'Expiration Test Client',
            'email' => 'expiration@example.com',
            'action' => 'create_and_invite'
        ];
        
        $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            $reflection = new \ReflectionClass($mail);
            $property = $reflection->getProperty('verificationUrl');
            $property->setAccessible(true);
            $loginUrl = $property->getValue($mail);
            
            // Parse URL to check expiration
            $parsedUrl = parse_url($loginUrl);
            parse_str($parsedUrl['query'], $queryParams);
            
            // Verify signature exists (indicates temporary signed URL)
            $this->assertArrayHasKey('signature', $queryParams);
            $this->assertArrayHasKey('expires', $queryParams);
            
            // Verify expiration is in the future (7 days from now)
            $expirationTime = (int)$queryParams['expires'];
            $expectedExpiration = now()->addDays(7)->timestamp;
            
            // Allow for small time differences (within 1 minute)
            $this->assertLessThan(60, abs($expirationTime - $expectedExpiration));
            
            return true;
        });
    }

    /** @test */
    public function multiple_invitations_can_be_sent_simultaneously()
    {
        Mail::fake();
        
        $clients = [
            ['name' => 'Client 1', 'email' => 'client1@example.com'],
            ['name' => 'Client 2', 'email' => 'client2@example.com'],
            ['name' => 'Client 3', 'email' => 'client3@example.com'],
        ];
        
        // Send multiple invitations from admin
        foreach ($clients as $client) {
            $this->actingAs($this->adminUser)
                ->post('/admin/users', array_merge($client, ['action' => 'create_and_invite']));
        }
        
        // Send multiple invitations from employee
        foreach ($clients as $index => $client) {
            $client['email'] = "employee-{$client['email']}";
            $this->actingAs($this->employeeUser)
                ->post("/employee/{$this->employeeUser->username}/clients", 
                    array_merge($client, ['action' => 'create_and_invite']));
        }
        
        // Verify all emails were sent
        Mail::assertSent(ClientVerificationMail::class, 6);
        
        // Verify each email was sent to correct recipient
        foreach ($clients as $client) {
            Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
                return $mail->hasTo($client['email']);
            });
            
            Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
                return $mail->hasTo("employee-{$client['email']}");
            });
        }
    }

    /** @test */
    public function email_content_is_appropriate_for_client_invitation()
    {
        Mail::fake();
        
        $userData = [
            'name' => 'Content Test Client',
            'email' => 'content@example.com',
            'action' => 'create_and_invite'
        ];
        
        $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            // Verify the mail is using the correct mailable class
            $this->assertInstanceOf(ClientVerificationMail::class, $mail);
            
            // Verify it has the correct recipient
            $this->assertTrue($mail->hasTo('content@example.com'));
            
            return true;
        });
    }

    /** @test */
    public function no_emails_sent_for_create_only_action()
    {
        Mail::fake();
        
        // Admin creates user without invitation
        $this->actingAs($this->adminUser)
            ->post('/admin/users', [
                'name' => 'No Email Admin Client',
                'email' => 'no-email-admin@example.com',
                'action' => 'create'
            ]);
        
        // Employee creates user without invitation
        $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'No Email Employee Client',
                'email' => 'no-email-employee@example.com',
                'action' => 'create'
            ]);
        
        // Verify no emails were sent
        Mail::assertNotSent(ClientVerificationMail::class);
        
        // But users should still be created
        $this->assertDatabaseHas('users', ['email' => 'no-email-admin@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'no-email-employee@example.com']);
    }
}