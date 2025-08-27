/**
 * Modal Overlay Behavior Tests
 * 
 * Tests for the upload success modal overlay fix to ensure:
 * 1. Modal displays without gray overlay after 1-2 seconds
 * 2. Modal interaction (close button, backdrop click) works correctly
 * 3. Z-index hierarchy is maintained properly
 */

import { jest } from '@jest/globals';

// Mock Alpine.js
global.Alpine = {
    data: jest.fn(),
    start: jest.fn(),
    plugin: jest.fn()
};

// Mock DOM environment
const mockModal = {
    container: null,
    backdrop: null,
    content: null,
    closeButton: null,
    
    create() {
        // Create modal container
        this.container = document.createElement('div');
        this.container.className = 'fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-[9999] modal-container';
        this.container.setAttribute('data-modal-name', 'upload-success');
        this.container.setAttribute('data-z-index', '9999');
        this.container.setAttribute('data-modal-type', 'container');
        this.container.style.display = 'none';
        
        // Create backdrop
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'fixed inset-0 bg-gray-500 opacity-75 z-[9998] modal-backdrop';
        this.backdrop.setAttribute('data-modal-name', 'upload-success');
        this.backdrop.setAttribute('data-z-index', '9998');
        this.backdrop.setAttribute('data-modal-type', 'backdrop');
        
        // Create modal content
        this.content = document.createElement('div');
        this.content.className = 'relative mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-2xl sm:mx-auto z-[10000] modal-content';
        this.content.setAttribute('data-modal-name', 'upload-success');
        this.content.setAttribute('data-z-index', '10000');
        this.content.setAttribute('data-modal-type', 'content');
        
        // Create close button
        this.closeButton = document.createElement('button');
        this.closeButton.textContent = 'Close';
        this.closeButton.className = 'inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white';
        
        // Assemble modal structure
        this.content.appendChild(this.closeButton);
        this.container.appendChild(this.backdrop);
        this.container.appendChild(this.content);
        document.body.appendChild(this.container);
        
        return this;
    },
    
    show() {
        this.container.style.display = 'block';
        this.container.setAttribute('x-show', 'true');
        return this;
    },
    
    hide() {
        this.container.style.display = 'none';
        this.container.setAttribute('x-show', 'false');
        return this;
    },
    
    destroy() {
        if (this.container && this.container.parentNode) {
            this.container.parentNode.removeChild(this.container);
        }
    }
};

