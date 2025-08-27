/**
 * Modal Z-Index Debugging Utilities
 * 
 * This file provides debugging utilities for modal layering issues.
 * Include this file during development to enable modal debugging features.
 * 
 * Z-Index Standards Enforced:
 * - Modal Container: z-[9999] (9999) - Highest level container
 * - Modal Content: z-[10000] (10000) - Content layer above backdrop
 * - Modal Backdrop: z-[9998] (9998) - Background overlay below content
 * - Debug Panel: z-index: 99999 - Always visible above modals for debugging
 * 
 * Debug Classes Applied:
 * - .z-debug-highest: Red outline for highest priority elements (containers)
 * - .z-debug-high: Orange outline for high priority elements (content)
 * - .z-debug-medium: Yellow outline for medium priority elements (backdrops)
 * 
 * Requirements Addressed:
 * - Requirement 3.1: Z-index values properly defined and documented
 * - Requirement 3.3: CSS debugging utilities for modal layering
 */

class ModalDebugger {
    constructor() {
        this.isEnabled = false;
        this.init();
    }

    init() {
        // Check if debugging is enabled via URL parameter or localStorage
        const urlParams = new URLSearchParams(window.location.search);
        const debugParam = urlParams.get('modal-debug');
        const debugStorage = localStorage.getItem('modal-debug');
        
        this.isEnabled = debugParam === 'true' || debugStorage === 'true';
        
        if (this.isEnabled) {
            this.enableDebugging();
            this.addDebugControls();
            this.logZIndexHierarchy();
        }
    }

    enableDebugging() {
        document.body.classList.add('modal-debug-enabled');
        console.log('🔍 Modal debugging enabled');
        
        // Add debugging styles to body
        if (!document.getElementById('modal-debug-styles')) {
            const style = document.createElement('style');
            style.id = 'modal-debug-styles';
            style.textContent = `
                .modal-debug-info {
                    position: fixed;
                    top: 10px;
                    left: 10px;
                    background: rgba(0, 0, 0, 0.8);
                    color: white;
                    padding: 10px;
                    border-radius: 5px;
                    font-family: monospace;
                    font-size: 12px;
                    z-index: 99999; /* Debug panel must be above all modals (99999 > 10000) */
                    max-width: 300px;
                }
                .modal-debug-info h4 {
                    margin: 0 0 5px 0;
                    color: #ffff00;
                }
                .modal-debug-info ul {
                    margin: 0;
                    padding-left: 15px;
                }
            `;
            document.head.appendChild(style);
        }
    }

    addDebugControls() {
        // Create debug control panel
        const debugPanel = document.createElement('div');
        debugPanel.id = 'modal-debug-panel';
        debugPanel.innerHTML = `
            <div class="modal-debug-info">
                <h4>Modal Debug Controls</h4>
                <button onclick="modalDebugger.toggleDebugging()" style="margin: 2px; padding: 4px 8px; font-size: 11px;">
                    Toggle Debug Mode
                </button>
                <button onclick="modalDebugger.logZIndexHierarchy()" style="margin: 2px; padding: 4px 8px; font-size: 11px;">
                    Log Z-Index Hierarchy
                </button>
                <button onclick="modalDebugger.highlightModals()" style="margin: 2px; padding: 4px 8px; font-size: 11px;">
                    Highlight Modals
                </button>
                <button onclick="modalDebugger.clearHighlights()" style="margin: 2px; padding: 4px 8px; font-size: 11px;">
                    Clear Highlights
                </button>
                <div id="modal-debug-info" style="margin-top: 10px; font-size: 10px;"></div>
            </div>
        `;
        document.body.appendChild(debugPanel);
    }

    toggleDebugging() {
        this.isEnabled = !this.isEnabled;
        localStorage.setItem('modal-debug', this.isEnabled.toString());
        
        if (this.isEnabled) {
            document.body.classList.add('modal-debug-enabled');
            console.log('🔍 Modal debugging enabled');
        } else {
            document.body.classList.remove('modal-debug-enabled');
            console.log('🔍 Modal debugging disabled');
        }
        
        this.updateDebugInfo();
    }

