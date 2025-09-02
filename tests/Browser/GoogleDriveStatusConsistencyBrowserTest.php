<?php

namespace Tests\Browser;

use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

class GoogleDriveStatusConsistencyBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    #[Test]
    public function dashboard_loads_without_token_refresh_warnings()
    {
        $this->browse(function (Browser $browser) {
            // Arrange - Create valid token and healthy status
            $futureExpiry = Carbon::now()->addHours(2);
            GoogleDriveToken::factory()->create([
                'user_id' => $this->adminUser->id,
                'access_token' => 'valid_access_token',
                'refresh_token' => 'valid_refresh_token',
                'expires_at' => $futureExpiry,
            ]);

            CloudStorageHealthStatus::create([
                'user_id' => $this->adminUser->id,
                'provider' => 'google-drive',
                'status' => 'healthy',
                'consolidated_status' => 'healthy',
                'last_successful_operation_at' => Carbon::now(),
                'token_refresh_failures' => 0,
                'operational_test_result' => ['status' => 'success'],
            ]);

            // Act - Visit admin dashboard
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/dashboard')
                    ->pause(2000); // Wait for page to load

            // Assert - Should not show confusing token refresh messages
            $browser->assertDontSee('Token refresh needed')
                    ->assertDontSee('Token will refresh soon')
                    ->assertDontSee('Token expires in');
        });
    }

}
}