<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * The schedule used to live in app/Console/Kernel.php, which Laravel 12 never
 * loads, so no task ever ran. Guard against it silently going empty again.
 */
class ScheduleRegistrationTest extends TestCase
{
    private function scheduledCommands(): array
    {
        return collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command ?? $event->description)
            ->filter()
            ->values()
            ->all();
    }

    public function test_core_tasks_are_scheduled()
    {
        $commands = implode("\n", $this->scheduledCommands());

        foreach ([
            'uploads:process-pending',
            'google-drive:refresh-tokens',
            'cloud-storage:check-health',
            'token-maintenance',
        ] as $expected) {
            $this->assertStringContainsString($expected, $commands, "{$expected} is not scheduled");
        }
    }

    public function test_health_check_does_not_notify_users()
    {
        // --notify emails every Google Drive user on each run
        $commands = implode("\n", $this->scheduledCommands());

        $this->assertStringNotContainsString('cloud-storage:check-health --notify', $commands);
    }
}
