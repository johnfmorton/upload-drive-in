<?php

namespace Tests\Unit\Controllers\Employee;

use Tests\TestCase;
use App\Http\Controllers\Employee\DashboardController;
use App\Models\User;
use App\Models\FileUpload;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private DashboardController $controller;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new DashboardController();
        $this->employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
    }

    /** @test */
    public function get_storage_provider_context_returns_correct_config_for_google_drive()
    {
        // Set Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
        ]);

        $this->actingAs($this->employee);
        
        $response = $this->controller->index(request());
        
        $this->assertInstanceOf(View::class, $response);
        
        $storageProvider = $response->getData()['storageProvider'];
        
        $this->assertEquals('google-drive', $storageProvider['provider']);
        $this->assertEquals('oauth', $storageProvider['auth_type']);
        $this->assertEquals('hierarchical', $storageProvider['storage_model']);
        $this->assertTrue($storageProvider['requires_user_auth']);
        $this->assertEquals('Google Drive', $storageProvider['display_name']);
        $this->assertArrayNotHasKey('error', $storageProvider);
    }

    /** @test */
    public function get_storage_provider_context_returns_correct_config_for_amazon_s3()
    {
        // Set Amazon S3 as the default provider
        Config::set('cloud-storage.default', 'amazon-s3');
        Config::set('cloud-storage.providers.amazon-s3', [
            'auth_type' => 'api_key',
            'storage_model' => 'flat',
        ]);

        $this->actingAs($this->employee);
        
        $response = $this->controller->index(request());
        
        $this->assertInstanceOf(View::class, $response);
        
        $storageProvider = $response->getData()['storageProvider'];
        
        $this->assertEquals('amazon-s3', $storageProvider['provider']);
        $this->assertEquals('api_key', $storageProvider['auth_type']);
        $this->assertEquals('flat', $storageProvider['storage_model']);
        $this->assertFalse($storageProvider['requires_user_auth']);
        $this->assertEquals('Amazon S3', $storageProvider['display_name']);
        $this->assertArrayNotHasKey('error', $storageProvider);
    }

    /** @test */
    public function get_storage_provider_context_handles_missing_default_configuration()
    {
        // Set no default provider
        Config::set('cloud-storage.default', null);

        Log::shouldReceive('warning')
            ->once()
            ->with('No default cloud storage provider configured');

        $this->actingAs($this->employee);
        
        $response = $this->controller->index(request());
        
        $this->assertInstanceOf(View::class, $response);
        
        $storageProvider = $response->getData()['storageProvider'];
        
        $this->assertEquals('unknown', $storageProvider['provider']);
        $this->assertEquals('oauth', $storageProvider['auth_type']);
        $this->assertEquals('hierarchical', $storageProvider['storage_model']);
        $this->assertTrue($storageProvider['requires_user_auth']);
        $this->assertEquals('Cloud Storage', $storageProvider['display_name']);
        $this->assertTrue($storageProvider['error']);
    }

    /** @test */
    public function get_storage_provider_context_handles_missing_provider_configuration()
    {
        // Set a default provider that doesn't exist in the config
        Config::set('cloud-storage.default', 'non-existent-provider');
        Config::set('cloud-storage.providers.non-existent-provider', null);

        Log::shouldReceive('error')
            ->once()
            ->with('Provider configuration not found: non-existent-provider');

        $this->actingAs($this->employee);
        
        $response = $this->controller->index(request());
        
        $this->assertInstanceOf(View::class, $response);
        
        $storageProvider = $response->getData()['storageProvider'];
        
        $this->assertEquals('unknown', $storageProvider['provider']);
        $this->assertEquals('oauth', $storageProvider['auth_type']);
        $this->assertEquals('hierarchical', $storageProvider['storage_model']);
        $this->assertTrue($storageProvider['requires_user_auth']);
        $this->assertEquals('Cloud Storage', $storageProvider['display_name']);
        $this->assertTrue($storageProvider['error']);
    }

    /** @test */
    public function get_provider_display_name_returns_correct_names()
    {
        // Test Google Drive
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
        ]);

        $this->actingAs($this->employee);
        $response = $this->controller->index(request());
        $storageProvider = $response->getData()['storageProvider'];
        $this->assertEquals('Google Drive', $storageProvider['display_name']);

        // Test Amazon S3
        Config::set('cloud-storage.default', 'amazon-s3');
        Config::set('cloud-storage.providers.amazon-s3', [
            'auth_type' => 'api_key',
            'storage_model' => 'flat',
        ]);

        $response = $this->controller->index(request());
        $storageProvider = $response->getData()['storageProvider'];
        $this->assertEquals('Amazon S3', $storageProvider['display_name']);

        // Test Microsoft Teams
        Config::set('cloud-storage.default', 'microsoft-teams');
        Config::set('cloud-storage.providers.microsoft-teams', [
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
        ]);

        $response = $this->controller->index(request());
        $storageProvider = $response->getData()['storageProvider'];
        $this->assertEquals('Microsoft Teams', $storageProvider['display_name']);

        // Test unknown provider (should convert kebab-case to Title Case)
        Config::set('cloud-storage.default', 'custom-provider');
        Config::set('cloud-storage.providers.custom-provider', [
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
        ]);

        $response = $this->controller->index(request());
        $storageProvider = $response->getData()['storageProvider'];
        $this->assertEquals('Custom Provider', $storageProvider['display_name']);
    }

    /** @test */
    public function index_passes_storage_provider_context_to_view()
    {
        // Set Google Drive as the default provider
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
        ]);

        $this->actingAs($this->employee);
        
        $response = $this->controller->index(request());
        
        $this->assertInstanceOf(View::class, $response);
        
        // Verify all expected data is passed to the view
        $viewData = $response->getData();
        
        $this->assertArrayHasKey('user', $viewData);
        $this->assertArrayHasKey('files', $viewData);
        $this->assertArrayHasKey('storageProvider', $viewData);
        
        $this->assertInstanceOf(User::class, $viewData['user']);
        $this->assertEquals($this->employee->id, $viewData['user']->id);
        
        $this->assertIsArray($viewData['storageProvider']);
        $this->assertArrayHasKey('provider', $viewData['storageProvider']);
        $this->assertArrayHasKey('auth_type', $viewData['storageProvider']);
        $this->assertArrayHasKey('storage_model', $viewData['storageProvider']);
        $this->assertArrayHasKey('requires_user_auth', $viewData['storageProvider']);
        $this->assertArrayHasKey('display_name', $viewData['storageProvider']);
    }

    /** @test */
    public function index_returns_files_for_employee()
    {
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
        ]);

        $this->actingAs($this->employee);
        
        // Create files for this employee
        $file1 = FileUpload::factory()->create([
            'company_user_id' => $this->employee->id,
        ]);
        
        $file2 = FileUpload::factory()->create([
            'uploaded_by_user_id' => $this->employee->id,
        ]);
        
        // Create a file for another user (should not be included)
        $otherUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        FileUpload::factory()->create([
            'company_user_id' => $otherUser->id,
            'uploaded_by_user_id' => $otherUser->id,
        ]);
        
        $response = $this->controller->index(request());
        
        $this->assertInstanceOf(View::class, $response);
        
        $files = $response->getData()['files'];
        
        // Should have 2 files (file1 and file2)
        $this->assertCount(2, $files);
    }

    /** @test */
    public function get_storage_provider_context_defaults_auth_type_to_oauth_when_missing()
    {
        Config::set('cloud-storage.default', 'test-provider');
        Config::set('cloud-storage.providers.test-provider', [
            // auth_type is missing
            'storage_model' => 'hierarchical',
        ]);

        $this->actingAs($this->employee);
        
        $response = $this->controller->index(request());
        
        $storageProvider = $response->getData()['storageProvider'];
        
        $this->assertEquals('oauth', $storageProvider['auth_type']);
        $this->assertTrue($storageProvider['requires_user_auth']);
    }

    /** @test */
    public function get_storage_provider_context_defaults_storage_model_to_hierarchical_when_missing()
    {
        Config::set('cloud-storage.default', 'test-provider');
        Config::set('cloud-storage.providers.test-provider', [
            'auth_type' => 'oauth',
            // storage_model is missing
        ]);

        $this->actingAs($this->employee);
        
        $response = $this->controller->index(request());
        
        $storageProvider = $response->getData()['storageProvider'];
        
        $this->assertEquals('hierarchical', $storageProvider['storage_model']);
    }

    /** @test */
    public function requires_user_auth_is_false_for_api_key_auth_type()
    {
        Config::set('cloud-storage.default', 'amazon-s3');
        Config::set('cloud-storage.providers.amazon-s3', [
            'auth_type' => 'api_key',
            'storage_model' => 'flat',
        ]);

        $this->actingAs($this->employee);
        
        $response = $this->controller->index(request());
        
        $storageProvider = $response->getData()['storageProvider'];
        
        $this->assertFalse($storageProvider['requires_user_auth']);
    }

    /** @test */
    public function requires_user_auth_is_true_for_oauth_auth_type()
    {
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.providers.google-drive', [
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
        ]);

        $this->actingAs($this->employee);
        
        $response = $this->controller->index(request());
        
        $storageProvider = $response->getData()['storageProvider'];
        
        $this->assertTrue($storageProvider['requires_user_auth']);
    }
}
