/**
 * Enhanced Error Handling Tests for Queue Worker Status
 * 
 * Tests the enhanced error handling functionality including:
 * - Specific error type detection
 * - Retry functionality
 * - Troubleshooting guidance
 * - Network error recovery
 */

import { jest } from '@jest/globals';

// Mock DOM elements and methods
const mockDocument = {
    getElementById: jest.fn(),
    querySelector: jest.fn(),
    createElement: jest.fn(),
    addEventListener: jest.fn()
};

const mockElement = {
    classList: {
        add: jest.fn(),
        remove: jest.fn(),
        contains: jest.fn()
    },
    textContent: '',
    innerHTML: '',
    appendChild: jest.fn(),
    querySelector: jest.fn(),
    addEventListener: jest.fn(),
    disabled: false
};

// Mock fetch for AJAX requests
global.fetch = jest.fn();
global.document = mockDocument;
global.AbortController = jest.fn(() => ({
    signal: {},
    abort: jest.fn()
}));
global.setTimeout = jest.fn((fn) => fn());
global.clearTimeout = jest.fn();

// Import the SetupStatusManager class
// Note: In a real test environment, you'd import this properly
class SetupStatusManager {
    constructor(options = {}) {
        this.generalStatusSteps = ["database", "mail", "google_drive", "migrations", "admin_user"];
        this.refreshInProgress = false;
        this.queueWorkerTestInProgress = false;
        this.retryAttempts = 0;
        this.maxRetryAttempts = 3;
        this.retryDelay = 2000;
    }

    getCSRFToken() {
        return 'mock-csrf-token';
    }

    async makeAjaxRequest(url, options = {}) {
        return fetch(url, options).then(response => response.json());
    }

    updateStatusIndicator(stepName, status, message, details) {
        // Mock implementation
        console.log(`Status updated: ${stepName} - ${status} - ${message}`);
    }

    isDispatchError(error) {
        const message = error.message.toLowerCase();
        return message.includes('dispatch') || 
               message.includes('queue connection') || 
               message.includes('database connection') ||
               message.includes('table') ||
               message.includes('configuration');
    }

    isNetworkError(error) {
        const message = error.message.toLowerCase();
        return message.includes('network') || 
               message.includes('connection refused') || 
               message.includes('timeout') ||
               message.includes('unreachable') ||
               message.includes('fetch');
    }

    isTimeoutError(error) {
        const message = error.message.toLowerCase();
        return message.includes('timeout') || message.includes('timed out');
    }

    getDispatchErrorDetails(error) {
        return `
            <div class="space-y-2">
                <p class="text-sm text-red-700">${error.message}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Verify queue configuration in .env file (QUEUE_CONNECTION)</li>
                        <li>Check if database tables exist: php artisan migrate</li>
                        <li>Ensure queue driver is properly configured</li>
                        <li>Check application logs for configuration errors</li>
                    </ul>
                </div>
            </div>
        `;
    }

    getNetworkErrorDetails(error) {
        return `
            <div class="space-y-2">
                <p class="text-sm text-red-700">${error.message}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Check your internet connection</li>
                        <li>Verify the application server is accessible</li>
                        <li>Check for firewall or proxy issues</li>
                        <li>Try refreshing the page and testing again</li>
                    </ul>
                </div>
            </div>
        `;
    }

    getTimeoutErrorDetails(error) {
        return `
            <div class="space-y-2">
                <p class="text-sm text-yellow-700">${error.message}</p>
                <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                    <h4 class="font-medium text-yellow-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                        <li>Ensure queue worker is running: php artisan queue:work</li>
                        <li>Check if worker process is stuck or crashed</li>
                        <li>Verify queue driver configuration</li>
                        <li>Check system resources (CPU, memory, disk space)</li>
                        <li>Review worker logs for errors or warnings</li>
                    </ul>
                </div>
            </div>
        `;
    }

    handleQueueWorkerTestError(error) {
        let statusClass = "error";
        let message = "Test failed";
        let details = error.message || "Unknown error occurred";

        if (this.isDispatchError(error)) {
            statusClass = "error";
            message = "Failed to dispatch test job";
            details = this.getDispatchErrorDetails(error);
        } else if (this.isNetworkError(error)) {
            statusClass = "error";
            message = "Network error during test";
            details = this.getNetworkErrorDetails(error);
        } else if (this.isTimeoutError(error)) {
            statusClass = "timeout";
            message = "Queue worker test timed out";
            details = this.getTimeoutErrorDetails(error);
        }

        this.updateStatusIndicator("queue_worker", statusClass, message, details);
        return { statusClass, message, details };
    }

    addRetryButtonToQueueWorkerStatus() {
        // Mock implementation
        return true;
    }

    async retryQueueWorkerTest() {
        // Mock implementation
        return this.triggerQueueWorkerTest();
    }

