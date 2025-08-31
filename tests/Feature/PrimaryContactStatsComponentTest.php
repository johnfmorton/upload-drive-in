<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrimaryContactStatsComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function component_renders_with_zero_primary_contacts()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $view = $this->blade('<x-dashboard.primary-contact-stats :user="$user" :is-admin="true" />', [
            'user' => $admin
        ]);

        $view->assertSee('Primary Contact For');
        $view->assertSee('0');
        $view->assertSee('Clients');
        $view->assertSee('No primary contact assignments yet');
    }

    /** @test */
    public function component_renders_with_primary_contacts()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $admin->id,
            'is_primary' => true,
        ]);

        $view = $this->blade('<x-dashboard.primary-contact-stats :user="$user" :is-admin="true" />', [
            'user' => $admin
        ]);

        $view->assertSee('Primary Contact For');
        $view->assertSee('1');
        $view->assertSee('Client');
        $view->assertSee('View primary contact clients');
    }

    /** @test */
    public function component_handles_singular_vs_plural()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $clients = User::factory()->count(2)->create(['role' => UserRole::CLIENT]);

        foreach ($clients as $client) {
            ClientUserRelationship::create([
                'client_user_id' => $client->id,
                'company_user_id' => $admin->id,
                'is_primary' => true,
            ]);
        }

        $view = $this->blade('<x-dashboard.primary-contact-stats :user="$user" :is-admin="true" />', [
            'user' => $admin
        ]);

        $view->assertSee('Primary Contact For');
        $view->assertSee('2');
        $view->assertSee('Clients'); // Plural form
    }
}