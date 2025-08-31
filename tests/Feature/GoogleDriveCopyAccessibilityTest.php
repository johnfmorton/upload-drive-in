<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Models\CloudStorageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleDriveCopyAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_drive_connection_component_has_accessibility_attributes()
    {
        // Create an admin user with Google Drive token
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        GoogleDriveToken::create([
            'user_id' => $user->id,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_at' => now()->addHour(),
            'token_type' => 'Bearer',
            'scopes' => ['https://www.googleapis.com/auth/drive.file']
        ]);

        // Configure Google Drive app settings
        CloudStorageSetting::create([
            'provider' => 'google-drive',
            'key' => 'client_id',
            'value' => 'test_client_id',
            'user_id' => null
        ]);

        CloudStorageSetting::create([
            'provider' => 'google-drive',
            'key' => 'client_secret',
            'value' => 'test_client_secret',
            'user_id' => null
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);

        // Check for accessibility attributes on the copy button
        $response->assertSee('role="button"', false);
        $response->assertSee('tabindex="0"', false);
        $response->assertSee('aria-label', false);
        $response->assertSee('aria-pressed', false);

        // Check for screen reader announcement area
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('aria-atomic="true"', false);
        $response->assertSee('class="sr-only"', false);

        // Check for proper ARIA attributes on the URL display
        $response->assertSee('role="textbox"', false);
        $response->assertSee('aria-readonly="true"', false);
        $response->assertSee('aria-label', false);

        // Check for proper span elements with aria-hidden
        $response->assertSee('aria-hidden="true"', false);
    }

    public function test_accessibility_translation_keys_exist()
    {
        // Test that all required translation keys exist
        $this->assertEquals('Upload URL for sharing with clients', __('messages.upload_url_label'));
        $this->assertEquals('Copy URL to clipboard', __('messages.copy_url_to_clipboard'));
        $this->assertEquals('URL copied to clipboard', __('messages.url_copied_to_clipboard'));
        $this->assertEquals('Failed to copy URL', __('messages.copy_failed'));
        $this->assertEquals('Copied!', __('messages.copied'));
        $this->assertEquals('Copy URL', __('messages.copy_url'));
    }
}