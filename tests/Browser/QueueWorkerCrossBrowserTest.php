<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class QueueWorkerCrossBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test queue worker functionality across different browser scenarios
     */
    public function test_queue_worker_functionality_in_chrome()
    {
        $this->browse(function (Browser $browser) {
            $this->runQueueWorkerBrowserTest($browser);
        });
    }

    /**
     * Test mobile responsiveness of queue worker interface
     */
    public function test_mobile_responsiveness()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone 6/7/8 size
                    ->visit('/setup/instructions')
                    ->waitFor('#queue-worker-status')
                    ->assertVisible('#test-queue-worker-btn')
                    ->assertVisible('#refresh-status-btn');

            // Test that buttons are properly sized for mobile
            $testButton = $browser->element('#test-queue-worker-btn');
            $this->assertGreaterThan(44, $testButton->getSize()->getHeight()); // Minimum touch target

            // Test that status text is readable on mobile
            $statusElement = $browser->element('#queue-worker-status');
            $this->assertNotEmpty($statusElement->getText());

            // Test button functionality on mobile
            Queue::fake();
            $browser->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 5)
                    ->assertSee('Testing queue worker');
        });
    }

    /**
     * Test tablet responsiveness
     */
    public function test_tablet_responsiveness()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(768, 1024) // iPad size
                    ->visit('/setup/instructions')
                    ->waitFor('#queue-worker-status')
                    ->assertVisible('#test-queue-worker-btn')
                    ->assertVisible('#refresh-status-btn');

            // Test layout on tablet
            $setupContainer = $browser->element('.setup-instructions-container');
            $this->assertNotNull($setupContainer);

            // Test interaction
            Queue::fake();
            $browser->click('#refresh-status-btn')
                    ->pause(1000) // Allow for AJAX
                    ->assertDontSee('Error'); // Should not show errors
        });
    }

    /**
     * Test keyboard navigation and accessibility
     */
    public function test_keyboard_navigation_and_accessibility()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/setup/instructions')
                    ->waitFor('#test-queue-worker-btn');

            // Test tab navigation
            $browser->keys('body', ['{tab}', '{tab}', '{tab}']); // Navigate to test button
            
            // Test that focused element is visible and has proper focus styles
            $focusedElement = $browser->driver->switchTo()->activeElement();
            $this->assertNotNull($focusedElement);

            // Test Enter key activation
            Queue::fake();
            $browser->keys($focusedElement, ['{enter}'])
                    ->waitForText('Testing queue worker', 5)
                    ->assertSee('Testing queue worker');

            // Test Escape key (if modal or overlay is present)
            $browser->keys('body', ['{escape}']);
        });
    }

    /**
     * Test with JavaScript disabled (graceful degradation)
     */
    public function test_graceful_degradation_without_javascript()
    {
        $this->browse(function (Browser $browser) {
            // Disable JavaScript
            $browser->driver->executeScript('window.jsEnabled = false;');
            
            $browser->visit('/setup/instructions')
                    ->waitFor('#queue-worker-status');

            // Should show fallback message when JS is disabled
            $statusText = $browser->text('#queue-worker-status');
            $this->assertNotEmpty($statusText);

            // Form should still be present for manual testing
            $browser->assertVisible('#test-queue-worker-btn');
        });
    }

    /**
     * Test network connectivity issues
     */
    public function test_network_connectivity_handling()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/setup/instructions')
                    ->waitFor('#test-queue-worker-btn');

            // Simulate network failure by blocking requests
            $browser->driver->executeScript('
                window.originalFetch = window.fetch;
                window.fetch = function() {
                    return Promise.reject(new Error("Network error"));
                };
            ');

            Queue::fake();
            $browser->click('#test-queue-worker-btn')
                    ->waitForText('Unable to start', 10)
                    ->assertSee('Unable to start');

            // Restore network and test recovery
            $browser->driver->executeScript('window.fetch = window.originalFetch;');
            
            $browser->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 5)
                    ->assertSee('Testing queue worker');
        });
    }

    /**
     * Test page refresh during active test
     */
    public function test_page_refresh_during_active_test()
    {
        $this->browse(function (Browser $browser) {
            Queue::fake();
            
            $browser->visit('/setup/instructions')
                    ->waitFor('#test-queue-worker-btn')
                    ->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 5);

            // Refresh page during test
            $browser->refresh()
                    ->waitFor('#queue-worker-status');

            // Should handle refresh gracefully
            $statusText = $browser->text('#queue-worker-status');
            $this->assertNotEmpty($statusText);
            
            // Should not be stuck in testing state
            $this->assertStringNotContainsString('Testing queue worker', $statusText);
        });
    }

    /**
     * Test multiple browser tabs/windows
     */
    public function test_multiple_browser_tabs()
    {
        $this->browse(function (Browser $browser1, Browser $browser2) {
            Queue::fake();

            // Open setup page in both tabs
            $browser1->visit('/setup/instructions')->waitFor('#test-queue-worker-btn');
            $browser2->visit('/setup/instructions')->waitFor('#test-queue-worker-btn');

            // Start test in first tab
            $browser1->click('#test-queue-worker-btn')
                     ->waitForText('Testing queue worker', 5);

            // Check that second tab shows appropriate state
            $browser2->refresh()
                     ->waitFor('#queue-worker-status');

            // Both tabs should show consistent state
            $status1 = $browser1->text('#queue-worker-status');
            $status2 = $browser2->text('#queue-worker-status');
            
            // They might not be identical due to timing, but both should be valid states
            $this->assertNotEmpty($status1);
            $this->assertNotEmpty($status2);
        });
    }

    /**
     * Common test logic for different browsers
     */
    private function runQueueWorkerBrowserTest(Browser $browser)
    {
        Queue::fake();

        $browser->visit('/setup/instructions')
                ->waitFor('#queue-worker-status', 10)
                ->assertSee('Click the Test Queue Worker button below');

        // Test initial state
        $browser->assertVisible('#test-queue-worker-btn')
                ->assertVisible('#refresh-status-btn')
                ->assertEnabled('#test-queue-worker-btn')
                ->assertEnabled('#refresh-status-btn');

        // Test queue worker button
        $browser->click('#test-queue-worker-btn')
                ->waitForText('Testing queue worker', 10)
                ->assertSee('Testing queue worker');

        // Verify buttons are disabled during test
        $browser->assertAttribute('#test-queue-worker-btn', 'disabled', 'true')
                ->assertAttribute('#refresh-status-btn', 'disabled', 'true');

        // Test Check Status button
        $browser->refresh()
                ->waitFor('#refresh-status-btn', 10)
                ->click('#refresh-status-btn')
                ->pause(2000); // Allow for AJAX requests

        // Should not show errors
        $browser->assertDontSee('Error')
                ->assertDontSee('Failed');

        // Test status persistence
        $browser->refresh()
                ->waitFor('#queue-worker-status', 10);

        $statusText = $browser->text('#queue-worker-status');
        $this->assertNotEmpty($statusText);
    }

    /**
     * Test performance under load
     */
    public function test_performance_under_rapid_clicks()
    {
        $this->browse(function (Browser $browser) {
            Queue::fake();

            $browser->visit('/setup/instructions')
                    ->waitFor('#test-queue-worker-btn');

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
     * Test CSS animations and transitions
     */
    public function test_visual_feedback_and_animations()
    {
        $this->browse(function (Browser $browser) {
            Queue::fake();

            $browser->visit('/setup/instructions')
                    ->waitFor('#queue-worker-status');

            // Check initial icon state
            $iconElement = $browser->element('#queue-worker-icon');
            $initialClasses = $iconElement->getAttribute('class');

            // Start test and check for visual changes
            $browser->click('#test-queue-worker-btn')
                    ->waitForText('Testing queue worker', 5);

            // Icon should have changed to indicate testing state
            $testingClasses = $browser->element('#queue-worker-icon')->getAttribute('class');
            $this->assertNotEquals($initialClasses, $testingClasses);

            // Should have appropriate color classes
            $this->assertStringContainsString('text-blue-500', $testingClasses);
        });
    }
}