    logZIndexHierarchy() {
        console.group('🔍 Z-Index Hierarchy Analysis');
        
        const elements = document.querySelectorAll('*');
        const zIndexElements = [];
        
        elements.forEach(el => {
            const style = getComputedStyle(el);
            const zIndex = style.zIndex;
            
            if (zIndex !== 'auto' && zIndex !== '0') {
                zIndexElements.push({
                    element: el,
                    zIndex: parseInt(zIndex),
                    tagName: el.tagName,
                    className: el.className,
                    id: el.id,
                    position: style.position
                });
            }
        });
        
        // Sort by z-index value
        zIndexElements.sort((a, b) => a.zIndex - b.zIndex);
        
        console.table(zIndexElements.map(item => ({
            'Z-Index': item.zIndex,
            'Tag': item.tagName,
            'ID': item.id || 'N/A',
            'Classes': item.className || 'N/A',
            'Position': item.position
        })));
        
        // Log modal-specific elements
        const modalElements = document.querySelectorAll('[data-modal-type]');
        if (modalElements.length > 0) {
            console.group('Modal Elements');
            modalElements.forEach(el => {
                const style = getComputedStyle(el);
                console.log(`${el.dataset.modalType} (${el.dataset.modalName}):`, {
                    zIndex: style.zIndex,
                    position: style.position,
                    display: style.display,
                    visibility: style.visibility,
                    opacity: style.opacity
                });
            });
            console.groupEnd();
        }
        
        console.groupEnd();
        
        this.updateDebugInfo();
    }

    highlightModals() {
        const modalElements = document.querySelectorAll('[data-modal-type]');
        modalElements.forEach(el => {
            const type = el.dataset.modalType;
            switch (type) {
                case 'container':
                    el.classList.add('z-debug-highest');
                    break;
                case 'backdrop':
                    el.classList.add('z-debug-medium');
                    break;
                case 'content':
                    el.classList.add('z-debug-high');
                    break;
            }
        });
        console.log('🔍 Modal elements highlighted');
    }

    clearHighlights() {
        const debugClasses = ['z-debug-low', 'z-debug-medium', 'z-debug-high', 'z-debug-highest'];
        debugClasses.forEach(className => {
            document.querySelectorAll(`.${className}`).forEach(el => {
                el.classList.remove(className);
            });
        });
        console.log('🔍 Highlights cleared');
    }

    updateDebugInfo() {
        const infoDiv = document.getElementById('modal-debug-info');
        if (!infoDiv) return;
        
        const modalElements = document.querySelectorAll('[data-modal-type]');
        const visibleModals = Array.from(modalElements).filter(el => {
            const style = getComputedStyle(el);
            return style.display !== 'none' && style.visibility !== 'hidden';
        });
        
        infoDiv.innerHTML = `
            <strong>Status:</strong> ${this.isEnabled ? 'Enabled' : 'Disabled'}<br>
            <strong>Total Modals:</strong> ${modalElements.length}<br>
            <strong>Visible Modals:</strong> ${visibleModals.length}<br>
            <strong>Timestamp:</strong> ${new Date().toLocaleTimeString()}
        `;
    }

    // Monitor modal state changes
    observeModalChanges() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                    const target = mutation.target;
                    if (target.hasAttribute('data-modal-type')) {
                        console.log('🔍 Modal state changed:', {
                            modalName: target.dataset.modalName,
                            modalType: target.dataset.modalType,
                            display: getComputedStyle(target).display,
                            zIndex: getComputedStyle(target).zIndex
                        });
                        this.updateDebugInfo();
                    }
                }
            });
        });

        observer.observe(document.body, {
            attributes: true,
            subtree: true,
            attributeFilter: ['style', 'class']
        });
    }
}

// Initialize the modal debugger
const modalDebugger = new ModalDebugger();

// Make it globally available for console access
window.modalDebugger = modalDebugger;

// Start observing modal changes if debugging is enabled
if (modalDebugger.isEnabled) {
    modalDebugger.observeModalChanges();
}

// Export for module usage
export default ModalDebugger;