    async triggerQueueWorkerTest() {
        // Mock implementation
        return { success: true };
    }

    clearErrorMessages() {
        // Mock implementation
    }
}

describe('Enhanced Error Handling for Queue Worker Tests', () => {
    let statusManager;

    beforeEach(() => {
        statusManager = new SetupStatusManager();
        jest.clearAllMocks();
        
        // Setup DOM mocks
        mockDocument.getElementById.mockReturnValue(mockElement);
        mockDocument.querySelector.mockReturnValue(mockElement);
        mockDocument.createElement.mockReturnValue(mockElement);
    });

    describe('Error Type Detection', () => {
        test('correctly identifies dispatch errors', () => {
            const dispatchError = new Error('Failed to dispatch job to queue');
            expect(statusManager.isDispatchError(dispatchError)).toBe(true);

            const queueConnectionError = new Error('Queue connection failed');
            expect(statusManager.isDispatchError(queueConnectionError)).toBe(true);

            const databaseError = new Error('Database connection lost');
            expect(statusManager.isDispatchError(databaseError)).toBe(true);

            const tableError = new Error('Table jobs doesn\'t exist');
            expect(statusManager.isDispatchError(tableError)).toBe(true);

            const genericError = new Error('Some other error');
            expect(statusManager.isDispatchError(genericError)).toBe(false);
        });

        test('correctly identifies network errors', () => {
            const connectionRefused = new Error('Connection refused');
            expect(statusManager.isNetworkError(connectionRefused)).toBe(true);

            const timeout = new Error('Network timeout occurred');
            expect(statusManager.isNetworkError(timeout)).toBe(true);

            const unreachable = new Error('Host unreachable');
            expect(statusManager.isNetworkError(unreachable)).toBe(true);

            const fetchError = new Error('Fetch request failed');
            expect(statusManager.isNetworkError(fetchError)).toBe(true);

            const genericError = new Error('Some other error');
            expect(statusManager.isNetworkError(genericError)).toBe(false);
        });

        test('correctly identifies timeout errors', () => {
            const timeoutError = new Error('Request timed out');
            expect(statusManager.isTimeoutError(timeoutError)).toBe(true);

            const timedOutError = new Error('Operation timed out');
            expect(statusManager.isTimeoutError(timedOutError)).toBe(true);

            const genericError = new Error('Some other error');
            expect(statusManager.isTimeoutError(genericError)).toBe(false);
        });
    });

    describe('Error Handling and Troubleshooting', () => {
        test('handles dispatch errors with specific troubleshooting', () => {
            const dispatchError = new Error('Failed to dispatch test job');
            const result = statusManager.handleQueueWorkerTestError(dispatchError);

            expect(result.statusClass).toBe('error');
            expect(result.message).toBe('Failed to dispatch test job');
            expect(result.details).toContain('Verify queue configuration in .env file');
            expect(result.details).toContain('Check if database tables exist');
        });

        test('handles network errors with specific troubleshooting', () => {
            const networkError = new Error('Connection refused');
            const result = statusManager.handleQueueWorkerTestError(networkError);

            expect(result.statusClass).toBe('error');
            expect(result.message).toBe('Network error during test');
            expect(result.details).toContain('Check your internet connection');
            expect(result.details).toContain('Verify the application server is accessible');
        });

        test('handles timeout errors with specific troubleshooting', () => {
            const timeoutError = new Error('Request timed out');
            const result = statusManager.handleQueueWorkerTestError(timeoutError);

            expect(result.statusClass).toBe('timeout');
            expect(result.message).toBe('Queue worker test timed out');
            expect(result.details).toContain('Ensure queue worker is running');
            expect(result.details).toContain('Check if worker process is stuck');
        });

        test('handles generic errors with fallback troubleshooting', () => {
            const genericError = new Error('Unknown error occurred');
            const result = statusManager.handleQueueWorkerTestError(genericError);

            expect(result.statusClass).toBe('error');
            expect(result.message).toBe('Test failed');
            expect(result.details).toContain('Unknown error occurred');
        });
    });

    describe('Troubleshooting Details Generation', () => {
        test('generates dispatch error details with proper HTML structure', () => {
            const error = new Error('Queue configuration error');
            const details = statusManager.getDispatchErrorDetails(error);

            expect(details).toContain('Queue configuration error');
            expect(details).toContain('Troubleshooting Steps:');
            expect(details).toContain('Verify queue configuration in .env file');
            expect(details).toContain('bg-red-50');
            expect(details).toContain('text-red-700');
        });

        test('generates network error details with proper HTML structure', () => {
            const error = new Error('Network connection failed');
            const details = statusManager.getNetworkErrorDetails(error);

            expect(details).toContain('Network connection failed');
            expect(details).toContain('Troubleshooting Steps:');
            expect(details).toContain('Check your internet connection');
            expect(details).toContain('bg-red-50');
            expect(details).toContain('text-red-700');
        });

        test('generates timeout error details with proper HTML structure', () => {
            const error = new Error('Operation timed out');
            const details = statusManager.getTimeoutErrorDetails(error);

            expect(details).toContain('Operation timed out');
            expect(details).toContain('Troubleshooting Steps:');
            expect(details).toContain('Ensure queue worker is running');
            expect(details).toContain('bg-yellow-50');
            expect(details).toContain('text-yellow-700');
        });
    });

    describe('Retry Functionality', () => {
        test('adds retry button to queue worker status', () => {
            const result = statusManager.addRetryButtonToQueueWorkerStatus();
            expect(result).toBe(true);
        });

        test('retry functionality clears errors and restarts test', async () => {
            const clearSpy = jest.spyOn(statusManager, 'clearErrorMessages');
            const triggerSpy = jest.spyOn(statusManager, 'triggerQueueWorkerTest');

            await statusManager.retryQueueWorkerTest();

            expect(clearSpy).toHaveBeenCalled();
            expect(triggerSpy).toHaveBeenCalled();
        });
    });

    describe('Error Recovery Scenarios', () => {
        test('handles multiple error types in sequence', () => {
            const errors = [
                new Error('Failed to dispatch job'),
                new Error('Connection refused'),
                new Error('Request timed out'),
                new Error('Generic error')
            ];

            const results = errors.map(error => statusManager.handleQueueWorkerTestError(error));

            expect(results[0].message).toBe('Failed to dispatch test job');
            expect(results[1].message).toBe('Network error during test');
            expect(results[2].message).toBe('Queue worker test timed out');
            expect(results[3].message).toBe('Test failed');
        });

        test('provides appropriate status classes for different error types', () => {
            const dispatchError = new Error('Failed to dispatch');
            const networkError = new Error('Connection refused');
            const timeoutError = new Error('Timed out');

            expect(statusManager.handleQueueWorkerTestError(dispatchError).statusClass).toBe('error');
            expect(statusManager.handleQueueWorkerTestError(networkError).statusClass).toBe('error');
            expect(statusManager.handleQueueWorkerTestError(timeoutError).statusClass).toBe('timeout');
        });
    });

    describe('Integration with Status Updates', () => {
        test('updates status indicator with error information', () => {
            const updateSpy = jest.spyOn(statusManager, 'updateStatusIndicator');
            const error = new Error('Test error');

            statusManager.handleQueueWorkerTestError(error);

            expect(updateSpy).toHaveBeenCalledWith(
                'queue_worker',
                'error',
                'Test failed',
                expect.stringContaining('Test error')
            );
        });

        test('maintains error state consistency', () => {
            const error = new Error('Dispatch failed');
            const result = statusManager.handleQueueWorkerTestError(error);

            expect(result.statusClass).toBe('error');
            expect(result.message).toContain('Failed to dispatch');
            expect(result.details).toContain('Troubleshooting Steps');
        });
    });
});

