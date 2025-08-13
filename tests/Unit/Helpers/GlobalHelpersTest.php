<?php

namespace Tests\Unit\Helpers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GlobalHelpersTest extends TestCase
{
    use RefreshDatabase;

    private string $setupStateFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupStateFile = storage_path('app/setup/setup-state.json');
        
        // Clean up any existing setup state file
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
    }

    protected function tearDown(): void
    {
        // Clean up setup state file after each test
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        parent::tearDown();
    }

    public function test_is_setup_required_function_works(): void
    {
        $this->assertTrue(is_setup_required());
    }

    public function test_is_setup_complete_function_works(): void
    {
        $this->assertFalse(is_setup_complete());
    }

    public function test_get_setup_step_function_works(): void
    {
        $step = get_setup_step();
        $this->assertIsString($step);
        $this->assertNotEmpty($step);
    }

    public function test_should_bypass_setup_function_works(): void
    {
        $this->assertTrue(should_bypass_setup('setup.welcome'));
        $this->assertTrue(should_bypass_setup(null, '/setup/admin'));
        $this->assertTrue(should_bypass_setup(null, '/build/app.css'));
        $this->assertFalse(should_bypass_setup('dashboard'));
    }

    public function test_format_bytes_function_works(): void
    {
        $this->assertEquals('0 B', format_bytes(0));
        $this->assertEquals('1 KB', format_bytes(1024));
        $this->assertEquals('1 MB', format_bytes(1024 * 1024));
        $this->assertEquals('1 GB', format_bytes(1024 * 1024 * 1024));
    }
}