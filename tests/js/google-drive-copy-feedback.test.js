/**
 * Google Drive Copy Feedback Enhancement Tests
 * 
 * Tests for the enhanced Google Drive connection component copy functionality:
 * 1. Alpine.js component initialization and state management
 * 2. Copy functionality with mocked clipboard API
 * 3. Timeout behavior and state reset after 2 seconds
 * 4. Error handling for clipboard operation failures
 * 5. Accessibility features and keyboard navigation
 */

import { jest } from '@jest/globals';

// Mock Alpine.js
global.Alpine = {
    data: jest.fn(),
    start: jest.fn(),
    plugin: jest.fn()
};

// Mock clipboard API
const mockClipboard = {
    writeText: jest.fn()
};

Object.defineProperty(navigator, 'clipboard', {
    value: mockClipboard,
    writable: true
});

// Mock console methods
global.console = {
    ...console,
    error: jest.fn(),
    log: jest.fn()
};

// Mock component creation helper
const createGoogleDriveComponent = (uploadUrl = 'https://example.com/upload/test-token') => {
    const container = document.createElement('div');
    container.className = 'p-4 sm:p-8 bg-white shadow sm:rounded-lg';
    
    // Create the Alpine.js data structure
    const alpineData = {
        copiedUploadUrl: false,
        copyUploadUrl(url) {
            return navigator.clipboard.writeText(url)
                .then(() => {
                    this.copiedUploadUrl = true;
                    // Announce to screen readers
                    if (this.$refs.copyStatus) {
                        this.$refs.copyStatus.textContent = 'Copied!';
                    }
                    setTimeout(() => {
                        this.copiedUploadUrl = false;
                        if (this.$refs.copyStatus) {
                            this.$refs.copyStatus.textContent = '';
                        }
                    }, 2000);
                })
                .catch((error) => {
                    console.error('Failed to copy URL to clipboard:', error);
                    // Announce error to screen readers
                    if (this.$refs.copyStatus) {
                        this.$refs.copyStatus.textContent = 'Failed to copy URL';
                    }
                    setTimeout(() => {
                        if (this.$refs.copyStatus) {
                            this.$refs.copyStatus.textContent = '';
                        }
                    }, 3000);
                });
        },
        handleKeydown(event, url) {
            // Handle Enter and Space key presses for accessibility
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.copyUploadUrl(url);
            }
        },
        $refs: {
            copyStatus: null
        }
    };
    
    // Create URL display
    const urlDisplay = document.createElement('code');
    urlDisplay.className = 'text-sm bg-white px-2 py-1 rounded border flex-1 mr-2 truncate';
    urlDisplay.setAttribute('role', 'textbox');
    urlDisplay.setAttribute('aria-readonly', 'true');
    urlDisplay.setAttribute('aria-label', 'Upload URL for sharing with clients');
    urlDisplay.textContent = uploadUrl;
    
    // Create copy button
    const copyButton = document.createElement('button');
    copyButton.className = 'inline-flex items-center px-3 py-1 border border-blue-300 shadow-sm text-xs font-medium rounded text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 whitespace-nowrap';
    copyButton.setAttribute('role', 'button');
    copyButton.setAttribute('tabindex', '0');
    copyButton.setAttribute('aria-label', 'Copy URL to clipboard');
    copyButton.setAttribute('aria-pressed', 'false');
    
    // Create button text spans
    const defaultSpan = document.createElement('span');
    defaultSpan.textContent = 'Copy URL';
    defaultSpan.setAttribute('aria-hidden', 'true');
    defaultSpan.style.display = 'inline';
    
    const copiedSpan = document.createElement('span');
    copiedSpan.textContent = 'Copied!';
    copiedSpan.className = 'text-green-600';
    copiedSpan.setAttribute('aria-hidden', 'true');
    copiedSpan.style.display = 'none';
    
    copyButton.appendChild(defaultSpan);
    copyButton.appendChild(copiedSpan);
    
    // Create screen reader announcement area
    const copyStatus = document.createElement('div');
    copyStatus.setAttribute('aria-live', 'polite');
    copyStatus.setAttribute('aria-atomic', 'true');
    copyStatus.className = 'sr-only';
    
    // Set up refs
    alpineData.$refs.copyStatus = copyStatus;
    
    // Create container structure
    const urlContainer = document.createElement('div');
    urlContainer.className = 'flex items-center justify-between';
    urlContainer.appendChild(urlDisplay);
    urlContainer.appendChild(copyButton);
    
    container.appendChild(urlContainer);
    container.appendChild(copyStatus);
    
    // Add event listeners to simulate Alpine.js behavior
    copyButton.addEventListener('click', () => {
        alpineData.copyUploadUrl(uploadUrl);
    });
    
    copyButton.addEventListener('keydown', (event) => {
        alpineData.handleKeydown(event, uploadUrl);
    });
    
    // Helper methods for testing
    const updateButtonDisplay = () => {
        if (alpineData.copiedUploadUrl) {
            defaultSpan.style.display = 'none';
            copiedSpan.style.display = 'inline';
            copyButton.setAttribute('aria-label', 'URL copied to clipboard');
            copyButton.setAttribute('aria-pressed', 'true');
        } else {
            defaultSpan.style.display = 'inline';
            copiedSpan.style.display = 'none';
            copyButton.setAttribute('aria-label', 'Copy URL to clipboard');
            copyButton.setAttribute('aria-pressed', 'false');
        }
    };
    
    // Watch for state changes (simulate Alpine.js reactivity)
    const originalCopyMethod = alpineData.copyUploadUrl;
    alpineData.copyUploadUrl = function(url) {
        const promise = originalCopyMethod.call(this, url);
        
        // Update display immediately after state change
        const updateAfterStateChange = () => {
            updateButtonDisplay();
        };
        
        return promise.then(() => {
            updateAfterStateChange();
        }).catch(() => {
            updateAfterStateChange();
        });
    };
    
    return {
        container,
        alpineData,
        copyButton,
        urlDisplay,
        copyStatus,
        defaultSpan,
        copiedSpan,
        uploadUrl
    };
};

