/**
 * File Manager Initialization Coordination
 * 
 * This module coordinates between Alpine.js and the FileManagerLazyLoader
 * to prevent multiple initializations of the File Manager component.
 */

// Global initialization state
window.fileManagerState = window.fileManagerState || {
    initialized: false,
    initSource: null,
    instance: null
};

/**
 * Initialize the File Manager only once
 * @param {string} source - The source of initialization ('alpine' or 'lazy-loader')
 * @param {Object} options - Initialization options
 * @returns {Object} - The File Manager instance
 */
function initializeFileManager(source, options = {}) {
    // Check the global flag first
    if (window.fileManagerAlreadyInitialized) {
        console.info(`File Manager already initialized. Skipping ${source} initialization.`);
        return window.fileManagerState.instance;
    }
    
    // If already initialized through the state, return the instance
    if (window.fileManagerState.initialized) {
        console.info(`File Manager already initialized by ${window.fileManagerState.initSource}. Skipping ${source} initialization.`);
        return window.fileManagerState.instance;
    }
    
    console.info(`Initializing File Manager from ${source}`);
    
    // Set the global flag immediately
    window.fileManagerAlreadyInitialized = true;
    
    window.fileManagerState.initialized = true;
    window.fileManagerState.initSource = source;
    
    // Create instance based on source
    if (source === 'lazy-loader') {
        window.fileManagerState.instance = new FileManagerLazyLoader(options);
    } else if (source === 'alpine') {
        // For Alpine.js initialization, we'll set the instance later
        // when the Alpine component is fully initialized
        console.info('Alpine.js initialization will set the instance when ready');
    }
    
    return window.fileManagerState.instance;
}

// Export for use in other modules
window.initializeFileManager = initializeFileManager;
/**

 * Debug function to help troubleshoot initialization issues
 */
// function debugFileManagerState() {
//     console.group('File Manager State Debug');
//     console.log('Initialized:', window.fileManagerState.initialized);
//     console.log('Init Source:', window.fileManagerState.initSource);
//     console.log('Instance:', window.fileManagerState.instance ? 'Exists' : 'None');
//     console.log('Alpine.js Loaded:', typeof window.Alpine !== 'undefined');
//     console.log('fileManagerRegistered:', window.fileManagerRegistered);
//     console.log('fileManagerInitialized:', window.fileManagerInitialized);
//     console.log('fileManagerAlreadyInitialized:', window.fileManagerAlreadyInitialized);
    
//     const container = document.querySelector('[data-lazy-container]');
//     console.log('Container exists:', !!container);
//     if (container) {
//         console.log('Container has x-data:', container.hasAttribute('x-data'));
//     }
//     console.groupEnd();
// }

// // Export debug function
// window.debugFileManagerState = debugFileManagerState;

// // Debug on load
// document.addEventListener('DOMContentLoaded', () => {
//     setTimeout(() => {
//         if (window.debugFileManagerState) {
//             window.debugFileManagerState();
//         }
//     }, 1000); // Wait 1 second to ensure everything is loaded
// });