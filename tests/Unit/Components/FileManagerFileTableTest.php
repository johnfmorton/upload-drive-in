<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\View\Component;
use Illuminate\Support\Facades\View;

class FileManagerFileTableTest extends TestCase
{
    public function test_file_table_component_renders_with_admin_user_type()
    {
        $view = View::make('components.file-manager.file-table', [
            'userType' => 'admin'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('x-show="viewMode === \'table\'"', $html);
        $this->assertStringContainsString('min-w-full divide-y divide-gray-200', $html);
        $this->assertStringContainsString('sticky left-0 bg-gray-50 z-10', $html);
        $this->assertStringContainsString('sticky right-0 bg-gray-50 z-10', $html);
        // The translation function gets processed, so we check for the actual translated text or the function call
        $this->assertTrue(
            str_contains($html, 'Actions') || 
            str_contains($html, '__("messages.actions")') ||
            str_contains($html, 'messages.actions')
        );
    }

    public function test_file_table_component_renders_with_employee_user_type()
    {
        $view = View::make('components.file-manager.file-table', [
            'userType' => 'employee',
            'username' => 'testuser'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('x-show="viewMode === \'table\'"', $html);
        $this->assertStringContainsString('min-w-full divide-y divide-gray-200', $html);
        $this->assertStringContainsString('sticky left-0 bg-gray-50 z-10', $html);
        $this->assertStringContainsString('sticky right-0 bg-gray-50 z-10', $html);
    }

    public function test_file_table_component_throws_exception_when_employee_missing_username()
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('Username is required for employee user type');

        View::make('components.file-manager.file-table', [
            'userType' => 'employee'
        ])->render();
    }

    public function test_file_table_component_includes_sortable_headers()
    {
        $view = View::make('components.file-manager.file-table', [
            'userType' => 'admin'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('x-on:click="column.sortable ? sortBy(column.key) : null"', $html);
        $this->assertStringContainsString('sortDirection === \'asc\' ? \'▲\' : \'▼\'', $html);
    }

    public function test_file_table_component_includes_column_resizing()
    {
        $view = View::make('components.file-manager.file-table', [
            'userType' => 'admin'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('cursor-col-resize', $html);
        $this->assertStringContainsString('x-on:mousedown="startColumnResize($event, column.key)"', $html);
    }

    public function test_file_table_component_includes_dynamic_columns()
    {
        $view = View::make('components.file-manager.file-table', [
            'userType' => 'admin'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('template x-for="column in visibleColumnsList"', $html);
        $this->assertStringContainsString('getColumnHeaderClass(column)', $html);
        $this->assertStringContainsString('getColumnStyle(column.key)', $html);
        $this->assertStringContainsString('getCellContent(file, column)', $html);
    }

    public function test_file_table_component_includes_action_buttons()
    {
        $view = View::make('components.file-manager.file-table', [
            'userType' => 'admin'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('x-on:click="previewFile(file)"', $html);
        $this->assertStringContainsString('x-on:click="downloadFile(file)"', $html);
        $this->assertStringContainsString('x-on:click="deleteFile(file)"', $html);
        // Check for translation keys or actual translated text
        $this->assertTrue(
            str_contains($html, 'Preview') || 
            str_contains($html, '__("messages.preview")') ||
            str_contains($html, 'messages.preview')
        );
        $this->assertTrue(
            str_contains($html, 'Download') || 
            str_contains($html, '__("messages.download")') ||
            str_contains($html, 'messages.download')
        );
        $this->assertTrue(
            str_contains($html, 'Delete') || 
            str_contains($html, '__("messages.delete")') ||
            str_contains($html, 'messages.delete')
        );
    }

    public function test_file_table_component_includes_empty_state()
    {
        $view = View::make('components.file-manager.file-table', [
            'userType' => 'admin'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('x-show="viewMode === \'table\' && filteredFiles.length === 0"', $html);
        // Just check that the empty state section exists
        $this->assertStringContainsString('text-center py-12', $html);
        $this->assertStringContainsString('mx-auto h-12 w-12 text-gray-400', $html);
    }

    public function test_file_table_component_includes_responsive_design()
    {
        $view = View::make('components.file-manager.file-table', [
            'userType' => 'admin'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('overflow-x-auto', $html);
        $this->assertStringContainsString('overflow-hidden', $html);
    }

    public function test_file_table_component_includes_selection_functionality()
    {
        $view = View::make('components.file-manager.file-table', [
            'userType' => 'admin'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('x-model="selectAll"', $html);
        $this->assertStringContainsString('x-model="selectedFiles"', $html);
        $this->assertStringContainsString(':value="file.id"', $html);
    }
}