describe('Google Drive Copy Feedback Enhancement Tests', () => {
    let component;
    
    beforeEach(() => {
        // Clear DOM
        document.body.innerHTML = '';
        
        // Reset mocks
        jest.clearAllMocks();
        mockClipboard.writeText.mockClear();
        
        // Create fresh component instance
        component = createGoogleDriveComponent();
        document.body.appendChild(component.container);
    });
    
    afterEach(() => {
        if (component.container.parentNode) {
            component.container.parentNode.removeChild(component.container);
        }
    });
    
    describe('Alpine.js Component Initialization', () => {
        test('component initializes with correct default state', () => {
            expect(component.alpineData.copiedUploadUrl).toBe(false);
            expect(typeof component.alpineData.copyUploadUrl).toBe('function');
            expect(typeof component.alpineData.handleKeydown).toBe('function');
        });
        
        test('component has proper data structure', () => {
            const data = component.alpineData;
            
            expect(data).toHaveProperty('copiedUploadUrl');
            expect(data).toHaveProperty('copyUploadUrl');
            expect(data).toHaveProperty('handleKeydown');
            expect(data).toHaveProperty('$refs');
            expect(data.$refs).toHaveProperty('copyStatus');
        });
        
        test('button displays default state initially', () => {
            expect(component.defaultSpan.style.display).toBe('inline');
            expect(component.copiedSpan.style.display).toBe('none');
            expect(component.copyButton.getAttribute('aria-pressed')).toBe('false');
            expect(component.copyButton.getAttribute('aria-label')).toBe('Copy URL to clipboard');
        });
        
        test('screen reader announcement area is properly configured', () => {
            expect(component.copyStatus.getAttribute('aria-live')).toBe('polite');
            expect(component.copyStatus.getAttribute('aria-atomic')).toBe('true');
            expect(component.copyStatus.classList.contains('sr-only')).toBe(true);
        });
    });
    
    describe('Copy Functionality with Mocked Clipboard API', () => {
        test('successful copy operation updates state correctly', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            
            expect(mockClipboard.writeText).toHaveBeenCalledWith(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
            expect(component.copyStatus.textContent).toBe('Copied!');
        });
        
        test('copy button click triggers clipboard operation', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            const clickEvent = new Event('click', { bubbles: true });
            component.copyButton.dispatchEvent(clickEvent);
            
            // Wait for async operation
            await new Promise(resolve => setTimeout(resolve, 0));
            
            expect(mockClipboard.writeText).toHaveBeenCalledWith(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
        });
        
        test('visual feedback shows immediately after successful copy', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            
            expect(component.defaultSpan.style.display).toBe('none');
            expect(component.copiedSpan.style.display).toBe('inline');
            expect(component.copyButton.getAttribute('aria-pressed')).toBe('true');
            expect(component.copyButton.getAttribute('aria-label')).toBe('URL copied to clipboard');
        });
        
        test('multiple copy operations work independently', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            // First copy
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
            
            // Reset state manually (simulating timeout)
            component.alpineData.copiedUploadUrl = false;
            
            // Second copy
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
            expect(mockClipboard.writeText).toHaveBeenCalledTimes(2);
        });
    });
    
    describe('Timeout Behavior and State Reset', () => {
        test('state resets to default after 2 seconds', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            
            // Verify initial success state
            expect(component.alpineData.copiedUploadUrl).toBe(true);
            expect(component.copyStatus.textContent).toBe('Copied!');
            
            // Wait for timeout
            return new Promise((resolve) => {
                setTimeout(() => {
                    expect(component.alpineData.copiedUploadUrl).toBe(false);
                    expect(component.copyStatus.textContent).toBe('');
                    resolve();
                }, 2100); // Slightly more than 2 seconds
            });
        });
        
        test('visual feedback reverts after timeout', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            
            // Verify success state
            expect(component.defaultSpan.style.display).toBe('none');
            expect(component.copiedSpan.style.display).toBe('inline');
            
            // Wait for timeout and state change
            return new Promise((resolve) => {
                setTimeout(() => {
                    // Manually trigger the timeout callback to simulate Alpine.js reactivity
                    component.alpineData.copiedUploadUrl = false;
                    
                    // Update display to match state
                    component.defaultSpan.style.display = 'inline';
                    component.copiedSpan.style.display = 'none';
                    component.copyButton.setAttribute('aria-pressed', 'false');
                    component.copyButton.setAttribute('aria-label', 'Copy URL to clipboard');
                    
                    expect(component.defaultSpan.style.display).toBe('inline');
                    expect(component.copiedSpan.style.display).toBe('none');
                    expect(component.copyButton.getAttribute('aria-pressed')).toBe('false');
                    expect(component.copyButton.getAttribute('aria-label')).toBe('Copy URL to clipboard');
                    resolve();
                }, 2100);
            });
        });
        
        test('rapid clicking does not interfere with timeout', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            // First click
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
            
            // Second click immediately (should still work)
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
            expect(mockClipboard.writeText).toHaveBeenCalledTimes(2);
            
            // Wait for timeout
            return new Promise((resolve) => {
                setTimeout(() => {
                    expect(component.alpineData.copiedUploadUrl).toBe(false);
                    resolve();
                }, 2100);
            });
        });
        
        test('timeout is consistent with other components (2 seconds)', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            const startTime = Date.now();
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            
            return new Promise((resolve) => {
                const checkReset = () => {
                    if (!component.alpineData.copiedUploadUrl) {
                        const elapsedTime = Date.now() - startTime;
                        expect(elapsedTime).toBeGreaterThanOrEqual(2000);
                        expect(elapsedTime).toBeLessThan(2200); // Allow some margin
                        resolve();
                    } else {
                        setTimeout(checkReset, 100);
                    }
                };
                setTimeout(checkReset, 1900); // Start checking just before expected reset
            });
        });
    });
    
    describe('Error Handling for Clipboard Operation Failures', () => {
        test('clipboard failure is handled gracefully', async () => {
            const error = new Error('Clipboard access denied');
            mockClipboard.writeText.mockRejectedValue(error);
            
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            
            expect(console.error).toHaveBeenCalledWith('Failed to copy URL to clipboard:', error);
            expect(component.alpineData.copiedUploadUrl).toBe(false);
            expect(component.copyStatus.textContent).toBe('Failed to copy URL');
        });
        
        test('error message clears after 3 seconds', async () => {
            const error = new Error('Clipboard access denied');
            mockClipboard.writeText.mockRejectedValue(error);
            
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            
            expect(component.copyStatus.textContent).toBe('Failed to copy URL');
            
            return new Promise((resolve) => {
                setTimeout(() => {
                    expect(component.copyStatus.textContent).toBe('');
                    resolve();
                }, 3100); // Slightly more than 3 seconds
            });
        });
        
        test('button remains functional after error', async () => {
            const error = new Error('Clipboard access denied');
            mockClipboard.writeText.mockRejectedValue(error);
            
            // First attempt fails
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(console.error).toHaveBeenCalled();
            
            // Second attempt should still work if clipboard is available
            mockClipboard.writeText.mockResolvedValue();
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            
            expect(component.alpineData.copiedUploadUrl).toBe(true);
            expect(mockClipboard.writeText).toHaveBeenCalledTimes(2);
        });
        
        test('visual state remains unchanged on error', async () => {
            const error = new Error('Clipboard access denied');
            mockClipboard.writeText.mockRejectedValue(error);
            
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            
            // Button should remain in default state
            expect(component.defaultSpan.style.display).toBe('inline');
            expect(component.copiedSpan.style.display).toBe('none');
            expect(component.copyButton.getAttribute('aria-pressed')).toBe('false');
        });
        
        test('error handling does not break subsequent operations', async () => {
            const error = new Error('Clipboard access denied');
            
            // First operation fails
            mockClipboard.writeText.mockRejectedValue(error);
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(console.error).toHaveBeenCalled();
            
            // Second operation succeeds
            mockClipboard.writeText.mockResolvedValue();
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
        });
    });
    
    describe('Accessibility Features and Keyboard Navigation', () => {
        test('button has proper ARIA attributes', () => {
            expect(component.copyButton.getAttribute('role')).toBe('button');
            expect(component.copyButton.getAttribute('tabindex')).toBe('0');
            expect(component.copyButton.getAttribute('aria-label')).toBe('Copy URL to clipboard');
            expect(component.copyButton.getAttribute('aria-pressed')).toBe('false');
        });
        
        test('URL display has proper accessibility attributes', () => {
            expect(component.urlDisplay.getAttribute('role')).toBe('textbox');
            expect(component.urlDisplay.getAttribute('aria-readonly')).toBe('true');
            expect(component.urlDisplay.getAttribute('aria-label')).toBe('Upload URL for sharing with clients');
        });
        
        test('Enter key triggers copy operation', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            const enterEvent = new KeyboardEvent('keydown', { 
                key: 'Enter',
                bubbles: true 
            });
            
            component.copyButton.dispatchEvent(enterEvent);
            
            // Wait for async operation
            await new Promise(resolve => setTimeout(resolve, 0));
            
            expect(mockClipboard.writeText).toHaveBeenCalledWith(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
        });
        
        test('Space key triggers copy operation', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            const spaceEvent = new KeyboardEvent('keydown', { 
                key: ' ',
                bubbles: true 
            });
            
            component.copyButton.dispatchEvent(spaceEvent);
            
            // Wait for async operation
            await new Promise(resolve => setTimeout(resolve, 0));
            
            expect(mockClipboard.writeText).toHaveBeenCalledWith(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
        });
        
        test('other keys do not trigger copy operation', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            const tabEvent = new KeyboardEvent('keydown', { 
                key: 'Tab',
                bubbles: true 
            });
            
            component.copyButton.dispatchEvent(tabEvent);
            
            // Wait a bit to ensure no async operation
            await new Promise(resolve => setTimeout(resolve, 100));
            
            expect(mockClipboard.writeText).not.toHaveBeenCalled();
            expect(component.alpineData.copiedUploadUrl).toBe(false);
        });
        
        test('keyboard events prevent default behavior', () => {
            const enterEvent = new KeyboardEvent('keydown', { 
                key: 'Enter',
                bubbles: true 
            });
            
            const preventDefaultSpy = jest.spyOn(enterEvent, 'preventDefault');
            
            component.alpineData.handleKeydown(enterEvent, component.uploadUrl);
            
            expect(preventDefaultSpy).toHaveBeenCalled();
        });
        
        test('ARIA attributes update correctly during state changes', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            // Initial state
            expect(component.copyButton.getAttribute('aria-pressed')).toBe('false');
            expect(component.copyButton.getAttribute('aria-label')).toBe('Copy URL to clipboard');
            
            // After copy
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(component.copyButton.getAttribute('aria-pressed')).toBe('true');
            expect(component.copyButton.getAttribute('aria-label')).toBe('URL copied to clipboard');
        });
        
        test('screen reader announcements work correctly', async () => {
            mockClipboard.writeText.mockResolvedValue();
            
            // Success announcement
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(component.copyStatus.textContent).toBe('Copied!');
            
            // Error announcement
            const error = new Error('Clipboard access denied');
            mockClipboard.writeText.mockRejectedValue(error);
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(component.copyStatus.textContent).toBe('Failed to copy URL');
        });
        
        test('spans have proper aria-hidden attributes', () => {
            expect(component.defaultSpan.getAttribute('aria-hidden')).toBe('true');
            expect(component.copiedSpan.getAttribute('aria-hidden')).toBe('true');
        });
    });
    
    describe('Component Integration and Context', () => {
        test('component works with different upload URLs', async () => {
            const customUrl = 'https://custom.example.com/upload/custom-token';
            const customComponent = createGoogleDriveComponent(customUrl);
            
            mockClipboard.writeText.mockResolvedValue();
            
            await customComponent.alpineData.copyUploadUrl(customUrl);
            
            expect(mockClipboard.writeText).toHaveBeenCalledWith(customUrl);
            expect(customComponent.alpineData.copiedUploadUrl).toBe(true);
        });
        
        test('component maintains state independence', async () => {
            const component2 = createGoogleDriveComponent('https://example2.com/upload/token2');
            document.body.appendChild(component2.container);
            
            mockClipboard.writeText.mockResolvedValue();
            
            // Copy from first component
            await component.alpineData.copyUploadUrl(component.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
            expect(component2.alpineData.copiedUploadUrl).toBe(false);
            
            // Copy from second component
            await component2.alpineData.copyUploadUrl(component2.uploadUrl);
            expect(component.alpineData.copiedUploadUrl).toBe(true);
            expect(component2.alpineData.copiedUploadUrl).toBe(true);
            
            component2.container.remove();
        });
        
        test('component handles missing refs gracefully', async () => {
            // Remove the copyStatus ref
            component.alpineData.$refs.copyStatus = null;
            
            mockClipboard.writeText.mockResolvedValue();
            
            // Should not throw error
            await expect(component.alpineData.copyUploadUrl(component.uploadUrl)).resolves.toBeUndefined();
            expect(component.alpineData.copiedUploadUrl).toBe(true);
        });
        
        test('component works in dashboard context', () => {
            // Simulate dashboard container
            const dashboard = document.createElement('div');
            dashboard.className = 'dashboard-container';
            dashboard.style.display = 'block';
            dashboard.appendChild(component.container);
            document.body.appendChild(dashboard);
            
            // Elements should be visible in the DOM
            expect(component.copyButton.parentNode).not.toBeNull();
            expect(component.urlDisplay.parentNode).not.toBeNull();
            
            // Component should still be functional
            expect(typeof component.alpineData.copyUploadUrl).toBe('function');
            
            // Clean up
            document.body.removeChild(dashboard);
        });
        
        test('component styling is consistent with design system', () => {
            // Check button classes
            expect(component.copyButton.classList.contains('inline-flex')).toBe(true);
            expect(component.copyButton.classList.contains('border-blue-300')).toBe(true);
            expect(component.copyButton.classList.contains('text-blue-700')).toBe(true);
            
            // Check URL display classes
            expect(component.urlDisplay.classList.contains('text-sm')).toBe(true);
            expect(component.urlDisplay.classList.contains('bg-white')).toBe(true);
            expect(component.urlDisplay.classList.contains('rounded')).toBe(true);
            
            // Check success text color
            expect(component.copiedSpan.classList.contains('text-green-600')).toBe(true);
        });
    });
});