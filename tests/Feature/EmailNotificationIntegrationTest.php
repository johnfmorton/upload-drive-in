<?php

namespace Tests\Feature;

use App\Events\BatchUploadComplete;
use App\Listeners\SendBatchUploadNotifications;
use App\Mail\AdminBatchUploadNotification;
use App\Mail\ClientBatchUploadConfirmation;
use App\Models\ClientUserRelationship;
use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailNotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;
    private User $clientUser;
    private User $secondEmployeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@company.com',
            'name' => 'Admin User',
            'receive_upload_notifications' => true
        ]);
        
        $this->employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@company.com',
            'name' => 'Employee User',
            'receive_upload_notifications' => true
        ]);

        $this->secondEmployeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee2@company.com',
            'name' => 'Second Employee',
            'receive_upload_notifications' => true
        ]);
        
        $this->clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
            'name' => 'Client User',
            'receive_upload_notifications' => true
        ]);

        // Create client-company relationship
        ClientUserRelationship::create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'is_primary' => true
        ]);

        Mail::fake();
        Queue::fake();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_email_to_specific_recipient_for_client_upload()
    {
        // Create a client upload with specific employee selected as recipient
        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'test-document.pdf',
            'original_filename' => 'Test Document.pdf'
        ]);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete([$upload->id], $this->clientUser->id);
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert admin notification was sent to the specific employee
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->employeeUser->email) &&
                   $mail->fileUploads->count() === 1 &&
                   $mail->user->id === $this->clientUser->id;
        });

        // Assert client confirmation was sent
        Mail::assertSent(ClientBatchUploadConfirmation::class, function ($mail) {
            return $mail->hasTo($this->clientUser->email) &&
                   $mail->fileUploads->count() === 1;
        });

        // Assert no email was sent to admin or other employees
        Mail::assertNotSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->adminUser->email);
        });

        Mail::assertNotSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->secondEmployeeUser->email);
        });

        // Assert exactly 2 emails were sent (1 admin notification + 1 client confirmation)
        Mail::assertSentCount(2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_email_to_primary_company_user_when_no_specific_recipient_selected()
    {
        // Create a client upload without specific recipient selected
        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => null, // No specific recipient
            'uploaded_by_user_id' => null,
            'filename' => 'test-document.pdf',
            'original_filename' => 'Test Document.pdf'
        ]);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete([$upload->id], $this->clientUser->id);
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert admin notification was sent to the primary company user (employee)
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->employeeUser->email) &&
                   $mail->fileUploads->count() === 1 &&
                   $mail->user->id === $this->clientUser->id;
        });

        // Assert client confirmation was sent
        Mail::assertSent(ClientBatchUploadConfirmation::class, function ($mail) {
            return $mail->hasTo($this->clientUser->email);
        });

        Mail::assertSentCount(2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_email_to_uploader_for_employee_upload()
    {
        // Create an employee upload (employee uploading on behalf of client)
        $upload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->employeeUser->id,
            'filename' => 'employee-upload.pdf',
            'original_filename' => 'Employee Upload.pdf'
        ]);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete([$upload->id], $this->employeeUser->id);
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert admin notification was sent to the uploader (employee)
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->employeeUser->email) &&
                   $mail->fileUploads->count() === 1 &&
                   $mail->user->id === $this->employeeUser->id;
        });

        // Assert client confirmation was sent to the uploader (employee)
        Mail::assertSent(ClientBatchUploadConfirmation::class, function ($mail) {
            return $mail->hasTo($this->employeeUser->email);
        });

        Mail::assertSentCount(2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_email_to_uploader_for_admin_upload()
    {
        // Create an admin upload (admin uploading on behalf of client)
        $upload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->adminUser->id,
            'filename' => 'admin-upload.pdf',
            'original_filename' => 'Admin Upload.pdf'
        ]);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete([$upload->id], $this->adminUser->id);
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert admin notification was sent to the uploader (admin)
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->adminUser->email) &&
                   $mail->fileUploads->count() === 1 &&
                   $mail->user->id === $this->adminUser->id;
        });

        // Assert client confirmation was sent to the uploader (admin)
        Mail::assertSent(ClientBatchUploadConfirmation::class, function ($mail) {
            return $mail->hasTo($this->adminUser->email);
        });

        Mail::assertSentCount(2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_client_confirmation_regardless_of_recipient()
    {
        // Create uploads to different recipients
        $uploadToEmployee = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'doc1.pdf',
            'original_filename' => 'Document 1.pdf'
        ]);

        $uploadToAdmin = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->adminUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'doc2.pdf',
            'original_filename' => 'Document 2.pdf'
        ]);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete(
            [$uploadToEmployee->id, $uploadToAdmin->id], 
            $this->clientUser->id
        );
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert client confirmation was sent with all files
        Mail::assertSent(ClientBatchUploadConfirmation::class, function ($mail) {
            return $mail->hasTo($this->clientUser->email) &&
                   $mail->fileUploads->count() === 2;
        });

        // Assert admin notifications were sent to both recipients
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->employeeUser->email) &&
                   $mail->fileUploads->count() === 1;
        });

        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->adminUser->email) &&
                   $mail->fileUploads->count() === 1;
        });

        // Total: 1 client confirmation + 2 admin notifications = 3 emails
        Mail::assertSentCount(3);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_batch_uploads_with_multiple_recipients()
    {
        // Create uploads to different recipients in a single batch
        $uploadToEmployee = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'employee-doc.pdf',
            'original_filename' => 'Employee Document.pdf'
        ]);

        $uploadToSecondEmployee = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->secondEmployeeUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'second-employee-doc.pdf',
            'original_filename' => 'Second Employee Document.pdf'
        ]);

        $uploadToAdmin = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->adminUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'admin-doc.pdf',
            'original_filename' => 'Admin Document.pdf'
        ]);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete(
            [$uploadToEmployee->id, $uploadToSecondEmployee->id, $uploadToAdmin->id], 
            $this->clientUser->id
        );
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert each recipient gets only their files
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) use ($uploadToEmployee) {
            return $mail->hasTo($this->employeeUser->email) &&
                   $mail->fileUploads->count() === 1 &&
                   $mail->fileUploads->first()->id === $uploadToEmployee->id;
        });

        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) use ($uploadToSecondEmployee) {
            return $mail->hasTo($this->secondEmployeeUser->email) &&
                   $mail->fileUploads->count() === 1 &&
                   $mail->fileUploads->first()->id === $uploadToSecondEmployee->id;
        });

        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) use ($uploadToAdmin) {
            return $mail->hasTo($this->adminUser->email) &&
                   $mail->fileUploads->count() === 1 &&
                   $mail->fileUploads->first()->id === $uploadToAdmin->id;
        });

        // Assert client confirmation includes all files
        Mail::assertSent(ClientBatchUploadConfirmation::class, function ($mail) {
            return $mail->hasTo($this->clientUser->email) &&
                   $mail->fileUploads->count() === 3;
        });

        // Total: 1 client confirmation + 3 admin notifications = 4 emails
        Mail::assertSentCount(4);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_mixed_upload_types_in_batch()
    {
        // Create a mix of client and employee uploads
        $clientUpload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'client-doc.pdf',
            'original_filename' => 'Client Document.pdf'
        ]);

        $employeeUpload = FileUpload::factory()->create([
            'client_user_id' => null,
            'company_user_id' => null,
            'uploaded_by_user_id' => $this->secondEmployeeUser->id,
            'filename' => 'employee-doc.pdf',
            'original_filename' => 'Employee Document.pdf'
        ]);

        // Dispatch batch upload event for client user (who initiated the batch)
        $event = new BatchUploadComplete(
            [$clientUpload->id, $employeeUpload->id], 
            $this->clientUser->id
        );
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert client upload notification goes to selected employee
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) use ($clientUpload) {
            return $mail->hasTo($this->employeeUser->email) &&
                   $mail->fileUploads->count() === 1 &&
                   $mail->fileUploads->first()->id === $clientUpload->id;
        });

        // Assert employee upload notification goes to the uploader
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) use ($employeeUpload) {
            return $mail->hasTo($this->secondEmployeeUser->email) &&
                   $mail->fileUploads->count() === 1 &&
                   $mail->fileUploads->first()->id === $employeeUpload->id;
        });

        // Assert client confirmation is sent to the batch initiator
        Mail::assertSent(ClientBatchUploadConfirmation::class, function ($mail) {
            return $mail->hasTo($this->clientUser->email) &&
                   $mail->fileUploads->count() === 2;
        });

        Mail::assertSentCount(3);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_send_client_confirmation_when_notifications_disabled()
    {
        // Disable notifications for client
        $this->clientUser->update(['receive_upload_notifications' => false]);

        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'test-doc.pdf',
            'original_filename' => 'Test Document.pdf'
        ]);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete([$upload->id], $this->clientUser->id);
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert admin notification was still sent
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->employeeUser->email);
        });

        // Assert client confirmation was NOT sent
        Mail::assertNotSent(ClientBatchUploadConfirmation::class);

        Mail::assertSentCount(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_falls_back_to_admin_when_no_valid_recipient_found()
    {
        // Create a client with no company relationships
        $isolatedClient = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'isolated@example.com',
            'name' => 'Isolated Client',
            'receive_upload_notifications' => true
        ]);

        $upload = FileUpload::factory()->create([
            'client_user_id' => $isolatedClient->id,
            'company_user_id' => null,
            'uploaded_by_user_id' => null,
            'filename' => 'isolated-doc.pdf',
            'original_filename' => 'Isolated Document.pdf'
        ]);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete([$upload->id], $isolatedClient->id);
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert admin notification was sent to admin as fallback
        Mail::assertSent(AdminBatchUploadNotification::class, function ($mail) {
            return $mail->hasTo($this->adminUser->email) &&
                   $mail->fileUploads->count() === 1;
        });

        // Assert client confirmation was sent
        Mail::assertSent(ClientBatchUploadConfirmation::class, function ($mail) use ($isolatedClient) {
            return $mail->hasTo($isolatedClient->email);
        });

        Mail::assertSentCount(2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_continues_processing_when_individual_email_fails()
    {
        // Create uploads to multiple recipients
        $uploadToEmployee = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'doc1.pdf',
            'original_filename' => 'Document 1.pdf'
        ]);

        $uploadToAdmin = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->adminUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'doc2.pdf',
            'original_filename' => 'Document 2.pdf'
        ]);

        // Mock mail to throw exception for employee but succeed for admin
        Mail::shouldReceive('to')
            ->with($this->employeeUser->email)
            ->andThrow(new \Exception('SMTP server error'));

        Mail::shouldReceive('to')
            ->with($this->adminUser->email)
            ->andReturnSelf();

        Mail::shouldReceive('to')
            ->with($this->clientUser->email)
            ->andReturnSelf();

        Mail::shouldReceive('send')
            ->andReturn(true);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete(
            [$uploadToEmployee->id, $uploadToAdmin->id], 
            $this->clientUser->id
        );
        $listener = new SendBatchUploadNotifications();
        
        // Should not throw exception despite individual email failure
        $listener->handle($event);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_recipient_names_in_client_confirmation()
    {
        // Create uploads to multiple recipients
        $uploadToEmployee = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'doc1.pdf',
            'original_filename' => 'Document 1.pdf'
        ]);

        $uploadToAdmin = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->adminUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'doc2.pdf',
            'original_filename' => 'Document 2.pdf'
        ]);

        // Dispatch the batch upload complete event
        $event = new BatchUploadComplete(
            [$uploadToEmployee->id, $uploadToAdmin->id], 
            $this->clientUser->id
        );
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Assert client confirmation includes recipient names
        Mail::assertSent(ClientBatchUploadConfirmation::class, function ($mail) {
            return $mail->hasTo($this->clientUser->email) &&
                   in_array($this->employeeUser->name, $mail->recipientNames) &&
                   in_array($this->adminUser->name, $mail->recipientNames) &&
                   count($mail->recipientNames) === 2;
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_empty_batch_gracefully()
    {
        // Dispatch event with empty file upload IDs
        $event = new BatchUploadComplete([], $this->clientUser->id);
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Should not send any emails
        Mail::assertNothingSent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_invalid_user_id_gracefully()
    {
        $upload = FileUpload::factory()->create([
            'client_user_id' => $this->clientUser->id,
            'company_user_id' => $this->employeeUser->id,
            'uploaded_by_user_id' => null,
            'filename' => 'test-doc.pdf',
            'original_filename' => 'Test Document.pdf'
        ]);

        // Dispatch event with invalid user ID
        $event = new BatchUploadComplete([$upload->id], 99999);
        $listener = new SendBatchUploadNotifications();
        $listener->handle($event);

        // Should not send any emails
        Mail::assertNothingSent();
    }
}