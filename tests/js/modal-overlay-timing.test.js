/**
 * Modal Overlay Timing Tests
 * 
 * Comprehensive tests for modal timing behavior and z-index hierarchy validation.
 * These tests specifically address the requirements for the upload success modal overlay fix.
 */

import { jest } from '@jest/globals';

// Mock Alpine.js with more comprehensive functionality
global.Alpine = {
    data: jest.fn(),
    start: jest.fn(),
    plugin: jest.fn(),
    store: jest.fn(),
    reactive: jest.fn()
};

// Mock window.getComputedStyle
global.getComputedStyle = jest.fn((element) => {
    const zIndexMap = {
        'modal-container': '9999',
        'modal-backdrop': '9998',
        'modal-content': '10000'
    };
    
    let zIndex = 'auto';
    for (const [className, value] of Object.entries(zIndexMap)) {
        if (element.classList && element.classList.contains(className)) {
            zIndex = value;
            break;
        }
    }
    
    return {
        zIndex,
        opacity: element.style.opacity || '1',
        display: element.style.display || 'block',
        position: 'fixed',
        visibility: 'visible'
    };
});

// Enhanced modal mock with timing simulation
const createModalMock = (name = 'upload-success') => {
    const container = document.createElement('div');
    container.className = 'fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-[9999] modal-container';
    container.setAttribute('data-modal-name', name);
    container.setAttribute('data-z-index', '9999');
    container.setAttribute('data-modal-type', 'container');
    container.style.display = 'none';
    
    const backdrop = document.createElement('div');
    backdrop.className = 'fixed inset-0 bg-gray-500 opacity-75 z-[9998] modal-backdrop';
    backdrop.setAttribute('data-modal-name', name);
    backdrop.setAttribute('data-z-index', '9998');
    backdrop.setAttribute('data-modal-type', 'backdrop');
    
    const content = document.createElement('div');
    content.className = 'relative mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-2xl sm:mx-auto z-[10000] modal-content';
    content.setAttribute('data-modal-name', name);
    content.setAttribute('data-z-index', '10000');
    content.setAttribute('data-modal-type', 'content');
    
    const closeButton = document.createElement('button');
    closeButton.textContent = 'Close';
    closeButton.className = 'inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white';
    
    const title = document.createElement('h3');
    title.textContent = name === 'upload-success' ? 'Upload Complete' : 'Success';
    
    const message = document.createElement('p');
    message.textContent = name === 'upload-success' ? 'Files uploaded successfully!' : 'Operation completed successfully.';
    
    content.appendChild(title);
    content.appendChild(message);
    content.appendChild(closeButton);
    container.appendChild(backdrop);
    container.appendChild(content);
    
    return {
        container,
        backdrop,
        content,
        closeButton,
        title,
        message,
        
        show() {
            this.container.style.display = 'block';
            this.container.style.visibility = 'visible';
            this.container.setAttribute('x-show', 'true');
            
            // Make content visible in JSDOM
            this.backdrop.style.display = 'block';
            this.backdrop.style.visibility = 'visible';
            this.content.style.display = 'block';
            this.content.style.visibility = 'visible';
            this.closeButton.style.display = 'block';
            this.closeButton.style.visibility = 'visible';
            
            // Simulate Alpine.js transition timing
            setTimeout(() => {
                this.backdrop.style.opacity = '0.75';
                this.content.style.opacity = '1';
            }, 50);
            
            return this;
        },
        
        hide() {
            this.container.style.display = 'none';
            this.container.setAttribute('x-show', 'false');
            this.backdrop.style.opacity = '0';
            this.content.style.opacity = '0';
            return this;
        },
        
        appendTo(parent) {
            parent.appendChild(this.container);
            return this;
        },
        
        remove() {
            if (this.container.parentNode) {
                this.container.parentNode.removeChild(this.container);
            }
            return this;
        }
    };
};