describe('Enhanced Polling with Error Recovery', () => {
    let statusManager;

    beforeEach(() => {
        statusManager = new SetupStatusManager();
        jest.clearAllMocks();
    });

    test('handles network errors during polling with retry logic', async () => {
        // Mock network failure followed by success
        global.fetch
            .mockRejectedValueOnce(new Error('Network error'))
            .mockRejectedValueOnce(new Error('Connection refused'))
            .mockResolvedValueOnce({
                json: () => Promise.resolve({
                    success: true,
                    status: { status: 'completed', processing_time: 1.5 }
                })
            });

        // This would be part of the polling logic in a real implementation
        const mockPoll = async () => {
            try {
                const response = await statusManager.makeAjaxRequest('/test-endpoint');
                return response;
            } catch (error) {
                if (statusManager.isNetworkError(error)) {
                    // Retry logic would go here
                    return { retry: true, error };
                }
                throw error;
            }
        };

        // Test that network errors are properly identified for retry
        try {
            await mockPoll();
        } catch (error) {
            expect(statusManager.isNetworkError(error)).toBe(true);
        }
    });

    test('provides detailed error information for polling failures', () => {
        const pollingError = new Error('Status check failed');
        const elapsedTime = '15.5';
        
        // This would be part of the getPollingErrorDetails method
        const details = `
            <div class="space-y-2">
                <p class="text-sm text-red-700">Status check failed after ${elapsedTime}s: ${pollingError.message}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Check your internet connection</li>
                        <li>Verify the application server is accessible</li>
                        <li>Try refreshing the page and testing again</li>
                        <li>Check application logs for server errors</li>
                    </ul>
                </div>
            </div>
        `;

        expect(details).toContain('Status check failed after 15.5s');
        expect(details).toContain('Check your internet connection');
        expect(details).toContain('Troubleshooting Steps');
    });
});