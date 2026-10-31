<?php

namespace Tests\Feature\Shares;

use App\Models\Share;
use App\Models\ShareAccessLog;
use App\Models\User;
use App\Services\ShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Extending a share's expiry, and the activity log on its detail page.
 */
class ShareExtendAndActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->employee()->create();
    }

    private function employeeRoute(string $name, array $params = []): string
    {
        return route("employee.shares.{$name}", ['username' => $this->employee->username] + $params);
    }

    public function test_extend_adds_days_to_the_current_expiry()
    {
        $this->freezeSecond(); // the database stores whole seconds
        $share = Share::factory()->ready()->create(['user_id' => $this->employee->id, 'expires_at' => now()->addDays(3)]);

        $this->actingAs($this->employee)
            ->post($this->employeeRoute('extend', ['share' => $share]), ['days' => 7])
            ->assertRedirect($this->employeeRoute('show', ['share' => $share]))
            ->assertSessionHas('status');

        $this->assertTrue($share->fresh()->expires_at->equalTo(now()->addDays(10)));
    }

    public function test_extend_counts_from_now_when_the_link_has_run_out_but_not_been_purged()
    {
        $this->freezeSecond(); // the database stores whole seconds
        $share = Share::factory()->ready()->create(['user_id' => $this->employee->id, 'expires_at' => now()->subHour()]);

        $this->assertTrue(app(ShareService::class)->extend($share, 7));

        $share->refresh();
        $this->assertTrue($share->expires_at->equalTo(now()->addDays(7)));
        $this->assertTrue($share->isAvailable());
    }

    public function test_extend_refuses_an_ended_share()
    {
        $share = Share::factory()->ready()->create(['user_id' => $this->employee->id, 'status' => Share::STATUS_EXPIRED]);
        $expiresAt = $share->expires_at;

        $this->actingAs($this->employee)
            ->from($this->employeeRoute('show', ['share' => $share]))
            ->post($this->employeeRoute('extend', ['share' => $share]), ['days' => 7])
            ->assertSessionHasErrors('days');

        $this->assertTrue($share->fresh()->expires_at->equalTo($expiresAt));
    }

    public function test_extend_does_not_revive_a_share_the_purge_job_just_ended()
    {
        // The sender's page loaded the share while it was still ready...
        $share = Share::factory()->ready()->create(['user_id' => $this->employee->id, 'expires_at' => now()->subMinute()]);
        // ...then the hourly job marked it expired before the extend arrived
        Share::whereKey($share->id)->update(['status' => Share::STATUS_EXPIRED]);

        $this->assertFalse(app(ShareService::class)->extend($share, 7));
        $this->assertSame(Share::STATUS_EXPIRED, $share->fresh()->status);
    }

    public function test_extend_only_accepts_the_configured_choices()
    {
        $share = Share::factory()->ready()->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)
            ->post($this->employeeRoute('extend', ['share' => $share]), ['days' => 365])
            ->assertSessionHasErrors('days');
    }

    public function test_employee_cannot_extend_another_users_share()
    {
        $share = Share::factory()->ready()->create(['user_id' => User::factory()->employee()->create()->id]);

        $this->actingAs($this->employee)
            ->post($this->employeeRoute('extend', ['share' => $share]), ['days' => 7])
            ->assertForbidden();
    }

    public function test_show_page_lists_activity_newest_first_with_ip_and_browser()
    {
        $share = Share::factory()->ready()->create(['user_id' => $this->employee->id]);
        ShareAccessLog::create(['share_id' => $share->id, 'event' => 'view', 'ip' => '203.0.113.5', 'user_agent' => 'OldBrowser/1.0']);
        $this->travel(1)->minutes();
        ShareAccessLog::create(['share_id' => $share->id, 'event' => 'zip', 'ip' => '203.0.113.9', 'user_agent' => 'NewBrowser/2.0']);

        $this->actingAs($this->employee)
            ->get($this->employeeRoute('show', ['share' => $share]))
            ->assertOk()
            ->assertSeeInOrder([__('messages.shares_event_zip'), '203.0.113.9', 'NewBrowser/2.0', __('messages.shares_event_view'), '203.0.113.5'])
            ->assertSee(__('messages.shares_extend'));
    }

    public function test_activity_is_paginated()
    {
        $share = Share::factory()->ready()->create(['user_id' => $this->employee->id]);
        for ($i = 1; $i <= 30; $i++) {
            ShareAccessLog::create(['share_id' => $share->id, 'event' => 'view', 'ip' => "198.51.100.{$i}"]);
        }

        $this->actingAs($this->employee)
            ->get($this->employeeRoute('show', ['share' => $share]))
            ->assertSee('198.51.100.30')
            ->assertDontSee('198.51.100.5')
            ->assertSee('activity=2');

        $this->actingAs($this->employee)
            ->get($this->employeeRoute('show', ['share' => $share]).'?activity=2')
            ->assertSee('198.51.100.5')
            ->assertDontSee('198.51.100.30');
    }

    public function test_ended_share_has_no_extend_form()
    {
        $share = Share::factory()->ready()->create(['user_id' => $this->employee->id, 'status' => Share::STATUS_REVOKED]);

        $this->actingAs($this->employee)
            ->get($this->employeeRoute('show', ['share' => $share]))
            ->assertOk()
            ->assertDontSee(__('messages.shares_extend_button'));
    }
}
