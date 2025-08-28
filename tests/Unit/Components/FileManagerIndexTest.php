<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\View\Component;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FileManagerIndexTest extends TestCase
{
    /** @test */
    public function it_renders_with_admin_user_type()
    {
        $files = collect([
            (object) ['id' => 1, 'original_filename' => 'test.pdf', 'email' => 'test@example.com']
        ]);
        
        $statistics = ['total' => 1, 'pending' => 0, 'total_size' => 1024];

        $view = $this->blade(
            '<x-file-manager.index :userType="$userType" :files="$files" :statistics="$statistics" />',
            [
                'userType' => 'admin',
                'files' => $files,
                'statistics' => $statistics
            ]
        );

        $view->assertSee('x-data="fileManager(', false);
        $view->assertSee('file-manager');
        $view->assertSee('data-lazy-container');
    }

    /** @test */
    public function it_renders_with_employee_user_type_and_username()
    {
        $files = collect([
            (object) ['id' => 1, 'original_filename' => 'test.pdf', 'email' => 'test@example.com']
        ]);
        
        $statistics = ['total' => 1, 'pending' => 0, 'total_size' => 1024];

        $view = $this->blade(
            '<x-file-manager.index :userType="$userType" :username="$username" :files="$files" :statistics="$statistics" />',
            [
                'userType' => 'employee',
                'username' => 'john-doe',
                'files' => $files,
                'statistics' => $statistics
            ]
        );

        $view->assertSee('x-data="fileManager(', false);
        $view->assertSee('file-manager');
        $view->assertSee('data-lazy-container');
    }

    /** @test */
    public function it_handles_paginated_files()
    {
        $items = collect([
            (object) ['id' => 1, 'original_filename' => 'test1.pdf', 'email' => 'test1@example.com'],
            (object) ['id' => 2, 'original_filename' => 'test2.pdf', 'email' => 'test2@example.com']
        ]);

        $paginator = new LengthAwarePaginator(
            $items,
            2,
            10,
            1,
            ['path' => request()->url()]
        );

        $statistics = ['total' => 2, 'pending' => 0, 'total_size' => 2048];

        $view = $this->blade(
            '<x-file-manager.index :userType="$userType" :files="$files" :statistics="$statistics" />',
            [
                'userType' => 'admin',
                'files' => $paginator,
                'statistics' => $statistics
            ]
        );

        $view->assertSee('x-data="fileManager(', false);
        $view->assertSee('&quot;id&quot;:1', false);
        $view->assertSee('&quot;id&quot;:2', false);
    }

    /** @test */
    public function it_includes_all_required_components()
    {
        $files = collect();
        $statistics = ['total' => 0, 'pending' => 0, 'total_size' => 0];

        $view = $this->blade(
            '<x-file-manager.index :userType="$userType" :files="$files" :statistics="$statistics" />',
            [
                'userType' => 'admin',
                'files' => $files,
                'statistics' => $statistics
            ]
        );

        // Check that all major components are included by looking for the component tags
        $view->assertSee('file-manager');
        $view->assertSee('data-lazy-container');
        // The component includes are processed by Blade, so we check for the rendered structure
        $view->assertSee('Header Section', false);
        $view->assertSee('Toolbar', false);
        $view->assertSee('Advanced Filters Panel', false);
    }

    /** @test */
    public function it_shows_empty_state_when_no_files()
    {
        $files = collect();
        $statistics = ['total' => 0, 'pending' => 0, 'total_size' => 0];

        $view = $this->blade(
            '<x-file-manager.index :userType="$userType" :files="$files" :statistics="$statistics" />',
            [
                'userType' => 'admin',
                'files' => $files,
                'statistics' => $statistics
            ]
        );

        $view->assertSee('No files uploaded yet');
        $view->assertSee('Files uploaded through your public upload form will appear here');
    }

    /** @test */
    public function it_shows_no_results_state()
    {
        $files = collect();
        $statistics = ['total' => 0, 'pending' => 0, 'total_size' => 0];

        $view = $this->blade(
            '<x-file-manager.index :userType="$userType" :files="$files" :statistics="$statistics" />',
            [
                'userType' => 'admin',
                'files' => $files,
                'statistics' => $statistics
            ]
        );

        $view->assertSee('No files found');
        $view->assertSee('Try adjusting your search or filter criteria');
        $view->assertSee('Clear all filters');
    }

    /** @test */
    public function it_handles_default_statistics()
    {
        $files = collect();

        $view = $this->blade(
            '<x-file-manager.index :userType="$userType" :files="$files" />',
            [
                'userType' => 'admin',
                'files' => $files
            ]
        );

        // Should render without errors even without statistics
        $view->assertSee('file-manager');
        $view->assertSee('data-lazy-container');
    }
}