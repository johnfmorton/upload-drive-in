<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\Support\Facades\View;

class FileManagerHeaderTest extends TestCase
{
    public function test_header_component_renders_with_admin_user_type()
    {
        $statistics = [
            'total' => 25,
            'pending' => 3,
            'total_size' => 1048576
        ];

        $view = View::make('components.file-manager.header', [
            'userType' => 'admin',
            'statistics' => $statistics
        ]);

        $html = $view->render();

        // Check that the component renders without errors
        $this->assertStringContainsString('Header Section', $html);
        $this->assertStringContainsString('text-lg leading-6 font-medium text-gray-900', $html);
        $this->assertStringContainsString('Statistics', $html);
        
        // Check that localized content is rendered
        $this->assertStringContainsString('Uploaded Files', $html);
        $this->assertStringContainsString('Total Files:', $html);
        $this->assertStringContainsString('Pending:', $html);
        $this->assertStringContainsString('Total Size:', $html);
    }

    public function test_header_component_renders_with_employee_user_type()
    {
        $statistics = [
            'total' => 10,
            'pending' => 1,
            'total_size' => 524288
        ];

        $view = View::make('components.file-manager.header', [
            'userType' => 'employee',
            'statistics' => $statistics
        ]);

        $html = $view->render();

        // Check that the component renders without errors
        $this->assertStringContainsString('Header Section', $html);
        $this->assertStringContainsString('Statistics', $html);
        
        // Check that the same localized content is used for employee
        $this->assertStringContainsString('Uploaded Files', $html);
        $this->assertStringContainsString('Total Files:', $html);
    }

    public function test_header_component_handles_empty_statistics()
    {
        $view = View::make('components.file-manager.header', [
            'userType' => 'admin',
            'statistics' => []
        ]);

        $html = $view->render();

        // Check that default values are used when statistics are empty
        $this->assertStringContainsString('statistics.total || 0', $html);
        $this->assertStringContainsString('statistics.pending || 0', $html);
        $this->assertStringContainsString('statistics.total_size || 0', $html);
    }

    public function test_header_component_defaults_to_admin_for_unknown_user_type()
    {
        $view = View::make('components.file-manager.header', [
            'userType' => 'unknown',
            'statistics' => []
        ]);

        $html = $view->render();

        // Should still render without errors and use admin defaults
        $this->assertStringContainsString('Header Section', $html);
        $this->assertStringContainsString('Uploaded Files', $html);
    }
}