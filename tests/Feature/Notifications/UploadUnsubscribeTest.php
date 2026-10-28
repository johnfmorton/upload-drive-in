<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UploadUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_link_turns_off_upload_emails_and_confirms_without_signing_in()
    {
        $user = User::factory()->create(['receive_upload_notifications' => true]);

        $url = URL::temporarySignedRoute('notifications.upload.unsubscribe', now()->addDay(), ['user' => $user->id]);

        $this->get($url)
            ->assertOk()
            ->assertSee(__('messages.unsubscribe_success_message'));

        $this->assertFalse($user->fresh()->receive_upload_notifications);
    }

    public function test_unsigned_link_is_refused()
    {
        $user = User::factory()->create(['receive_upload_notifications' => true]);

        $this->get(route('notifications.upload.unsubscribe', ['user' => $user->id]))->assertForbidden();

        $this->assertTrue($user->fresh()->receive_upload_notifications);
    }
}
