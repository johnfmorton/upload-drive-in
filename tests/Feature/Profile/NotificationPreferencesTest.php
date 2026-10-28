<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * What the browser submits when both boxes are unchecked: only the hidden zeros.
     */
    private function uncheckedForm(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'receive_upload_notifications' => '0',
            'receive_share_download_notifications' => '0',
        ];
    }

    public function test_forms_send_a_zero_when_a_box_is_unchecked()
    {
        $hidden = '<input type="hidden" name="receive_upload_notifications" value="0">';

        $employee = User::factory()->employee()->create();
        $this->actingAs($employee)
            ->get(route('employee.profile.edit', ['username' => $employee->username]))
            ->assertSee($hidden, false)
            ->assertSee('<input type="hidden" name="receive_share_download_notifications" value="0">', false);

        $client = User::factory()->create();
        $this->actingAs($client)
            ->get(route('client.profile.edit'))
            ->assertSee($hidden, false);
    }

    public function test_employee_can_turn_both_emails_off_and_on()
    {
        $employee = User::factory()->employee()->create();
        $url = route('employee.profile.update', ['username' => $employee->username]);

        $this->actingAs($employee)->patch($url, $this->uncheckedForm($employee))->assertRedirect();

        $employee->refresh();
        $this->assertFalse($employee->receive_upload_notifications);
        $this->assertFalse($employee->receive_share_download_notifications);

        $this->actingAs($employee)->patch($url, [
            'receive_upload_notifications' => '1',
            'receive_share_download_notifications' => '1',
        ] + $this->uncheckedForm($employee))->assertRedirect();

        $employee->refresh();
        $this->assertTrue($employee->receive_upload_notifications);
        $this->assertTrue($employee->receive_share_download_notifications);
    }

    public function test_client_can_turn_upload_emails_off()
    {
        $client = User::factory()->create();

        $this->actingAs($client)
            ->patch(route('client.profile.update'), [
                'name' => $client->name,
                'email' => $client->email,
                'receive_upload_notifications' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse($client->fresh()->receive_upload_notifications);
    }

    public function test_admin_profile_shows_and_saves_both_settings()
    {
        $admin = User::factory()->admin()->create();
        $admin->forceFill(['two_factor_enabled' => true])->save();
        $session = ['two_factor_verified' => now()->timestamp];

        $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('name="receive_upload_notifications"', false)
            ->assertSee('name="receive_share_download_notifications"', false);

        $this->actingAs($admin)
            ->withSession($session)
            ->patch(route('admin.profile.update'), $this->uncheckedForm($admin))
            ->assertRedirect();

        $admin->refresh();
        $this->assertFalse($admin->receive_upload_notifications);
        $this->assertFalse($admin->receive_share_download_notifications);
    }

    public function test_clients_do_not_see_the_share_download_setting()
    {
        $client = User::factory()->create();

        $this->actingAs($client)
            ->get(route('client.profile.edit'))
            ->assertOk()
            ->assertSee('name="receive_upload_notifications"', false)
            ->assertDontSee('name="receive_share_download_notifications"', false);
    }
}
