<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaginationConfigurationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagination_configuration_logging_method_works()
    {
        // This test verifies that the pagination configuration logging method
        // works correctly without interfering with the application bootstrap
        
        Log::shouldReceive('info')
            ->once()
            ->with('File manager pagination configuration loaded', \Mockery::type('array'));

        // Directly call the logging method to test it works
        \App\Helpers\PaginationConfigHelper::logPaginationConfiguration();
        
        $this->assertTrue(true); // Test passes if no exceptions are thrown
    }

    public function test_pagination_configuration_values_are_accessible()
    {
        // Test that the configuration values are properly loaded and accessible
        $itemsPerPage = config('file-manager.pagination.items_per_page');
        $minItemsPerPage = config('file-manager.pagination.min_items_per_page');
        $maxItemsPerPage = config('file-manager.pagination.max_items_per_page');

        $this->assertIsInt($itemsPerPage);
        $this->assertIsInt($minItemsPerPage);
        $this->assertIsInt($maxItemsPerPage);
        
        $this->assertGreaterThanOrEqual($minItemsPerPage, $itemsPerPage);
        $this->assertLessThanOrEqual($maxItemsPerPage, $itemsPerPage);
        
        $this->assertEquals(1, $minItemsPerPage);
        $this->assertEquals(100, $maxItemsPerPage);
    }

    public function test_pagination_helper_config_method_returns_correct_structure()
    {
        $config = \App\Helpers\PaginationConfigHelper::getPaginationConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('items_per_page', $config);
        $this->assertArrayHasKey('min_items_per_page', $config);
        $this->assertArrayHasKey('max_items_per_page', $config);
        
        // Verify the values match the configuration
        $this->assertEquals(config('file-manager.pagination.items_per_page'), $config['items_per_page']);
        $this->assertEquals(config('file-manager.pagination.min_items_per_page'), $config['min_items_per_page']);
        $this->assertEquals(config('file-manager.pagination.max_items_per_page'), $config['max_items_per_page']);
    }
}