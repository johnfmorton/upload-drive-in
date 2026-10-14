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
    private function scheduledCommands(): string
    {
        return collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command ?? $event->description)
            ->filter()
            ->implode("\n");
    }

    public function test_core_tasks_are_scheduled()
    {
        $commands = $this->scheduledCommands();

        foreach ([
            'uploads:process-pending',
            'google-drive:refresh-tokens',
            'token-maintenance',
            'health-status-validation',
            'cloud-storage:check-health --notify',
            'cloud-storage:monitor --alert',
            'cloud-storage:health-check',
            'cloud-storage:validate-config',
        ] as $expected) {
            $this->assertStringContainsString($expected, $commands, "{$expected} is not scheduled");
        }
    }
}