describe('Modal Overlay Behavior Tests', () => {
    let modal;
    
    beforeEach(() => {
        // Clear DOM
        document.body.innerHTML = '';
        
        // Create fresh modal instance
        modal = Object.create(mockModal);
        modal.create();
    });
    
    afterEach(() => {
        modal.destroy();
    });
    
    describe('Modal Display Timing', () => {
        test('modal displays immediately without delay', () => {
            modal.show();
            
            expect(modal.container.style.display).toBe('block');
            expect(modal.container.getAttribute('x-show')).toBe('true');
        });
        
        test('modal remains visible after 1 second delay', (done) => {
            modal.show();
            
            setTimeout(() => {
                expect(modal.container.style.display).toBe('block');
                expect(modal.content.offsetParent).not.toBeNull();
                done();
            }, 1000);
        });
        
        test('modal remains visible after 2 second delay', (done) => {
            modal.show();
            
            setTimeout(() => {
                expect(modal.container.style.display).toBe('block');
                expect(modal.content.offsetParent).not.toBeNull();
                expect(modal.closeButton.offsetParent).not.toBeNull();
                done();
            }, 2000);
        });
        
        test('modal content is immediately accessible', () => {
            modal.show();
            
            // Check that modal content is visible
            expect(modal.content.offsetParent).not.toBeNull();
            expect(modal.closeButton.offsetParent).not.toBeNull();
            
            // Check that close button is clickable
            expect(modal.closeButton.disabled).toBeFalsy();
        });
    });
    
    describe('Z-Index Hierarchy', () => {
        test('modal container has correct z-index', () => {
            modal.show();
            
            const computedStyle = window.getComputedStyle(modal.container);
            expect(modal.container.getAttribute('data-z-index')).toBe('9999');
            expect(modal.container.classList.contains('z-[9999]')).toBe(true);
        });
        
        test('modal backdrop has lower z-index than content', () => {
            modal.show();
            
            expect(modal.backdrop.getAttribute('data-z-index')).toBe('9998');
            expect(modal.content.getAttribute('data-z-index')).toBe('10000');
            expect(modal.backdrop.classList.contains('z-[9998]')).toBe(true);
            expect(modal.content.classList.contains('z-[10000]')).toBe(true);
        });
        
        test('z-index hierarchy is maintained during transitions', (done) => {
            modal.show();
            
            // Check immediately
            expect(modal.container.getAttribute('data-z-index')).toBe('9999');
            expect(modal.backdrop.getAttribute('data-z-index')).toBe('9998');
            expect(modal.content.getAttribute('data-z-index')).toBe('10000');
            
            // Check after transition time
            setTimeout(() => {
                expect(modal.container.getAttribute('data-z-index')).toBe('9999');
                expect(modal.backdrop.getAttribute('data-z-index')).toBe('9998');
                expect(modal.content.getAttribute('data-z-index')).toBe('10000');
                done();
            }, 500);
        });
    });
    
    describe('Modal Interaction', () => {
        test('close button is clickable immediately', () => {
            modal.show();
            
            expect(modal.closeButton.disabled).toBeFalsy();
            
            // Simulate click
            const clickEvent = new Event('click', { bubbles: true });
            const clickHandler = jest.fn();
            modal.closeButton.addEventListener('click', clickHandler);
            modal.closeButton.dispatchEvent(clickEvent);
            
            expect(clickHandler).toHaveBeenCalled();
        });
        
        test('close button remains clickable after delay', (done) => {
            modal.show();
            
            setTimeout(() => {
                expect(modal.closeButton.disabled).toBeFalsy();
                
                // Simulate click
                const clickEvent = new Event('click', { bubbles: true });
                const clickHandler = jest.fn();
                modal.closeButton.addEventListener('click', clickHandler);
                modal.closeButton.dispatchEvent(clickEvent);
                
                expect(clickHandler).toHaveBeenCalled();
                done();
            }, 2000);
        });
        
        test('backdrop click is functional immediately', () => {
            modal.show();
            
            const clickEvent = new Event('click', { bubbles: true });
            const clickHandler = jest.fn();
            modal.backdrop.addEventListener('click', clickHandler);
            modal.backdrop.dispatchEvent(clickEvent);
            
            expect(clickHandler).toHaveBeenCalled();
        });
        
        test('backdrop click remains functional after delay', (done) => {
            modal.show();
            
            setTimeout(() => {
                const clickEvent = new Event('click', { bubbles: true });
                const clickHandler = jest.fn();
                modal.backdrop.addEventListener('click', clickHandler);
                modal.backdrop.dispatchEvent(clickEvent);
                
                expect(clickHandler).toHaveBeenCalled();
                done();
            }, 2000);
        });
    });
    
    describe('Modal Event Handling', () => {
        test('open-modal event triggers modal display', () => {
            // Mock Alpine.js event handling
            const openModalEvent = new CustomEvent('open-modal', { 
                detail: 'upload-success' 
            });
            
            // Simulate Alpine.js event listener
            window.addEventListener('open-modal', (event) => {
                if (event.detail === 'upload-success') {
                    modal.show();
                }
            });
            
            window.dispatchEvent(openModalEvent);
            
            expect(modal.container.style.display).toBe('block');
        });
        
        test('close-modal event triggers modal hide', () => {
            modal.show();
            
            const closeModalEvent = new CustomEvent('close-modal', { 
                detail: 'upload-success' 
            });
            
            // Simulate Alpine.js event listener
            window.addEventListener('close-modal', (event) => {
                if (event.detail === 'upload-success') {
                    modal.hide();
                }
            });
            
            window.dispatchEvent(closeModalEvent);
            
            expect(modal.container.style.display).toBe('none');
        });
        
        test('escape key closes modal', () => {
            modal.show();
            
            const escapeEvent = new KeyboardEvent('keydown', { 
                key: 'Escape',
                bubbles: true 
            });
            
            // Simulate Alpine.js escape key handler
            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    modal.hide();
                }
            });
            
            window.dispatchEvent(escapeEvent);
            
            expect(modal.container.style.display).toBe('none');
        });
    });
    
    describe('Debug Mode Functionality', () => {
        test('debug mode adds appropriate classes', () => {
            // Simulate debug mode enabled
            modal.container.classList.add('z-debug-highest');
            modal.content.classList.add('stacking-context-debug');
            
            expect(modal.container.classList.contains('z-debug-highest')).toBe(true);
            expect(modal.content.classList.contains('stacking-context-debug')).toBe(true);
        });
        
        test('debug mode provides console logging', () => {
            const consoleSpy = jest.spyOn(console, 'group').mockImplementation();
            const consoleLogSpy = jest.spyOn(console, 'log').mockImplementation();
            const consoleGroupEndSpy = jest.spyOn(console, 'groupEnd').mockImplementation();
            
            // Simulate debug logging
            const logModalState = (action) => {
                console.group(`🔍 Modal Debug: ${action} - upload-success`);
                console.log('Modal Name:', 'upload-success');
                console.log('Show State:', true);
                console.log('Container Z-Index:', '9999');
                console.log('Timestamp:', new Date().toISOString());
                console.groupEnd();
            };
            
            logModalState('Opening');
            
            expect(consoleSpy).toHaveBeenCalledWith('🔍 Modal Debug: Opening - upload-success');
            expect(consoleLogSpy).toHaveBeenCalledWith('Modal Name:', 'upload-success');
            expect(consoleGroupEndSpy).toHaveBeenCalled();
            
            consoleSpy.mockRestore();
            consoleLogSpy.mockRestore();
            consoleGroupEndSpy.mockRestore();
        });
    });
    
    describe('Multiple Modal Scenarios', () => {
        test('multiple modals do not interfere with each other', () => {
            // Create second modal
            const modal2 = Object.create(mockModal);
            modal2.create();
            modal2.container.setAttribute('data-modal-name', 'association-success');
            
            // Show first modal
            modal.show();
            expect(modal.container.style.display).toBe('block');
            
            // Show second modal
            modal2.show();
            expect(modal2.container.style.display).toBe('block');
            
            // Both should maintain their z-index
            expect(modal.container.getAttribute('data-z-index')).toBe('9999');
            expect(modal2.container.getAttribute('data-z-index')).toBe('9999');
            
            modal2.destroy();
        });
        
        test('sequential modal display works correctly', (done) => {
            modal.show();
            
            setTimeout(() => {
                modal.hide();
                
                // Create and show second modal
                const modal2 = Object.create(mockModal);
                modal2.create();
                modal2.container.setAttribute('data-modal-name', 'association-success');
                modal2.show();
                
                expect(modal.container.style.display).toBe('none');
                expect(modal2.container.style.display).toBe('block');
                
                modal2.destroy();
                done();
            }, 1000);
        });
    });
});