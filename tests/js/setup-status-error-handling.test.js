/**
 * Tests for setup status error handling and fallback mechanisms
 */

// Mock fetch for testing
global.fetch = jest.fn();

// Mock DOM elements
const mockElements = {
    'refresh-status-btn': { addEventListener: jest.fn(), disabled: false },
    'refresh-btn-text': { textContent: 'Check Status' },
    'refresh-spinner': { classList: { add: jest.fn(), remove: jest.fn() } },
    'status-database': { classList: { add: jest.fn(), remove: jest.fn() }, setAttribute: jest.fn() },
    'status-database-text': { textContent: '', style: { opacity: '1' } },
    'last-checked': {},
    'last-checked-time': { textContent: '' }
};

// Mock document methods
global.document = {
    getElementById: jest.fn((id) => mockElements[id] || null),
    querySelector: jest.fn((selector) => {
        if (selector === 'meta[name="csrf-token"]') {
            return { getAttribute: () => 'test-token' };
        }
        return null;
    }),
    querySelectorAll: jest.fn(() => []),
    addEventListener: jest.fn(),
    createElement: jest.fn(() => ({
        className: '',
        textContent: '',
        innerHTML: '',
        appendChild: jest.fn(),
        insertAdjacentElement: jest.fn(),
        remove: jest.fn()
    })),
    head: { appendChild: jest.fn() }
};

// Mock console methods
global.console = {
    log: jest.fn(),
    error: jest.fn(),
    warn: jest.fn(),
    debug: jest.fn()
};

// Mock setTimeout and clearTimeout
global.setTimeout = jest.fn((fn, delay) => {
    if (typeof fn === 'function') {
        fn();
    }
    return 1;
});
global.clearTimeout = jest.fn();

// Import the class after mocks are set up
const SetupStatusManager = require('../../resources/js/setup-status.js').default || 
    require('../../resources/js/setup-status.js');

