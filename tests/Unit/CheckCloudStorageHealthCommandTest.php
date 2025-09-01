<?php

namespace Tests\Unit;

use App\Console\Commands\CheckCloudStorageHealth;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckCloudStorageHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake notifications to prevent actual sending during tests
        Notification::fake();
    }

    public function test_command_runs_successfully_with_no_users(): void
    {
        $this->artisan('cloud-storage:check-health')
            ->expectsOutput('Starting cloud storage health check...')
            ->expectsOutput('No users found with cloud storage connections.')
            ->assertExitCode(0);
    }

    public function test_command_checks_health_for_users_with_tokens(): void
    {
        // Create a user with a Google Drive token
        $user = User::factory()->create(['email' => 'test@example.com']);
        GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        $this->artisan('cloud-storage:check-health')
            ->expectsOutput('Starting cloud storage health check...')
            ->expectsOutputToContain('Checking health for 1 users')
            ->expectsOutputToContain('Checking google-drive for user test@example.com...')
            ->assertExitCode(0);
    }

    public function test_command_can_check_specific_user(): void
    {
        // Create users with tokens
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        GoogleDriveToken::factory()->create(['user_id' => $user1->id]);
        GoogleDriveToken::factory()->create(['user_id' => $user2->id]);

        $this->artisan('cloud-storage:check-health', ['--user' => $user1->id])
            ->expectsOutputToContain('user1@example.com')
            ->doesntExpectOutputToContain('user2@example.com')
            ->assertExitCode(0);
    }

    public function test_command_can_check_specific_provider(): void
    {
        $user = User::factory()->create();
        GoogleDriveToken::factory()->create(['user_id' => $user->id]);

        $this->artisan('cloud-storage:check-health', ['--provider' => ['google-drive']])
            ->expectsOutputToContain('google-drive')
            ->assertExitCode(0);
    }

    public function test_command_displays_health_summary(): void
    {
        $user = User::factory()->create();
        GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        $this->artisan('cloud-storage:check-health')
            ->expectsOutput('Health Check Summary:')
            ->expectsOutputToContain('Status')
            ->expectsOutputToContain('Count')
            ->assertExitCode(0);
    }

    public function test_command_warns_about_expiring_tokens(): void
    {
        $user = User::factory()->create(['email' => 'expiring@example.com']);
        GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->addHours(2), // Expires soon
        ]);

        $this->artisan('cloud-storage:check-health')
            ->expectsOutputToContain('⏰ Token expires soon')
            ->assertExitCode(0);
    }

    public function test_command_with_notify_flag_sends_notifications(): void
    {
        $user = User::factory()->create();
        GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->addHours(2), // Expires soon
        ]);

        $this->artisan('cloud-storage:check-health', ['--notify'])
            ->expectsOutput('Sending notifications for issues found...')
            ->assertExitCode(0);
    }
}