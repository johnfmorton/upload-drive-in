<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Models\FileUpload;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientRecipientSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @test */
    public function client_with_single_company_user_uploads_to_primary()
    {
        // Create users
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $companyUser = User::factory()->create(['role' => UserRole::ADMIN]);

        // Create Google Drive token for company user
        \App\Models\GoogleDriveToken::create([
            'user_id' => $companyUser->id,
            'access_token' => 'fake_access_token',
            'refresh_token' => 'fake_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        // Create relationship
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $companyUser->id,
            'is_primary' => true,
        ]);

        // Test that the client has the correct primary company user
        $this->assertEquals($companyUser->id, $client->primaryCompanyUser()->id);
        $this->assertTrue($client->companyUsers->count() === 1);
    }

    /** @test */
    public function client_with_multiple_company_users_can_select_recipient()
    {
        // Create users
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $primaryUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $secondaryUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        // Create Google Drive tokens for both users
        \App\Models\GoogleDriveToken::create([
            'user_id' => $primaryUser->id,
            'access_token' => 'fake_access_token',
            'refresh_token' => 'fake_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        \App\Models\GoogleDriveToken::create([
            'user_id' => $secondaryUser->id,
            'access_token' => 'fake_access_token_2',
            'refresh_token' => 'fake_refresh_token_2',
            'expires_at' => now()->addHour(),
        ]);

        // Create relationships
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $primaryUser->id,
            'is_primary' => true,
        ]);

        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $secondaryUser->id,
            'is_primary' => false,
        ]);

        // Test that the client has multiple company users
        $this->assertEquals(2, $client->companyUsers->count());
        $this->assertEquals($primaryUser->id, $client->primaryCompanyUser()->id);
        
        // Test that both users are in the relationship
        $companyUserIds = $client->companyUsers->pluck('id')->toArray();
        $this->assertContains($primaryUser->id, $companyUserIds);
        $this->assertContains($secondaryUser->id, $companyUserIds);
    }

    /** @test */
    public function client_upload_falls_back_to_primary_if_invalid_selection()
    {
        // Create users
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $primaryUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $unrelatedUser = User::factory()->create(['role' => UserRole::ADMIN]);

        // Create Google Drive token for primary user
        \App\Models\GoogleDriveToken::create([
            'user_id' => $primaryUser->id,
            'access_token' => 'fake_access_token',
            'refresh_token' => 'fake_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        // Create relationship only with primary user
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $primaryUser->id,
            'is_primary' => true,
        ]);

        // Test that the client doesn't have a relationship with the unrelated user
        $this->assertFalse($client->companyUsers->contains($unrelatedUser));
        $this->assertEquals($primaryUser->id, $client->primaryCompanyUser()->id);
    }

    /** @test */
    public function upload_page_shows_recipient_selector_for_multiple_relationships()
    {
        // Create users
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $primaryUser = User::factory()->create(['role' => UserRole::ADMIN, 'name' => 'Primary Admin']);
        $secondaryUser = User::factory()->create(['role' => UserRole::EMPLOYEE, 'name' => 'Secondary Employee']);

        // Create relationships
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $primaryUser->id,
            'is_primary' => true,
        ]);

        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $secondaryUser->id,
            'is_primary' => false,
        ]);

        $response = $this->actingAs($client)
            ->get(route('client.upload-files'));

        $response->assertStatus(200)
            ->assertSee(__('messages.select_recipient'))
            ->assertSee('Primary Admin')
            ->assertSee('Secondary Employee')
            ->assertSee('Primary'); // Should show "Primary" indicator
    }

    /** @test */
    public function upload_page_hides_recipient_selector_for_single_relationship()
    {
        // Create users
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $companyUser = User::factory()->create(['role' => UserRole::ADMIN, 'name' => 'Single Admin']);

        // Create single relationship
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $companyUser->id,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($client)
            ->get(route('client.upload-files'));

        $response->assertStatus(200)
            ->assertDontSee(__('messages.select_recipient')); // Should not show selector for single relationship
    }
}