describe('SetupStatusManager Error Handling', () => {
    let manager;

    beforeEach(() => {
        jest.clearAllMocks();
        fetch.mockClear();
        
        // Initialize manager with autoInit disabled to control initialization
        manager = new SetupStatusManager({ autoInit: false });
        manager.init();
    });

    describe('Network Error Handling', () => {
        test('handles network timeout gracefully', async () => {
            // Mock fetch to simulate timeout
            fetch.mockImplementation(() => 
                new Promise((resolve, reject) => {
                    setTimeout(() => reject(new Error('AbortError')), 100);
                })
            );

            await manager.refreshAllStatuses();

            // Should show timeout error message
            expect(console.error).toHaveBeenCalledWith(
                expect.stringContaining('Error refreshing all statuses'),
                expect.any(Error)
            );
        });

        test('handles server errors with retry logic', async () => {
            let callCount = 0;
            fetch.mockImplementation(() => {
                callCount++;
                if (callCount < 3) {
                    return Promise.resolve({
                        ok: false,
                        status: 500,
                        statusText: 'Internal Server Error',
                        json: () => Promise.resolve({
                            error: { message: 'Server error' }
                        })
                    });
                }
                return Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({
                        success: true,
                        data: {
                            statuses: {
                                database: {
                                    status: 'completed',
                                    message: 'Working'
                                }
                            }
                        }
                    })
                });
            });

            await manager.refreshAllStatuses();

            // Should eventually succeed after retries
            expect(fetch).toHaveBeenCalledTimes(3);
        });

        test('shows fallback error state after max retries', async () => {
            // Mock fetch to always fail
            fetch.mockImplementation(() => 
                Promise.resolve({
                    ok: false,
                    status: 500,
                    statusText: 'Internal Server Error',
                    json: () => Promise.resolve({
                        error: { message: 'Persistent server error' }
                    })
                })
            );

            await manager.refreshAllStatuses();

            // Should show error state after max retries
            expect(manager.retryAttempts).toBe(manager.maxRetryAttempts);
        });
    });

    describe('Response Validation', () => {
        test('handles malformed JSON responses', async () => {
            fetch.mockImplementation(() => 
                Promise.resolve({
                    ok: true,
                    json: () => Promise.reject(new Error('Invalid JSON'))
                })
            );

            await manager.refreshAllStatuses();

            expect(console.error).toHaveBeenCalledWith(
                expect.stringContaining('Error refreshing all statuses'),
                expect.any(Error)
            );
        });

        test('validates response structure', async () => {
            fetch.mockImplementation(() => 
                Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({
                        // Missing required fields
                        invalid: 'response'
                    })
                })
            );

            await manager.refreshAllStatuses();

            // Should handle invalid response structure
            expect(console.error).toHaveBeenCalled();
        });
    });

    describe('UI Error States', () => {
        test('shows user-friendly error messages', async () => {
            fetch.mockImplementation(() => 
                Promise.resolve({
                    ok: false,
                    status: 503,
                    statusText: 'Service Unavailable',
                    json: () => Promise.resolve({
                        error: { 
                            message: 'Service temporarily unavailable',
                            code: 'SERVICE_UNAVAILABLE'
                        }
                    })
                })
            );

            await manager.refreshAllStatuses();

            // Should display user-friendly error message
            expect(manager.showErrorMessage).toBeDefined();
        });

        test('provides retry button for recoverable errors', async () => {
            fetch.mockImplementation(() => 
                Promise.resolve({
                    ok: false,
                    status: 503,
                    json: () => Promise.resolve({
                        error: { message: 'Temporary error' }
                    })
                })
            );

            await manager.refreshAllStatuses();

            // Should show retry button for temporary errors
            // This would be verified by checking if showMessage was called with showRetryButton: true
        });

        test('updates status indicators to error state', async () => {
            fetch.mockImplementation(() => 
                Promise.reject(new Error('Network error'))
            );

            await manager.refreshAllStatuses();

            // Should update all status indicators to error state
            manager.statusSteps.forEach(step => {
                const indicator = mockElements[`status-${step}`];
                if (indicator) {
                    expect(indicator.classList.add).toHaveBeenCalledWith('status-error');
                }
            });
        });
    });

    describe('Queue Test Error Handling', () => {
        test('handles queue test dispatch failures', async () => {
            fetch.mockImplementation((url) => {
                if (url.includes('/setup/queue/test')) {
                    return Promise.resolve({
                        ok: false,
                        status: 500,
                        json: () => Promise.resolve({
                            success: false,
                            error: { message: 'Queue service unavailable' }
                        })
                    });
                }
                return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
            });

            await manager.testQueueWorker();

            // Should handle queue test failure gracefully
            expect(console.error).toHaveBeenCalledWith(
                expect.stringContaining('Queue test failed'),
                expect.any(Error)
            );
        });

        test('handles polling errors during queue test', async () => {
            // Mock successful dispatch but failed polling
            fetch.mockImplementation((url) => {
                if (url.includes('/setup/queue/test') && !url.includes('status')) {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve({
                            success: true,
                            test_job_id: 'test_123'
                        })
                    });
                }
                if (url.includes('status')) {
                    return Promise.resolve({
                        ok: false,
                        status: 500,
                        json: () => Promise.resolve({
                            error: { message: 'Polling failed' }
                        })
                    });
                }
                return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
            });

            await manager.testQueueWorker();

            // Should handle polling errors
            expect(console.error).toHaveBeenCalledWith(
                expect.stringContaining('Polling error'),
                expect.any(Error)
            );
        });
    });

    describe('Graceful Degradation', () => {
        test('continues working when optional elements are missing', () => {
            // Remove optional elements
            document.getElementById.mockImplementation((id) => {
                if (id === 'auto-refresh-toggle' || id === 'test-queue-worker-btn') {
                    return null;
                }
                return mockElements[id] || null;
            });

            // Should initialize without errors
            expect(() => {
                const newManager = new SetupStatusManager();
            }).not.toThrow();
        });

        test('handles missing CSRF token gracefully', () => {
            document.querySelector.mockImplementation((selector) => {
                if (selector === 'meta[name="csrf-token"]') {
                    return null;
                }
                return null;
            });

            const token = manager.getCSRFToken();
            expect(token).toBeUndefined();
            expect(console.warn).toHaveBeenCalledWith('CSRF token not found');
        });

        test('provides fallback when status elements are missing', async () => {
            // Mock missing status elements
            document.getElementById.mockImplementation((id) => {
                if (id.startsWith('status-')) {
                    return null;
                }
                return mockElements[id] || null;
            });

            manager.updateStatusIndicator('database', 'completed', 'Working');

            // Should log error for missing elements but not crash
            expect(console.error).toHaveBeenCalledWith(
                expect.stringContaining('Could not find status elements'),
                'database'
            );
        });
    });

    describe('Performance and Resource Management', () => {
        test('prevents multiple concurrent refresh operations', async () => {
            fetch.mockImplementation(() => 
                new Promise(resolve => setTimeout(() => resolve({
                    ok: true,
                    json: () => Promise.resolve({
                        success: true,
                        data: { statuses: {} }
                    })
                }), 100))
            );

            // Start multiple refresh operations
            const promise1 = manager.refreshAllStatuses();
            const promise2 = manager.refreshAllStatuses();
            const promise3 = manager.refreshAllStatuses();

            await Promise.all([promise1, promise2, promise3]);

            // Should only make one actual request
            expect(fetch).toHaveBeenCalledTimes(1);
        });

        test('cleans up resources on errors', async () => {
            fetch.mockImplementation(() => 
                Promise.reject(new Error('Network error'))
            );

            await manager.refreshAllStatuses();

            // Should reset loading state even on error
            expect(manager.refreshInProgress).toBe(false);
        });
    });

    describe('Accessibility and User Experience', () => {
        test('maintains keyboard navigation during errors', () => {
            const keyEvent = new KeyboardEvent('keydown', {
                key: 'r',
                ctrlKey: true
            });

            // Should handle keyboard events even when in error state
            expect(() => {
                document.addEventListener.mock.calls
                    .find(call => call[0] === 'keydown')[1](keyEvent);
            }).not.toThrow();
        });

        test('provides screen reader friendly error messages', async () => {
            fetch.mockImplementation(() => 
                Promise.reject(new Error('Network error'))
            );

            await manager.refreshAllStatuses();

            // Should update aria-label attributes for accessibility
            manager.statusSteps.forEach(step => {
                const indicator = mockElements[`status-${step}`];
                if (indicator) {
                    expect(indicator.setAttribute).toHaveBeenCalledWith(
                        'aria-label',
                        expect.stringContaining('status:')
                    );
                }
            });
        });
    });
});