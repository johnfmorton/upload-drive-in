<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\View\Component;
use Illuminate\Support\Facades\View;

class FileManagerFileGridTest extends TestCase
{
    /** @test */
    public function it_renders_with_admin_user_type()
    {
        $view = View::make('components.file-manager.file-grid', [
            'userType' => 'admin'
        ]);

        $rendered = $view->render();

        $this->assertStringContainsString('x-show="viewMode === \'grid\' && filteredFiles.length > 0"', $rendered);
        $this->assertStringContainsString('grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4', $rendered);
        $this->assertStringContainsString('x-model="selectedFiles"', $rendered);
        $this->assertStringContainsString('x-on:click="previewFile(file)"', $rendered);
        $this->assertStringContainsString('x-on:click="downloadFile(file)"', $rendered);
        $this->assertStringContainsString('x-on:click="deleteFile(file)"', $rendered);
    }

    /** @test */
    public function it_renders_with_employee_user_type()
    {
        $view = View::make('components.file-manager.file-grid', [
            'userType' => 'employee',
            'username' => 'testuser'
        ]);

        $rendered = $view->render();

        $this->assertStringContainsString('x-show="viewMode === \'grid\' && filteredFiles.length > 0"', $rendered);
        $this->assertStringContainsString('grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4', $rendered);
        $this->assertStringContainsString('x-model="selectedFiles"', $rendered);
    }

    /** @test */
    public function it_throws_exception_when_employee_user_type_missing_username()
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('Username is required for employee user type');

        View::make('components.file-manager.file-grid', [
            'userType' => 'employee'
        ])->render();
    }

    /** @test */
    public function it_includes_message_field_for_admin_only()
    {
        $adminView = View::make('components.file-manager.file-grid', [
            'userType' => 'admin'
        ]);

        $employeeView = View::make('components.file-manager.file-grid', [
            'userType' => 'employee',
            'username' => 'testuser'
        ]);

        $adminRendered = $adminView->render();
        $employeeRendered = $employeeView->render();

        // Admin should have more content (message field) than employee
        $this->assertGreaterThan(strlen($employeeRendered), strlen($adminRendered));
        
        // Both should have basic file info fields
        $this->assertStringContainsString('Size:', $adminRendered);
        $this->assertStringContainsString('Size:', $employeeRendered);
    }

    /** @test */
    public function it_includes_proper_localization_keys()
    {
        $view = View::make('components.file-manager.file-grid', [
            'userType' => 'admin'
        ]);

        $rendered = $view->render();

        // Check for key elements that should be present
        $this->assertStringContainsString('Select', $rendered);
        $this->assertStringContainsString('Size:', $rendered);
        $this->assertStringContainsString('Date:', $rendered);
        $this->assertStringContainsString('Preview', $rendered);
        $this->assertStringContainsString('Download', $rendered);
        $this->assertStringContainsString('Delete', $rendered);
    }

    /** @test */
    public function it_includes_proper_responsive_grid_classes()
    {
        $view = View::make('components.file-manager.file-grid', [
            'userType' => 'admin'
        ]);

        $rendered = $view->render();

        $this->assertStringContainsString('grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4', $rendered);
        $this->assertStringContainsString('gap-4 p-4 sm:p-6', $rendered);
        $this->assertStringContainsString('flex flex-col h-full', $rendered);
    }

    /** @test */
    public function it_includes_proper_file_preview_functionality()
    {
        $view = View::make('components.file-manager.file-grid', [
            'userType' => 'admin'
        ]);

        $rendered = $view->render();

        $this->assertStringContainsString('x-if="file.can_preview && file.thumbnail_url"', $rendered);
        $this->assertStringContainsString('checkerboard-bg', $rendered);
        $this->assertStringContainsString('cursor: pointer', $rendered);
        $this->assertStringContainsString('x-if="!file.can_preview || !file.thumbnail_url"', $rendered);
    }

    /** @test */
    public function it_includes_status_badges()
    {
        $view = View::make('components.file-manager.file-grid', [
            'userType' => 'admin'
        ]);

        $rendered = $view->render();

        $this->assertStringContainsString('x-show="file.google_drive_file_id"', $rendered);
        $this->assertStringContainsString('bg-green-100 text-green-800', $rendered);
        $this->assertStringContainsString('x-show="!file.google_drive_file_id"', $rendered);
        $this->assertStringContainsString('bg-yellow-100 text-yellow-800', $rendered);
    }

    /** @test */
    public function it_includes_action_buttons_with_proper_styling()
    {
        $view = View::make('components.file-manager.file-grid', [
            'userType' => 'admin'
        ]);

        $rendered = $view->render();

        $this->assertStringContainsString('text-blue-600 hover:text-blue-800', $rendered);
        $this->assertStringContainsString('text-green-600 hover:text-green-800', $rendered);
        $this->assertStringContainsString('text-red-600 hover:text-red-800', $rendered);
        $this->assertStringContainsString('mt-auto', $rendered); // Ensures footer sticks to bottom
    }
}