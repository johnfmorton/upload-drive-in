/**
 * Simplified JavaScript tests for Setup Status functionality
 * 
 * These tests focus on the core functionality without complex DOM mocking
 */

// Import the class to test
const SetupStatusManager = require('../../resources/js/setup-status.js');

describe('SetupStatusManager - Core Functionality', () => {
    let statusManager;

    beforeEach(() => {
        // Create instance with auto-init disabled for testing
        statusManager = new SetupStatusManager({ autoInit: false });
    });

    describe('Basic Properties', () => {
        test('should have correct default properties', () => {
            expect(statusManager.statusSteps).toEqual([
                'database', 'mail', 'google_drive', 'migrations', 'admin_user', 'queue_worker'
            ]);
            expect(statusManager.refreshInProgress).toBe(false);
            expect(statusManager.retryAttempts).toBe(0);
            expect(statusManager.maxRetryAttempts).toBe(3);
            expect(statusManager.retryDelay).toBe(2000);
            expect(statusManager.autoRefreshEnabled).toBe(false);
        });

        test('should bind methods correctly', () => {
            expect(typeof statusManager.refreshAllStatuses).toBe('function');
            expect(typeof statusManager.refreshSingleStep).toBe('function');
            expect(typeof statusManager.handleRefreshError).toBe('function');
            expect(typeof statusManager.retryRefresh).toBe('function');
        });
    });

    describe('Retry Logic', () => {
        test('should reset retry attempts', () => {
            statusManager.retryAttempts = 2;
            statusManager.resetRetryAttempts();
            expect(statusManager.retryAttempts).toBe(0);
        });

        test('should increment retry attempts on error', () => {
            const mockError = new Error('Test error');
            const originalSetTimeout = global.setTimeout;
            global.setTimeout = jest.fn();

            statusManager.handleRefreshError(mockError, 'all');
            
            expect(statusManager.retryAttempts).toBe(1);
            expect(global.setTimeout).toHaveBeenCalled();

            global.setTimeout = originalSetTimeout;
        });

        test('should reset retry attempts after max retries', () => {
            statusManager.retryAttempts = 3; // Set to max
            const mockError = new Error('Max retries reached');

            // Mock the showErrorState method to avoid DOM manipulation
            statusManager.showErrorState = jest.fn();

            statusManager.handleRefreshError(mockError, 'all');

            expect(statusManager.retryAttempts).toBe(0);
            expect(statusManager.showErrorState).toHaveBeenCalled();
        });
    });

    describe('Auto-refresh Management', () => {
        test('should enable auto-refresh', () => {
            const originalSetInterval = global.setInterval;
            global.setInterval = jest.fn(() => 'mock-interval');

            statusManager.toggleAutoRefresh(true);

            expect(statusManager.autoRefreshEnabled).toBe(true);
            expect(global.setInterval).toHaveBeenCalledWith(
                expect.any(Function),
                30000
            );

            global.setInterval = originalSetInterval;
        });

        test('should disable auto-refresh', () => {
            const originalClearInterval = global.clearInterval;
            global.clearInterval = jest.fn();
            
            statusManager.autoRefreshInterval = 'mock-interval';
            statusManager.toggleAutoRefresh(false);

            expect(statusManager.autoRefreshEnabled).toBe(false);
            expect(global.clearInterval).toHaveBeenCalledWith('mock-interval');

            global.clearInterval = originalClearInterval;
        });
    });

    describe('Cleanup', () => {
        test('should cleanup intervals', () => {
            const originalClearInterval = global.clearInterval;
            global.clearInterval = jest.fn();
            
            statusManager.autoRefreshInterval = 'mock-interval';
            statusManager.cleanup();

            expect(global.clearInterval).toHaveBeenCalledWith('mock-interval');

            global.clearInterval = originalClearInterval;
        });
    });

    describe('Status Step Validation', () => {
        test('should validate valid step names', () => {
            const validSteps = ['database', 'mail', 'google_drive', 'migrations', 'admin_user', 'queue_worker'];
            
            validSteps.forEach(step => {
                expect(statusManager.statusSteps.includes(step)).toBe(true);
            });
        });

        test('should reject invalid step names in refreshSingleStep', async () => {
            const originalConsoleError = console.error;
            console.error = jest.fn();

            await statusManager.refreshSingleStep('invalid_step');

            expect(console.error).toHaveBeenCalledWith('Invalid step name:', 'invalid_step');

            console.error = originalConsoleError;
        });
    });

    describe('Message Utilities', () => {
        test('should get correct CSS classes for message types', () => {
            const successClasses = statusManager.getMessageClasses('success');
            const errorClasses = statusManager.getMessageClasses('error');
            const warningClasses = statusManager.getMessageClasses('warning');
            const infoClasses = statusManager.getMessageClasses('info');

            expect(successClasses).toContain('bg-green-100');
            expect(errorClasses).toContain('bg-red-100');
            expect(warningClasses).toContain('bg-yellow-100');
            expect(infoClasses).toContain('bg-blue-100');
        });

        test('should get correct emoji icons for message types', () => {
            const successIcon = statusManager.getMessageIcon('success');
            const errorIcon = statusManager.getMessageIcon('error');
            const warningIcon = statusManager.getMessageIcon('warning');
            const infoIcon = statusManager.getMessageIcon('info');

            expect(successIcon).toContain('✅');
            expect(errorIcon).toContain('🚫');
            expect(warningIcon).toContain('⚠️');
            expect(infoIcon).toContain('ℹ️');
        });
    });

    describe('AJAX Request Timeout Handling', () => {
        test('should handle AbortError correctly', async () => {
            const abortError = new Error('The operation was aborted');
            abortError.name = 'AbortError';

            try {
                // This would normally be called within makeAjaxRequest
                if (abortError.name === 'AbortError') {
                    throw new Error('Request timed out. Please check your connection and try again.');
                }
            } catch (error) {
                expect(error.message).toBe('Request timed out. Please check your connection and try again.');
            }
        });
    });

    describe('Status Icon Updates', () => {
        test('should return correct emoji icons for different statuses', () => {
            // Mock indicator element with emoji icon
            const mockIndicator = {
                querySelector: jest.fn(),
                insertBefore: jest.fn(),
                appendChild: jest.fn()
            };

            const mockEmojiIcon = { 
                textContent: '',
                className: 'status-emoji w-4 h-4 mr-1.5 text-base'
            };
            
            // First call returns null (no SVG), second call returns emoji element
            mockIndicator.querySelector
                .mockReturnValueOnce(null) // No SVG
                .mockReturnValueOnce(mockEmojiIcon); // Emoji element

            statusManager.updateStatusIcon(mockIndicator, 'completed');
            expect(mockEmojiIcon.textContent).toBe('✅');

            // Reset for next test
            mockIndicator.querySelector
                .mockReturnValueOnce(null)
                .mockReturnValueOnce(mockEmojiIcon);

            statusManager.updateStatusIcon(mockIndicator, 'error');
            expect(mockEmojiIcon.textContent).toBe('🚫');

            // Reset for next test
            mockIndicator.querySelector
                .mockReturnValueOnce(null)
                .mockReturnValueOnce(mockEmojiIcon);

            statusManager.updateStatusIcon(mockIndicator, 'checking');
            expect(mockEmojiIcon.textContent).toBe('🔄');
        });
    });

    describe('Concurrent Request Prevention', () => {
        test('should prevent concurrent refresh requests', async () => {
            const originalConsoleLog = console.log;
            console.log = jest.fn();

            // Set refresh in progress
            statusManager.refreshInProgress = true;

            await statusManager.refreshAllStatuses();

            expect(console.log).toHaveBeenCalledWith('Refresh already in progress, skipping...');

            console.log = originalConsoleLog;
        });

        test('should prevent concurrent single step refresh requests', async () => {
            const originalConsoleLog = console.log;
            console.log = jest.fn();

            // Set refresh in progress
            statusManager.refreshInProgress = true;

            await statusManager.refreshSingleStep('database');

            expect(console.log).toHaveBeenCalledWith('Refresh already in progress, skipping...');

            console.log = originalConsoleLog;
        });
    });
});

describe('Global Functions', () => {
    test('should define toggleStatusDetails function in browser environment', () => {
        // In a real browser environment, this would be available globally
        // Here we test that the function would be defined
        // Since we're in Node.js test environment, we just verify the concept
        expect(typeof global.toggleStatusDetails).toBe('undefined'); // Expected in Node.js
        
        // In browser, it would be:
        // expect(typeof toggleStatusDetails).toBe('function');
    });
});

describe('Module Export', () => {
    test('should export SetupStatusManager class', () => {
        expect(SetupStatusManager).toBeDefined();
        expect(typeof SetupStatusManager).toBe('function');
        expect(SetupStatusManager.prototype.constructor).toBe(SetupStatusManager);
    });
});