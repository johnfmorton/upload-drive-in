/**
 * File Manager Debug Utilities
 */

// Add this to app.js with: import './file-manager-debug';

window.debugFileManager = function() {
    console.group('File Manager Debug Information');
    
    // Check Alpine.js
    console.log('Alpine.js loaded:', typeof window.Alpine !== 'undefined');
    
    // Check container
    const container = document.querySelector('[data-lazy-container]');
    console.log('Container exists:', !!container);
    
    if (container) {
        console.log('Container has x-data:', container.hasAttribute('x-data'));
        console.log('Container Alpine data stack:', container._x_dataStack);
        
        // Try to get Alpine component
        if (window.Alpine) {
            try {
                const data = window.Alpine.$data(container);
                console.log('Alpine data:', data);
                console.log('Files count:', data.files ? data.files.length : 'N/A');
                console.log('Filtered files count:', data.filteredFiles ? data.filteredFiles.length : 'N/A');
            } catch (e) {
                console.error('Error accessing Alpine data:', e);
            }
        }
    }
    
    // Check coordination state
    console.log('File Manager State:', window.fileManagerState);
    console.log('Already Initialized:', window.fileManagerAlreadyInitialized);
    
    // Check lazy loader
    if (window.FileManagerLazyLoader) {
        console.log('Lazy Loader class exists');
        
        // Check if instance exists
        if (window.fileManagerState && window.fileManagerState.instance) {
            const instance = window.fileManagerState.instance;
            console.log('Lazy Loader instance:', instance);
            console.log('Cache stats:', instance.getCacheStats ? instance.getCacheStats() : 'N/A');
        }
    }
    
    console.groupEnd();
    
    // Return instructions for fixing common issues
    return `
    File Manager Debug Complete. Check console for details.
    
    Common fixes:
    1. If no files are showing, try running: 
       - window.location.reload() to refresh the page
       - Or manually trigger file loading with: window.fileManagerState.instance.loadMore()
       
    2. If Alpine.js component is not detected:
       - Check if the container has the correct x-data attribute
       - Ensure Alpine.js is properly initialized
       
    3. If files are loaded but not displayed:
       - Check the filters in the UI
       - Try clearing filters with: window.fileManagerState.instance.clearAllFilters()
    `;
};

// Add a button to the UI for easy debugging
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-lazy-container]');
    if (container) {
        const debugButton = document.createElement('button');
        debugButton.textContent = 'Debug File Manager';
        debugButton.className = 'debug-button hidden';
        debugButton.style.position = 'fixed';
        debugButton.style.bottom = '10px';
        debugButton.style.right = '10px';
        debugButton.style.zIndex = '9999';
        debugButton.style.padding = '5px 10px';
        debugButton.style.background = '#f0f0f0';
        debugButton.style.border = '1px solid #ccc';
        debugButton.style.borderRadius = '4px';
        
        debugButton.addEventListener('click', () => {
            console.clear();
            window.debugFileManager();
        });
        
        document.body.appendChild(debugButton);
        
        // Show debug button with Ctrl+Shift+D
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                debugButton.classList.toggle('hidden');
                e.preventDefault();
            }
        });
    }
});