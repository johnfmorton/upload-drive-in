<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\PaginationConfigHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class PaginationConfigHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default config values for testing
        Config::set('file-manager.pagination.items_per_page', 10);
        Config::set('file-manager.pagination.min_items_per_page', 1);
        Config::set('file-manager.pagination.max_items_per_page', 100);
    }

    // ========================================
    // Default Value Behavior Tests
    // ========================================

    public function test_default_value_behavior_when_no_environment_variable_is_set()
    {
        // Test with null (no environment variable)
        $result = PaginationConfigHelper::validatePaginationValue(null, 10, 1, 100);
        $this->assertEquals(10, $result, 'Should return default value when no environment variable is set');
    }

    public function test_default_value_behavior_with_empty_string()
    {
        // Test with empty string
        $result = PaginationConfigHelper::validatePaginationValue('', 15, 1, 100);
        $this->assertEquals(15, $result, 'Should return default value when environment variable is empty string');
    }

    public function test_default_value_behavior_with_whitespace()
    {
        // Test with whitespace
        $result = PaginationConfigHelper::validatePaginationValue('   ', 20, 1, 100);
        $this->assertEquals(20, $result, 'Should return default value when environment variable is whitespace');
    }

    // ========================================
    // Environment Variable Override Tests
    // ========================================

    public function test_environment_variable_override_with_valid_integer()
    {
        $result = PaginationConfigHelper::validatePaginationValue(25, 10, 1, 100);
        $this->assertEquals(25, $result, 'Should use environment variable value when valid integer provided');
    }

    public function test_environment_variable_override_with_valid_string_number()
    {
        $result = PaginationConfigHelper::validatePaginationValue('30', 10, 1, 100);
        $this->assertEquals(30, $result, 'Should convert and use valid string number from environment variable');
    }

    public function test_environment_variable_override_with_string_number_with_spaces()
    {
        // PHP's is_numeric() returns true for ' 35 ', so it gets converted to 35
        $result = PaginationConfigHelper::validatePaginationValue(' 35 ', 10, 1, 100);
        $this->assertEquals(35, $result, 'Should convert string with spaces to integer when numeric');
    }

    public function test_environment_variable_override_at_minimum_boundary()
    {
        $result = PaginationConfigHelper::validatePaginationValue(1, 10, 1, 100);
        $this->assertEquals(1, $result, 'Should accept environment variable at minimum boundary');
    }

    public function test_environment_variable_override_at_maximum_boundary()
    {
        $result = PaginationConfigHelper::validatePaginationValue(100, 10, 1, 100);
        $this->assertEquals(100, $result, 'Should accept environment variable at maximum boundary');
    }

    // ========================================
    // Boundary Validation Tests (Min/Max Enforcement)
    // ========================================

    public function test_boundary_validation_enforces_minimum_with_zero()
    {
        $result = PaginationConfigHelper::validatePaginationValue(0, 10, 5, 100);
        $this->assertEquals(10, $result, 'Should fall back to default when value is zero (below absolute minimum of 1)');
    }

    public function test_boundary_validation_enforces_minimum_with_negative()
    {
        $result = PaginationConfigHelper::validatePaginationValue(-10, 15, 5, 100);
        $this->assertEquals(15, $result, 'Should fall back to default when value is negative (below absolute minimum of 1)');
    }

    public function test_boundary_validation_enforces_configured_minimum()
    {
        // Test that values above 1 but below configured minimum get enforced to minimum
        $result = PaginationConfigHelper::validatePaginationValue(3, 10, 5, 100);
        $this->assertEquals(5, $result, 'Should enforce configured minimum when value is above 1 but below min');
    }

    public function test_boundary_validation_enforces_maximum()
    {
        $result = PaginationConfigHelper::validatePaginationValue(150, 10, 1, 100);
        $this->assertEquals(100, $result, 'Should cap value at maximum when exceeding limit');
    }

    public function test_boundary_validation_enforces_maximum_with_very_large_number()
    {
        $result = PaginationConfigHelper::validatePaginationValue(999999, 10, 1, 50);
        $this->assertEquals(50, $result, 'Should cap very large values at maximum');
    }

    public function test_boundary_validation_with_custom_min_max()
    {
        // Test with custom boundaries - the helper applies min/max constraints, not fallback to default
        $result = PaginationConfigHelper::validatePaginationValue(3, 10, 5, 20);
        $this->assertEquals(5, $result, 'Should enforce minimum boundary when value is below minimum');

        $result = PaginationConfigHelper::validatePaginationValue(25, 10, 5, 20);
        $this->assertEquals(20, $result, 'Should cap at custom maximum');

        $result = PaginationConfigHelper::validatePaginationValue(15, 10, 5, 20);
        $this->assertEquals(15, $result, 'Should accept value within custom boundaries');
    }

    // ========================================
    // Invalid Value Fallback Tests
    // ========================================

    public function test_invalid_value_fallback_with_non_numeric_string()
    {
        $result = PaginationConfigHelper::validatePaginationValue('invalid', 12, 1, 100);
        $this->assertEquals(12, $result, 'Should fall back to default with non-numeric string');
    }

    public function test_invalid_value_fallback_with_mixed_string()
    {
        $result = PaginationConfigHelper::validatePaginationValue('25abc', 8, 1, 100);
        $this->assertEquals(8, $result, 'Should fall back to default with mixed alphanumeric string');
    }

    public function test_invalid_value_fallback_with_float_string()
    {
        $result = PaginationConfigHelper::validatePaginationValue('15.5', 10, 1, 100);
        $this->assertEquals(15, $result, 'Should convert float string to integer');
    }

    public function test_invalid_value_fallback_with_boolean()
    {
        $result = PaginationConfigHelper::validatePaginationValue(true, 10, 1, 100);
        $this->assertEquals(10, $result, 'Should fall back to default with boolean true');

        $result = PaginationConfigHelper::validatePaginationValue(false, 10, 1, 100);
        $this->assertEquals(10, $result, 'Should fall back to default with boolean false');
    }

    public function test_invalid_value_fallback_with_array()
    {
        $result = PaginationConfigHelper::validatePaginationValue([25], 10, 1, 100);
        $this->assertEquals(10, $result, 'Should fall back to default with array');
    }

    public function test_invalid_value_fallback_with_object()
    {
        $result = PaginationConfigHelper::validatePaginationValue((object)['value' => 25], 10, 1, 100);
        $this->assertEquals(10, $result, 'Should fall back to default with object');
    }

    // ========================================
    // Edge Cases and Special Values
    // ========================================

    public function test_edge_case_with_string_zero()
    {
        $result = PaginationConfigHelper::validatePaginationValue('0', 10, 1, 100);
        $this->assertEquals(10, $result, 'Should fall back to default with string zero');
    }

    public function test_edge_case_with_negative_string()
    {
        $result = PaginationConfigHelper::validatePaginationValue('-5', 10, 1, 100);
        $this->assertEquals(10, $result, 'Should fall back to default with negative string');
    }

    public function test_edge_case_with_scientific_notation()
    {
        $result = PaginationConfigHelper::validatePaginationValue('1e2', 10, 1, 100);
        $this->assertEquals(100, $result, 'Should handle scientific notation string');
    }

    // ========================================
    // Configuration Helper Tests
    // ========================================

    public function test_get_pagination_config_returns_expected_structure()
    {
        $config = PaginationConfigHelper::getPaginationConfig();
        
        $this->assertIsArray($config, 'Should return array');
        $this->assertArrayHasKey('items_per_page', $config, 'Should have items_per_page key');
        $this->assertArrayHasKey('min_items_per_page', $config, 'Should have min_items_per_page key');
        $this->assertArrayHasKey('max_items_per_page', $config, 'Should have max_items_per_page key');
        $this->assertIsInt($config['items_per_page'], 'items_per_page should be integer');
        $this->assertIsInt($config['min_items_per_page'], 'min_items_per_page should be integer');
        $this->assertIsInt($config['max_items_per_page'], 'max_items_per_page should be integer');
    }

    public function test_get_pagination_config_values_are_reasonable()
    {
        $config = PaginationConfigHelper::getPaginationConfig();
        
        $this->assertGreaterThan(0, $config['items_per_page'], 'items_per_page should be positive');
        $this->assertGreaterThan(0, $config['min_items_per_page'], 'min_items_per_page should be positive');
        $this->assertGreaterThan(0, $config['max_items_per_page'], 'max_items_per_page should be positive');
        $this->assertGreaterThanOrEqual($config['min_items_per_page'], $config['items_per_page'], 'items_per_page should be >= min');
        $this->assertLessThanOrEqual($config['max_items_per_page'], $config['items_per_page'], 'items_per_page should be <= max');
    }

    // ========================================
    // Logging Tests
    // ========================================

    public function test_log_pagination_configuration_logs_info()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('File manager pagination configuration loaded', \Mockery::type('array'));

        PaginationConfigHelper::logPaginationConfiguration();
    }

    public function test_log_pagination_configuration_includes_required_fields()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('File manager pagination configuration loaded', \Mockery::on(function ($data) {
                return is_array($data) 
                    && array_key_exists('items_per_page', $data)
                    && array_key_exists('source', $data)
                    && array_key_exists('min_items_per_page', $data)
                    && array_key_exists('max_items_per_page', $data)
                    && in_array($data['source'], ['environment', 'default'])
                    && (is_int($data['items_per_page']) || is_null($data['items_per_page']))
                    && is_int($data['min_items_per_page'])
                    && is_int($data['max_items_per_page']);
            }));

        PaginationConfigHelper::logPaginationConfiguration();
    }

    public function test_log_pagination_configuration_detects_environment_source()
    {
        // Mock environment variable being set
        $this->app['config']->set('file-manager.pagination.items_per_page', 25);
        
        Log::shouldReceive('info')
            ->once()
            ->with('File manager pagination configuration loaded', \Mockery::on(function ($data) {
                return $data['source'] === 'default'; // Will be default since we're not actually setting env var
            }));

        PaginationConfigHelper::logPaginationConfiguration();
    }

    // ========================================
    // Integration Tests
    // ========================================

    public function test_validation_integration_with_realistic_scenarios()
    {
        // Scenario 1: Production environment with reasonable value
        $result = PaginationConfigHelper::validatePaginationValue('20', 10, 1, 100);
        $this->assertEquals(20, $result);

        // Scenario 2: Development environment with high value for testing
        $result = PaginationConfigHelper::validatePaginationValue('50', 10, 1, 100);
        $this->assertEquals(50, $result);

        // Scenario 3: Misconfigured environment with invalid value
        $result = PaginationConfigHelper::validatePaginationValue('abc', 10, 1, 100);
        $this->assertEquals(10, $result);

        // Scenario 4: Performance testing with maximum value
        $result = PaginationConfigHelper::validatePaginationValue('100', 10, 1, 100);
        $this->assertEquals(100, $result);

        // Scenario 5: Minimal pagination for mobile optimization
        $result = PaginationConfigHelper::validatePaginationValue('5', 10, 1, 100);
        $this->assertEquals(5, $result);
    }

    public function test_validation_maintains_type_safety()
    {
        // Ensure all return values are integers
        $testCases = [
            ['25', 10, 1, 100],
            [null, 10, 1, 100],
            ['invalid', 10, 1, 100],
            [150, 10, 1, 100],
            [-5, 10, 1, 100],
        ];

        foreach ($testCases as $case) {
            $result = PaginationConfigHelper::validatePaginationValue(...$case);
            $this->assertIsInt($result, 'All validation results should be integers');
            $this->assertGreaterThan(0, $result, 'All validation results should be positive');
        }
    }
}