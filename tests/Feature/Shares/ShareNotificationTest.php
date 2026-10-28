<?php

namespace Tests\Feature\Shares;

use App\Events\ShareFirstDownloaded;
use App\Events\ShareReady;
use App\Listeners\SendShareDownloadedNotification;
use App\Listeners\SendShareLinkToRecipients;
use App\Mail\ShareDownloadedMail;
use App\Mail\ShareLinkMail;
use App\Models\Share;
use App\Models\ShareFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ShareNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ';

    public function test_ready_share_is_emailed_to_each_recipient_separately()
    {
        Mail::fake();

        $share = Share::factory()->ready(self::TOKEN)->create([
            'recipient_emails' => ['a@example.com', 'b@example.com'],
        ]);

        (new SendShareLinkToRecipients)->handle(new ShareReady($share));

        Mail::assertSent(ShareLinkMail::class, 2);
        Mail::assertSent(ShareLinkMail::class, fn ($mail) => $mail->hasTo('a@example.com') && ! $mail->hasTo('b@example.com'));
    }

    public function test_no_email_without_recipients_or_once_the_share_has_ended()
    {
        Mail::fake();

        $noRecipients = Share::factory()->ready('t1')->create();
        $revoked = Share::factory()->create([
            'status' => Share::STATUS_REVOKED,
            'recipient_emails' => ['a@example.com'],
        ]);

        (new SendShareLinkToRecipients)->handle(new ShareReady($noRecipients));
        (new SendShareLinkToRecipients)->handle(new ShareReady($revoked));

        Mail::assertNothingSent();
    }

    public function test_link_email_contains_the_link_and_password_note()
    {
        $sender = User::factory()->employee()->create(['name' => 'Dana Sender']);
        $share = Share::factory()->ready(self::TOKEN)->create([
            'user_id' => $sender->id,
            'title' => 'Site plans',
            'password' => 'secret-pass',
        ]);
        ShareFile::factory()->uploaded()->create(['share_id' => $share->id, 'file_size' => 2048]);

        $mail = new ShareLinkMail($share);

        $mail->assertHasSubject(__('messages.shares_email_link_subject', ['name' => 'Dana Sender', 'title' => 'Site plans']));
        $mail->assertSeeInHtml(url('/s/'.self::TOKEN));
        $mail->assertSeeInHtml(__('messages.shares_email_link_password'));
    }

    public function test_sender_is_emailed_on_first_download_unless_opted_out()
    {
        Mail::fake();

        $sender = User::factory()->employee()->create();
        $share = Share::factory()->ready()->create(['user_id' => $sender->id]);
        $file = ShareFile::factory()->uploaded()->create(['share_id' => $share->id]);

        (new SendShareDownloadedNotification)->handle(new ShareFirstDownloaded($share, $file));
        Mail::assertSent(ShareDownloadedMail::class, fn ($mail) => $mail->hasTo($sender->email));

        $sender->update(['receive_share_download_notifications' => false]);
        Mail::fake();

        (new SendShareDownloadedNotification)->handle(new ShareFirstDownloaded($share->fresh(), $file));
        Mail::assertNothingSent();
    }

    public function test_downloaded_email_links_to_the_senders_panel_and_can_unsubscribe()
    {
        $employee = User::factory()->employee()->create();
        $share = Share::factory()->ready()->create(['user_id' => $employee->id]);
        $file = ShareFile::factory()->uploaded()->create(['share_id' => $share->id, 'original_filename' => 'plans.pdf']);

        $mail = new ShareDownloadedMail($share, $file);

        $mail->assertSeeInHtml('plans.pdf');
        $mail->assertSeeInHtml("/employee/{$employee->username}/shares/{$share->ulid}");
        $mail->assertSeeInHtml('notifications/share-downloads/unsubscribe');
    }

    public function test_unsubscribe_link_turns_the_emails_off()
    {
        $user = User::factory()->employee()->create();

        $url = URL::temporarySignedRoute('notifications.share-downloads.unsubscribe', now()->addDay(), ['user' => $user->id]);

        $this->get($url)->assertOk()->assertSee(__('messages.shares_unsubscribe_success'));
        $this->assertFalse($user->fresh()->receive_share_download_notifications);

        // Unsigned links don't work
        $user->update(['receive_share_download_notifications' => true]);
        $this->get(route('notifications.share-downloads.unsubscribe', ['user' => $user->id]))->assertForbidden();
        $this->assertTrue($user->fresh()->receive_share_download_notifications);
    }

    public function test_employee_can_turn_the_emails_off_in_their_profile()
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)
            ->patch(route('employee.profile.update', ['username' => $employee->username]), [
                'name' => $employee->name,
                'email' => $employee->email,
                'receive_share_download_notifications' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse($employee->fresh()->receive_share_download_notifications);
    }
}
