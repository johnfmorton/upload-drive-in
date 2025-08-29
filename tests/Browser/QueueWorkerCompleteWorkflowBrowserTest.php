<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class QueueWorkerCompleteWorkflowBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test the complete queue worker workflow from browser perspective
     */
    public function test_complete_queue_worker_workflow()
    {
        $this->browse(function (Browser $browser) {
            // 1. Initial state - fresh setup
            $browser->visit('/setup/instructions')
                    ->waitFor('#queue-worker-status', 10)
                    ->assertSee('Click the Test Queue Worker button below');

            // Verify initial button states
            $browser->assertVisible('#test-queue-worker-btn')
                    ->assertVisible('#refresh-status-btn')
                    ->assertEnabled('#test-queue-worker-btn')
                    ->assertEnabled('#refresh-status-btn');

            // 2. Test "Check Status" button (should exclude queue worker from general refresh)
            $browser->click('#refresh-status-btn')
                    ->pause(2000) // Allow for AJAX
                    ->assertDontSee('Error'); // Should not show errors

            // Queue worker status should remain unchanged after general status refresh
            $browser->assertSee('Click the Test Queue Worker button below');

            // 3. Test queue worker button
            Queue::fake();
            
            $browser->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 10)
                    ->assertSee('Testing queue worker');

            // Verify buttons are disabled during test
            $browser->assertAttribute('#test-queue-worker-btn', 'disabled', 'true')
                    ->assertAttribute('#refresh-status-btn', 'disabled', 'true');

            // 4. Test status persistence across page refresh
            $browser->refresh()
                    ->waitFor('#queue-worker-status', 10);

            // Status should not be stuck in testing state after refresh
            $statusText = $browser->text('#queue-worker-status');
            $this->assertNotEmpty($statusText);
            $this->assertStringNotContainsString('Testing queue worker', $statusText);

            // 5. Test retry functionality after refresh
            $browser->assertEnabled('#test-queue-worker-btn')
                    ->assertEnabled('#refresh-status-btn');

            // Should be able to start a new test
            $browser->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 10)
                    ->assertSee('Testing queue worker');
        });
    }

    /**
     * Test error handling and recovery
     */
    public function test_error_handling_and_recovery()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/setup/instructions')
                    ->waitFor('#test-queue-worker-btn', 10);

            // Simulate network error by intercepting fetch requests
            $browser->script('
                window.originalFetch = window.fetch;
                window.fetch = function(url) {
                    if (url.includes("queue")) {
                        return Promise.reject(new Error("Network error"));
                    }
                    return window.originalFetch.apply(this, arguments);
                };
            ');

            Queue::fake();
            
            $browser->click('#test-queue-worker-btn')
                    ->waitForText('Unable to start', 10)
                    ->assertSee('Unable to start');

            // Restore network and test recovery
            $browser->script('window.fetch = window.originalFetch;');
            
            // Should be able to retry
            $browser->assertEnabled('#test-queue-worker-btn')
                    ->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 10)
                    ->assertSee('Testing queue worker');
        });
    }

    /**
     * Test mobile responsiveness
     */
    public function test_mobile_responsiveness()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone size
                    ->visit('/setup/instructions')
                    ->waitFor('#queue-worker-status', 10)
                    ->assertVisible('#test-queue-worker-btn')
                    ->assertVisible('#refresh-status-btn');

            // Test that buttons are properly sized for mobile
            $testButton = $browser->element('#test-queue-worker-btn');
            $this->assertGreaterThan(40, $testButton->getSize()->getHeight());

            // Test functionality on mobile
            Queue::fake();
            
            $browser->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 10)
                    ->assertSee('Testing queue worker');
        });
    }

    /**
     * Test keyboard navigation
     */
    public function test_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/setup/instructions')
                    ->waitFor('#test-queue-worker-btn', 10);

            // Test tab navigation to the test button
            $browser->keys('body', ['{tab}', '{tab}', '{tab}']);
            
            // Test Enter key activation
            Queue::fake();
            
            $focusedElement = $browser->driver->switchTo()->activeElement();
            if ($focusedElement && $focusedElement->getTagName() === 'button') {
                $browser->keys($focusedElement, ['{enter}'])
                        ->waitForText('Testing queue worker', 10)
                        ->assertSee('Testing queue worker');
            }
        });
    }

    /**
     * Test concurrent operations
     */
    public function test_concurrent_operations()
    {
        $this->browse(function (Browser $browser1, Browser $browser2) {
            Queue::fake();

            // Open setup page in both browsers
            $browser1->visit('/setup/instructions')->waitFor('#test-queue-worker-btn', 10);
            $browser2->visit('/setup/instructions')->waitFor('#test-queue-worker-btn', 10);

            // Start test in first browser
            $browser1->click('#test-queue-worker-btn')
                     ->waitForText('Testing queue worker', 10);

            // Check second browser shows consistent state after refresh
            $browser2->refresh()
                     ->waitFor('#queue-worker-status', 10);

            // Both browsers should show valid states
            $status1 = $browser1->text('#queue-worker-status');
            $status2 = $browser2->text('#queue-worker-status');
            
            $this->assertNotEmpty($status1);
            $this->assertNotEmpty($status2);
        });
    }

    /**
     * Test rapid clicking prevention
     */
    public function test_rapid_clicking_prevention()
    {
        $this->browse(function (Browser $browser) {
            Queue::fake();

            $browser->visit('/setup/instructions')
                    ->waitFor('#test-queue-worker-btn', 10);

            // Rapidly click the test button multiple times
            for ($i = 0; $i < 5; $i++) {
                $browser->click('#test-queue-worker-btn');
                usleep(100000); // 100ms between clicks
            }

            // Should handle rapid clicks gracefully
            $browser->waitForText('Testing queue worker', 10)
                    ->assertSee('Testing queue worker');

            // Should not show multiple tests running
            $statusText = $browser->text('#queue-worker-status');
            $this->assertEquals(1, substr_count($statusText, 'Testing queue worker'));
        });
    }

    /**
     * Test visual feedback and animations
     */
    public function test_visual_feedback()
    {
        $this->browse(function (Browser $browser) {
            Queue::fake();

            $browser->visit('/setup/instructions')
                    ->waitFor('#queue-worker-status', 10);

            // Check initial icon state
            $iconElement = $browser->element('#queue-worker-icon');
            $initialClasses = $iconElement->getAttribute('class');

            // Start test and check for visual changes
            $browser->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 10);

            // Icon should have changed to indicate testing state
            $testingClasses = $browser->element('#queue-worker-icon')->getAttribute('class');
            $this->assertNotEquals($initialClasses, $testingClasses);
        });
    }

    /**
     * Test page refresh during active test
     */
    public function test_page_refresh_during_test()
    {
        $this->browse(function (Browser $browser) {
            Queue::fake();
            
            $browser->visit('/setup/instructions')
                    ->waitFor('#test-queue-worker-btn', 10)
                    ->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 10);

            // Refresh page during test
            $browser->refresh()
                    ->waitFor('#queue-worker-status', 10);

            // Should handle refresh gracefully and not be stuck in testing state
            $statusText = $browser->text('#queue-worker-status');
            $this->assertNotEmpty($statusText);
            
            // Buttons should be enabled after refresh
            $browser->assertEnabled('#test-queue-worker-btn')
                    ->assertEnabled('#refresh-status-btn');
        });
    }

    /**
     * Test backward compatibility
     */
    public function test_backward_compatibility()
    {
        $this->browse(function (Browser $browser) {
            // Test that existing setup instructions functionality still works
            $browser->visit('/setup/instructions')
                    ->waitFor('.setup-instructions-container', 10)
                    ->assertVisible('#refresh-status-btn')
                    ->assertVisible('#test-queue-worker-btn');

            // Test that Check Status button still works
            $browser->click('#refresh-status-btn')
                    ->pause(2000) // Allow for AJAX
                    ->assertDontSee('Error');

            // Test that the page layout is intact
            $browser->assertVisible('.setup-step')
                    ->assertSee('Setup Instructions');
        });
    }
}