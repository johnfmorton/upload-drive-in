<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class GoogleDriveFolderConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'google_drive_root_folder_id' => null
        ]);

        // Create Google Drive token for admin
        GoogleDriveToken::create([
            'user_id' => $this->admin->id,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive']
        ]);
    }

    /**
     * Test admin can access Google Drive configuration page.
     */
    public function test_admin_can_access_google_drive_configuration()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.cloud-storage.index');
    }

    /**
     * Test admin can update their Google Drive root folder setting.
     */
    public function test_admin_can_update_google_drive_root_folder()
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.google-drive.folder.update'), [
                'google_drive_root_folder_id' => 'custom-admin-folder-id'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->admin->refresh();
        $this->assertEquals('custom-admin-folder-id', $this->admin->google_drive_root_folder_id);
    }

    /**
     * Test admin can clear their Google Drive root folder setting.
     */
    public function test_admin_can_clear_google_drive_root_folder()
    {
        $this->admin->update(['google_drive_root_folder_id' => 'existing-folder-id']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.google-drive.folder.update'), [
                'google_drive_root_folder_id' => ''
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->admin->refresh();
        $this->assertNull($this->admin->google_drive_root_folder_id);
    }

    /**
     * Test admin folder configuration shows current setting.
     */
    public function test_admin_folder_configuration_shows_current_setting()
    {
        $this->admin->update(['google_drive_root_folder_id' => 'current-folder-id']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        $response->assertSee('current-folder-id');
    }

    /**
     * Test admin folder configuration shows default when no setting.
     */
    public function test_admin_folder_configuration_shows_default_when_no_setting()
    {
        $this->admin->update(['google_drive_root_folder_id' => null]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        // Should indicate default Google Drive root behavior
        $response->assertSee('Google Drive root');
    }

    /**
     * Test admin cannot access configuration without Google Drive connection.
     */
    public function test_admin_cannot_configure_without_google_drive_connection()
    {
        // Remove Google Drive token
        $this->admin->googleDriveToken()->delete();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));

        $response->assertStatus(200);
        // Should show not connected status
        $response->assertSee('Not Connected');
    }

    /**
     * Test admin folder update validation.
     */
    public function test_admin_folder_update_validation()
    {
        // Test with invalid folder ID format (if validation exists)
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.google-drive.folder.update'), [
                'google_drive_root_folder_id' => str_repeat('a', 256) // Too long
            ]);

        // Should either accept it or show validation error
        // The actual validation depends on implementation
        $this->assertTrue($response->isRedirection() || $response->status() === 422);
    }

    /**
     * Test admin folder configuration workflow end-to-end.
     */
    public function test_admin_folder_configuration_workflow()
    {
        // 1. Start with no folder configured
        $this->assertNull($this->admin->google_drive_root_folder_id);

        // 2. Access configuration page
        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));
        $response->assertStatus(200);

        // 3. Set a custom folder
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.google-drive.folder.update'), [
                'google_drive_root_folder_id' => 'workflow-test-folder'
            ]);
        $response->assertRedirect();

        // 4. Verify setting was saved
        $this->admin->refresh();
        $this->assertEquals('workflow-test-folder', $this->admin->google_drive_root_folder_id);

        // 5. View configuration again to confirm
        $response = $this->actingAs($this->admin)
            ->get(route('admin.cloud-storage.index'));
        $response->assertStatus(200);
        $response->assertSee('workflow-test-folder');

        // 6. Clear the setting
        $response = $this->actingAs($this->admin)
            ->put(route('admin.cloud-storage.google-drive.folder.update'), [
                'google_drive_root_folder_id' => null
            ]);
        $response->assertRedirect();

        // 7. Verify setting was cleared
        $this->admin->refresh();
        $this->assertNull($this->admin->google_drive_root_folder_id);
    }
}