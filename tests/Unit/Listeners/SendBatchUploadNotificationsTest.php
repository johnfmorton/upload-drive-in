<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Listeners\SendBatchUploadNotifications;
use App\Events\BatchUploadComplete;
use App\Models\FileUpload;
use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Mail\AdminBatchUploadNotification;
use App\Mail\ClientBatchUploadConfirmation;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Mockery;
use ReflectionClass;

class SendBatchUploadNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private SendBatchUploadNotifications $listener;
    private User $adminUser;
    private User $employeeUser;
    private User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->listener = new SendBatchUploadNotifications();
        
        // Create test users
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
            'name' => 'Admin User'
        ]);
        
        $this->employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'name' => 'Employee User'
        ]);
        
        $this->clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
            'name' => 'Client User'
        ]);

        // Create client-company relationship
        ClientUserRelationship::create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'is_primary' => true
        ]);

        Mail::fake();
        Log::spy();
    }

    /** @test */
    public function it_determines_recipient_for_client_upload_with_specific_recipient()
    {
        // Create a client upload with specific company user selected
        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null
        ]);

        $recipientId = $this->callPrivateMethod('determineRecipientForUpload', [$upload]);

        $this->assertEquals($this->employeeUser->id, $recipientId);
    }

    /** @test */
    public function it_determines_recipient_for_client_upload_without_specific_recipient()
    {
        // Create a client upload without specific company user selected
        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => null,
            'uploaded_by_user_id' => null
        ]);

        $recipientId = $this->callPrivateMethod('determineRecipientForUpload', [$upload]);

        // Should fall back to primary company user
        $this->assertEquals($this->employeeUser->id, $recipientId);
    }

    /** @test */
    public function it_determines_recipient_for_employee_upload()
    {
        // Create an employee upload (uploaded by employee on behalf of client)
        $upload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->employeeUser->id
        ]);

        $recipientId = $this->callPrivateMethod('determineRecipientForUpload', [$upload]);

        // Should notify the uploader (employee)
        $this->assertEquals($this->employeeUser->id, $recipientId);
    }

    /** @test */
    public function it_determines_recipient_for_admin_upload()
    {
        // Create an admin upload (uploaded by admin on behalf of client)
        $upload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->adminUser->id
        ]);

        $recipientId = $this->callPrivateMethod('determineRecipientForUpload', [$upload]);

        // Should notify the uploader (admin)
        $this->assertEquals($this->adminUser->id, $recipientId);
    }

    /** @test */
    public function it_falls_back_to_admin_when_no_valid_recipient_found()
    {
        // Create a client upload with no company user selected
        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => null,
            'uploaded_by_user_id' => null
        ]);

        // Remove the client-company relationship to force fallback
        ClientUserRelationship::where('client_user_id', $this->clientUser->id)->delete();

        $recipientId = $this->callPrivateMethod('determineRecipientForUpload', [$upload]);

        // Should fall back to admin user
        $this->assertEquals($this->adminUser->id, $recipientId);
    }

    /** @test */
    public function it_falls_back_to_admin_when_client_has_no_company_relationship()
    {
        // Create a client user with no company relationships
        $isolatedClient = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'isolated@example.com'
        ]);

        $upload = FileUpload::factory()->create([
            'client_user_id' => $isolatedClient->id,
            'company_user_id' => null,
            'uploaded_by_user_id' => null
        ]);

        $recipientId = $this->callPrivateMethod('determineRecipientForUpload', [$upload]);

        // Should fall back to admin user
        $this->assertEquals($this->adminUser->id, $recipientId);
    }

    /** @test */
    public function it_validates_recipient_correctly()
    {
        // Valid recipient (admin with email)
        $this->assertTrue($this->callPrivateMethod('isValidRecipient', [$this->adminUser->id]));
        
        // Valid recipient (employee with email)
        $this->assertTrue($this->callPrivateMethod('isValidRecipient', [$this->employeeUser->id]));
        
        // Invalid recipient (client user)
        $this->assertFalse($this->callPrivateMethod('isValidRecipient', [$this->clientUser->id]));
        
        // Invalid recipient (non-existent user)
        $this->assertFalse($this->callPrivateMethod('isValidRecipient', [999]));
        
        // Invalid recipient (user with empty email)
        $userWithEmptyEmail = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => ''
        ]);
        $this->assertFalse($this->callPrivateMethod('isValidRecipient', [$userWithEmptyEmail->id]));
    }

    /** @test */
    public function it_finds_primary_company_user_for_client()
    {
        $primaryCompanyUserId = $this->callPrivateMethod('findPrimaryCompanyUserForClient', [$this->clientUser->id]);

        $this->assertEquals($this->employeeUser->id, $primaryCompanyUserId);
    }

    /** @test */
    public function it_returns_null_when_no_primary_company_user_exists()
    {
        // Create a client with no relationships
        $isolatedClient = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'isolated@example.com'
        ]);

        $primaryCompanyUserId = $this->callPrivateMethod('findPrimaryCompanyUserForClient', [$isolatedClient->id]);

        $this->assertNull($primaryCompanyUserId);
    }

    /** @test */
    public function it_gets_fallback_admin_recipient()
    {
        $adminId = $this->callPrivateMethod('getFallbackAdminRecipient');

        $this->assertEquals($this->adminUser->id, $adminId);
    }

    /** @test */
    public function it_falls_back_to_employee_when_no_admin_available()
    {
        // Delete admin user
        $this->adminUser->delete();

        $fallbackId = $this->callPrivateMethod('getFallbackAdminRecipient');

        $this->assertEquals($this->employeeUser->id, $fallbackId);
    }

    /** @test */
    public function it_returns_null_when_no_fallback_recipient_available()
    {
        // Delete all admin and employee users
        User::whereIn('role', [UserRole::ADMIN, UserRole::EMPLOYEE])->delete();

        $fallbackId = $this->callPrivateMethod('getFallbackAdminRecipient');

        $this->assertNull($fallbackId);
    }

    /** @test */
    public function it_analyzes_upload_context_for_client_upload()
    {
        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null
        ]);

        $context = $this->callPrivateMethod('analyzeUploadContext', [$upload]);

        $this->assertEquals('client_upload', $context['upload_type']);
        $this->assertTrue($context['has_client_user']);
        $this->assertTrue($context['has_company_user']);
        $this->assertFalse($context['has_uploaded_by_user']);
    }

    /** @test */
    public function it_analyzes_upload_context_for_employee_upload()
    {
        $upload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->employeeUser->id
        ]);

        $context = $this->callPrivateMethod('analyzeUploadContext', [$upload]);

        $this->assertEquals('employee_admin_upload', $context['upload_type']);
        $this->assertFalse($context['has_client_user']);
        $this->assertFalse($context['has_company_user']);
        $this->assertTrue($context['has_uploaded_by_user']);
    }

    /** @test */
    public function it_gets_recipient_selection_reason_for_client_upload()
    {
        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null
        ]);

        $reason = $this->callPrivateMethod('getRecipientSelectionReason', [$upload, $this->employeeUser->id]);

        $this->assertEquals('selected_company_user_for_client_upload', $reason);
    }

    /** @test */
    public function it_gets_recipient_selection_reason_for_employee_upload()
    {
        $upload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->employeeUser->id
        ]);

        $reason = $this->callPrivateMethod('getRecipientSelectionReason', [$upload, $this->employeeUser->id]);

        $this->assertEquals('uploader_notification_for_employee_admin_upload', $reason);
    }

    /** @test */
    public function it_gets_recipient_selection_reason_for_admin_fallback()
    {
        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => null,
            'uploaded_by_user_id' => null
        ]);

        $reason = $this->callPrivateMethod('getRecipientSelectionReason', [$upload, $this->adminUser->id]);

        $this->assertEquals('admin_fallback_for_client_upload', $reason);
    }

    /** @test */
    public function it_filters_uploads_for_specific_recipient()
    {
        // Create uploads for different recipients
        $uploadForEmployee = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null
        ]);

        $uploadForAdmin = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->adminUser->id,
            'uploaded_by_user_id' => null
        ]);

        $fileUploadIds = [$uploadForEmployee->id, $uploadForAdmin->id];

        // Get uploads for employee
        $uploadsForEmployee = $this->callPrivateMethod('getUploadsForRecipient', [$fileUploadIds, $this->employeeUser->id]);
        
        $this->assertEquals(1, $uploadsForEmployee->count());
        $this->assertEquals($uploadForEmployee->id, $uploadsForEmployee->first()->id);

        // Get uploads for admin
        $uploadsForAdmin = $this->callPrivateMethod('getUploadsForRecipient', [$fileUploadIds, $this->adminUser->id]);
        
        $this->assertEquals(1, $uploadsForAdmin->count());
        $this->assertEquals($uploadForAdmin->id, $uploadsForAdmin->first()->id);
    }

    /** @test */
    public function it_handles_batch_uploads_with_mixed_recipient_types()
    {
        // Create mixed uploads
        $clientUpload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null
        ]);

        $employeeUpload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->adminUser->id
        ]);

        $fileUploadIds = [$clientUpload->id, $employeeUpload->id];

        // Test that each upload gets the correct recipient
        $clientUploadRecipient = $this->callPrivateMethod('determineRecipientForUpload', [$clientUpload]);
        $employeeUploadRecipient = $this->callPrivateMethod('determineRecipientForUpload', [$employeeUpload]);

        $this->assertEquals($this->employeeUser->id, $clientUploadRecipient);
        $this->assertEquals($this->adminUser->id, $employeeUploadRecipient);

        // Test filtering works correctly for each recipient
        $uploadsForEmployee = $this->callPrivateMethod('getUploadsForRecipient', [$fileUploadIds, $this->employeeUser->id]);
        $uploadsForAdmin = $this->callPrivateMethod('getUploadsForRecipient', [$fileUploadIds, $this->adminUser->id]);

        $this->assertEquals(1, $uploadsForEmployee->count());
        $this->assertEquals($clientUpload->id, $uploadsForEmployee->first()->id);

        $this->assertEquals(1, $uploadsForAdmin->count());
        $this->assertEquals($employeeUpload->id, $uploadsForAdmin->first()->id);
    }

    /** @test */
    public function it_handles_invalid_recipient_data_gracefully()
    {
        // Test with invalid user ID format - need to handle type checking
        try {
            $this->callPrivateMethod('isValidRecipient', ['invalid']);
            $this->fail('Expected TypeError was not thrown');
        } catch (\TypeError $e) {
            $this->assertStringContainsString('must be of type int', $e->getMessage());
        }

        // Test with negative and zero IDs
        $this->assertFalse($this->callPrivateMethod('isValidRecipient', [-1]));
        $this->assertFalse($this->callPrivateMethod('isValidRecipient', [0]));

        // Test with user having invalid email format
        $userWithInvalidEmail = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'invalid-email-format'
        ]);
        $this->assertFalse($this->callPrivateMethod('isValidRecipient', [$userWithInvalidEmail->id]));
    }

    /** @test */
    public function it_handles_missing_upload_data_gracefully()
    {
        // Create upload with minimal data
        $upload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => null
        ]);

        $recipientId = $this->callPrivateMethod('determineRecipientForUpload', [$upload]);

        // Should fall back to admin
        $this->assertEquals($this->adminUser->id, $recipientId);
    }

    /** @test */
    public function it_handles_exception_during_recipient_determination()
    {
        // Create a real upload with minimal data that will trigger fallback logic
        $upload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => null
        ]);

        $recipientId = $this->callPrivateMethod('determineRecipientForUpload', [$upload]);

        // Should fall back to admin when no valid recipient data is available
        $this->assertEquals($this->adminUser->id, $recipientId);
    }

    /** @test */
    public function it_correctly_identifies_client_uploads()
    {
        $clientUpload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null
        ]);

        $employeeUpload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->employeeUser->id
        ]);

        $this->assertTrue($this->callPrivateMethod('isClientUpload', [$clientUpload]));
        $this->assertFalse($this->callPrivateMethod('isClientUpload', [$employeeUpload]));
    }

    /** @test */
    public function it_correctly_identifies_employee_admin_uploads()
    {
        $clientUpload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null
        ]);

        $employeeUpload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->employeeUser->id
        ]);

        $this->assertFalse($this->callPrivateMethod('isEmployeeAdminUpload', [$clientUpload]));
        $this->assertTrue($this->callPrivateMethod('isEmployeeAdminUpload', [$employeeUpload]));
    }

    /** @test */
    public function it_returns_correct_upload_type_strings()
    {
        $clientUpload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null
        ]);

        $employeeUpload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->employeeUser->id
        ]);

        $unknownUpload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => null
        ]);

        $this->assertEquals('client_upload', $this->callPrivateMethod('getUploadType', [$clientUpload]));
        $this->assertEquals('employee_admin_upload', $this->callPrivateMethod('getUploadType', [$employeeUpload]));
        $this->assertEquals('unknown_upload_type', $this->callPrivateMethod('getUploadType', [$unknownUpload]));
    }

    /** @test */
    public function it_gets_primary_recipient_for_client_upload()
    {
        // Test with specific company user selected
        $uploadWithSpecificRecipient = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null
        ]);

        $recipientId = $this->callPrivateMethod('getPrimaryRecipientForClientUpload', [$uploadWithSpecificRecipient]);
        $this->assertEquals($this->employeeUser->id, $recipientId);

        // Test without specific company user (should find primary)
        $uploadWithoutSpecificRecipient = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => null,
            'uploaded_by_user_id' => null
        ]);

        $recipientId = $this->callPrivateMethod('getPrimaryRecipientForClientUpload', [$uploadWithoutSpecificRecipient]);
        $this->assertEquals($this->employeeUser->id, $recipientId);
    }

    /** @test */
    public function it_gets_primary_recipient_for_employee_admin_upload()
    {
        $employeeUpload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->employeeUser->id
        ]);

        $recipientId = $this->callPrivateMethod('getPrimaryRecipientForEmployeeAdminUpload', [$employeeUpload]);
        $this->assertEquals($this->employeeUser->id, $recipientId);
    }

    /**
     * Helper method to call private methods for testing
     */
    private function callPrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($this->listener);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->listener, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}