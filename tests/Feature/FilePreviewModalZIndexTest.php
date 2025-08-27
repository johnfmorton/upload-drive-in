<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FilePreviewModalZIndexTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function file_preview_modal_has_higher_z_index_than_delete_modal()
    {
        // Test that the file preview modal template contains the correct z-index
        $previewModalPath = resource_path('views/admin/file-manager/partials/preview-modal.blade.php');
        $previewModalContent = file_get_contents($previewModalPath);
        
        // Check that the preview modal has z-[10002]
        $this->assertStringContainsString('z-[10002]', $previewModalContent);
        
        // Test that the CSS file contains the correct z-index values
        $cssPath = resource_path('css/file-manager.css');
        $cssContent = file_get_contents($cssPath);
        
        // Check preview modal z-index values
        $this->assertStringContainsString('z-[10002]', $cssContent);
        $this->assertStringContainsString('z-index: 10002', $cssContent);
        $this->assertStringContainsString('z-index: 10003', $cssContent);
        
        // Check delete modal z-index values (should be lower)
        $this->assertStringContainsString('z-index: 9999', $cssContent);
        $this->assertStringContainsString('z-index: 10000', $cssContent);
    }

    /** @test */
    public function z_index_hierarchy_is_correct()
    {
        $cssPath = resource_path('css/file-manager.css');
        $cssContent = file_get_contents($cssPath);
        
        // Extract z-index values and verify hierarchy
        $zIndexValues = [];
        
        // Find all z-index declarations
        preg_match_all('/z-index:\s*(\d+)/', $cssContent, $matches);
        foreach ($matches[1] as $value) {
            $zIndexValues[] = (int)$value;
        }
        
        // Find Tailwind z-index classes
        preg_match_all('/z-\[(\d+)\]/', $cssContent, $tailwindMatches);
        foreach ($tailwindMatches[1] as $value) {
            $zIndexValues[] = (int)$value;
        }
        
        // Verify that preview modal has the highest z-index
        $maxZIndex = max($zIndexValues);
        $this->assertEquals(10003, $maxZIndex, 'File preview modal should have the highest z-index');
        
        // Verify specific hierarchy
        $this->assertContains(9999, $zIndexValues, 'Delete modal container should have z-index 9999');
        $this->assertContains(10000, $zIndexValues, 'Delete modal panel should have z-index 10000');
        $this->assertContains(10002, $zIndexValues, 'Preview modal container should have z-index 10002');
        $this->assertContains(10003, $zIndexValues, 'Preview modal panel should have z-index 10003');
    }
}