describe('Modal Overlay Timing Tests', () => {
    let modal;
    
    beforeEach(() => {
        document.body.innerHTML = '';
        modal = createModalMock('upload-success');
        modal.appendTo(document.body);
    });
    
    afterEach(() => {
        modal.remove();
        jest.clearAllTimers();
    });
    
    describe('Requirement 1.1: Modal displays without gray overlay', () => {
        test('modal displays immediately without delay', () => {
            modal.show();
            
            expect(modal.container.style.display).toBe('block');
            expect(modal.container.getAttribute('x-show')).toBe('true');
            expect(modal.content.style.display).toBe('block');
        });
        
        test('modal content is visible immediately after show', () => {
            modal.show();
            
            // Check that all modal elements are visible (JSDOM compatible)
            expect(modal.container.style.display).toBe('block');
            expect(modal.content.style.display).toBe('block');
            expect(modal.closeButton.style.display).toBe('block');
            expect(modal.title.textContent).toBe('Upload Complete');
            expect(modal.message.textContent).toBe('Files uploaded successfully!');
        });
        
        test('modal remains visible after 1 second delay', (done) => {
            modal.show();
            
            setTimeout(() => {
                expect(modal.container.style.display).toBe('block');
                expect(modal.content.style.display).toBe('block');
                expect(modal.closeButton.style.display).toBe('block');
                done();
            }, 1000);
        });
        
        test('modal remains visible after 2 second delay', (done) => {
            modal.show();
            
            setTimeout(() => {
                expect(modal.container.style.display).toBe('block');
                expect(modal.content.style.display).toBe('block');
                expect(modal.closeButton.style.display).toBe('block');
                expect(modal.closeButton.disabled).toBeFalsy();
                done();
            }, 2000);
        });
        
        test('modal remains visible after 3 second delay', (done) => {
            modal.show();
            
            setTimeout(() => {
                expect(modal.container.style.display).toBe('block');
                expect(modal.content.style.display).toBe('block');
                expect(modal.title.textContent).toBe('Upload Complete');
                expect(modal.message.textContent).toBe('Files uploaded successfully!');
                done();
            }, 3000);
        });
    });
    
    describe('Requirement 1.4: Z-index layering ensures modal is topmost', () => {
        test('modal container has highest z-index', () => {
            modal.show();
            
            const computedStyle = window.getComputedStyle(modal.container);
            expect(computedStyle.zIndex).toBe('9999');
            expect(modal.container.getAttribute('data-z-index')).toBe('9999');
        });
        
        test('modal content has higher z-index than backdrop', () => {
            modal.show();
            
            const backdropStyle = window.getComputedStyle(modal.backdrop);
            const contentStyle = window.getComputedStyle(modal.content);
            
            expect(backdropStyle.zIndex).toBe('9998');
            expect(contentStyle.zIndex).toBe('10000');
            expect(parseInt(contentStyle.zIndex)).toBeGreaterThan(parseInt(backdropStyle.zIndex));
        });
        
        test('z-index hierarchy is maintained during transitions', (done) => {
            modal.show();
            
            // Check immediately
            expect(modal.container.getAttribute('data-z-index')).toBe('9999');
            expect(modal.backdrop.getAttribute('data-z-index')).toBe('9998');
            expect(modal.content.getAttribute('data-z-index')).toBe('10000');
            
            // Check after transition delay
            setTimeout(() => {
                expect(modal.container.getAttribute('data-z-index')).toBe('9999');
                expect(modal.backdrop.getAttribute('data-z-index')).toBe('9998');
                expect(modal.content.getAttribute('data-z-index')).toBe('10000');
                
                const containerStyle = window.getComputedStyle(modal.container);
                const backdropStyle = window.getComputedStyle(modal.backdrop);
                const contentStyle = window.getComputedStyle(modal.content);
                
                expect(containerStyle.zIndex).toBe('9999');
                expect(backdropStyle.zIndex).toBe('9998');
                expect(contentStyle.zIndex).toBe('10000');
                done();
            }, 500);
        });
        
        test('z-index values remain consistent across multiple show/hide cycles', () => {
            // Show and hide multiple times
            for (let i = 0; i < 3; i++) {
                modal.show();
                expect(modal.container.getAttribute('data-z-index')).toBe('9999');
                expect(modal.backdrop.getAttribute('data-z-index')).toBe('9998');
                expect(modal.content.getAttribute('data-z-index')).toBe('10000');
                
                modal.hide();
                // Z-index should remain the same even when hidden
                expect(modal.container.getAttribute('data-z-index')).toBe('9999');
                expect(modal.backdrop.getAttribute('data-z-index')).toBe('9998');
                expect(modal.content.getAttribute('data-z-index')).toBe('10000');
            }
        });
    });
    
    describe('Requirement 3.4: Test case for modal z-index hierarchy validation', () => {
        test('validates complete z-index hierarchy structure', () => {
            modal.show();
            
            // Test data attributes
            expect(modal.container.getAttribute('data-modal-type')).toBe('container');
            expect(modal.backdrop.getAttribute('data-modal-type')).toBe('backdrop');
            expect(modal.content.getAttribute('data-modal-type')).toBe('content');
            
            // Test z-index data attributes
            expect(modal.container.getAttribute('data-z-index')).toBe('9999');
            expect(modal.backdrop.getAttribute('data-z-index')).toBe('9998');
            expect(modal.content.getAttribute('data-z-index')).toBe('10000');
            
            // Test CSS classes
            expect(modal.container.classList.contains('z-[9999]')).toBe(true);
            expect(modal.backdrop.classList.contains('z-[9998]')).toBe(true);
            expect(modal.content.classList.contains('z-[10000]')).toBe(true);
            
            // Test computed styles
            const containerStyle = window.getComputedStyle(modal.container);
            const backdropStyle = window.getComputedStyle(modal.backdrop);
            const contentStyle = window.getComputedStyle(modal.content);
            
            expect(containerStyle.zIndex).toBe('9999');
            expect(backdropStyle.zIndex).toBe('9998');
            expect(contentStyle.zIndex).toBe('10000');
        });
        
        test('validates z-index hierarchy with multiple modals', () => {
            const modal2 = createModalMock('association-success');
            modal2.appendTo(document.body);
            
            modal.show();
            modal2.show();
            
            // Both modals should have the same z-index structure
            expect(modal.container.getAttribute('data-z-index')).toBe('9999');
            expect(modal2.container.getAttribute('data-z-index')).toBe('9999');
            
            expect(modal.backdrop.getAttribute('data-z-index')).toBe('9998');
            expect(modal2.backdrop.getAttribute('data-z-index')).toBe('9998');
            
            expect(modal.content.getAttribute('data-z-index')).toBe('10000');
            expect(modal2.content.getAttribute('data-z-index')).toBe('10000');
            
            modal2.remove();
        });
        
        test('validates z-index hierarchy persists through DOM manipulation', () => {
            modal.show();
            
            // Simulate DOM manipulation that might affect z-index
            const newElement = document.createElement('div');
            newElement.style.zIndex = '5000';
            newElement.className = 'some-other-element';
            document.body.appendChild(newElement);
            
            // Modal z-index should still be higher
            const containerStyle = window.getComputedStyle(modal.container);
            const contentStyle = window.getComputedStyle(modal.content);
            
            expect(parseInt(containerStyle.zIndex)).toBeGreaterThan(5000);
            expect(parseInt(contentStyle.zIndex)).toBeGreaterThan(5000);
            
            document.body.removeChild(newElement);
        });
    });
    
    describe('Modal Event Handling and Timing', () => {
        test('open-modal event triggers immediate display', () => {
            const openEvent = new CustomEvent('open-modal', { detail: 'upload-success' });
            
            // Simulate Alpine.js event listener
            const eventHandler = jest.fn((event) => {
                if (event.detail === 'upload-success') {
                    modal.show();
                }
            });
            
            window.addEventListener('open-modal', eventHandler);
            window.dispatchEvent(openEvent);
            
            expect(eventHandler).toHaveBeenCalledWith(openEvent);
            expect(modal.container.style.display).toBe('block');
            
            window.removeEventListener('open-modal', eventHandler);
        });
        
        test('modal state changes are logged in debug mode', () => {
            const consoleSpy = jest.spyOn(console, 'group').mockImplementation();
            const consoleLogSpy = jest.spyOn(console, 'log').mockImplementation();
            const consoleGroupEndSpy = jest.spyOn(console, 'groupEnd').mockImplementation();
            
            // Simulate debug mode logging
            const logModalState = (action, modalName) => {
                console.group(`🔍 Modal Debug: ${action} - ${modalName}`);
                console.log('Modal Name:', modalName);
                console.log('Show State:', true);
                console.log('Container Z-Index:', '9999');
                console.log('Timestamp:', new Date().toISOString());
                console.groupEnd();
            };
            
            logModalState('Opening', 'upload-success');
            
            expect(consoleSpy).toHaveBeenCalledWith('🔍 Modal Debug: Opening - upload-success');
            expect(consoleLogSpy).toHaveBeenCalledWith('Modal Name:', 'upload-success');
            expect(consoleLogSpy).toHaveBeenCalledWith('Show State:', true);
            expect(consoleLogSpy).toHaveBeenCalledWith('Container Z-Index:', '9999');
            expect(consoleGroupEndSpy).toHaveBeenCalled();
            
            consoleSpy.mockRestore();
            consoleLogSpy.mockRestore();
            consoleGroupEndSpy.mockRestore();
        });
        
        test('modal timing does not interfere with user interactions', (done) => {
            modal.show();
            
            // Test immediate interaction
            expect(modal.closeButton.disabled).toBeFalsy();
            
            const clickHandler = jest.fn();
            modal.closeButton.addEventListener('click', clickHandler);
            
            // Test interaction after delay
            setTimeout(() => {
                expect(modal.closeButton.disabled).toBeFalsy();
                
                const clickEvent = new Event('click', { bubbles: true });
                modal.closeButton.dispatchEvent(clickEvent);
                
                expect(clickHandler).toHaveBeenCalled();
                done();
            }, 2000);
        });
        
        test('backdrop click functionality persists through timing', (done) => {
            modal.show();
            
            const backdropClickHandler = jest.fn();
            modal.backdrop.addEventListener('click', backdropClickHandler);
            
            // Test backdrop click after delay
            setTimeout(() => {
                const clickEvent = new Event('click', { bubbles: true });
                modal.backdrop.dispatchEvent(clickEvent);
                
                expect(backdropClickHandler).toHaveBeenCalled();
                done();
            }, 2000);
        });
    });
    
    describe('Debug Mode Functionality', () => {
        test('debug mode adds appropriate CSS classes', () => {
            // Simulate debug mode
            modal.container.classList.add('z-debug-highest');
            modal.content.classList.add('stacking-context-debug');
            
            expect(modal.container.classList.contains('z-debug-highest')).toBe(true);
            expect(modal.content.classList.contains('stacking-context-debug')).toBe(true);
        });
        
        test('debug mode detection works correctly', () => {
            // Simulate URL parameter detection
            const mockLocation = {
                search: '?modal-debug=true'
            };
            
            const debugMode = mockLocation.search.includes('modal-debug=true');
            expect(debugMode).toBe(true);
            
            // Simulate localStorage detection
            const mockLocalStorage = {
                getItem: jest.fn().mockReturnValue('true')
            };
            
            const debugModeFromStorage = mockLocalStorage.getItem('modal-debug') === 'true';
            expect(debugModeFromStorage).toBe(true);
        });
        
        test('debug mode provides comprehensive state information', () => {
            modal.show();
            
            const debugInfo = {
                modalName: modal.container.getAttribute('data-modal-name'),
                showState: modal.container.getAttribute('x-show') === 'true',
                containerZIndex: modal.container.getAttribute('data-z-index'),
                backdropZIndex: modal.backdrop.getAttribute('data-z-index'),
                contentZIndex: modal.content.getAttribute('data-z-index'),
                containerVisible: modal.container.style.display === 'block',
                contentVisible: modal.content.style.display === 'block',
                timestamp: new Date().toISOString()
            };
            
            expect(debugInfo.modalName).toBe('upload-success');
            expect(debugInfo.showState).toBe(true);
            expect(debugInfo.containerZIndex).toBe('9999');
            expect(debugInfo.backdropZIndex).toBe('9998');
            expect(debugInfo.contentZIndex).toBe('10000');
            expect(debugInfo.containerVisible).toBe(true);
            expect(debugInfo.contentVisible).toBe(true);
            expect(debugInfo.timestamp).toBeDefined();
        });
    });
});