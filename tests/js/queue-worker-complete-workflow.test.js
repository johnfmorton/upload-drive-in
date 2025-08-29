/**
 * Comprehensive JavaScript tests for complete queue worker status workflow.
 * 
 * Tests the end-to-end JavaScript functionality including status management,
 * error handling, button states, and user interactions.
 */

// Mock Shoelace components
jest.mock('@shoelace-style/shoelace/dist/components/alert/alert.js', () => ({}));
jest.mock('@shoelace-style/shoelace/dist/components/icon/icon.js', () => ({}));
jest.mock('@shoelace-style/shoelace/dist/components/button/button.js', () => ({}));

// Mock QueueWorkerPollingService
const mockPollingService = {
    start: jest.fn(),
    stop: jest.fn(),
    updateConfig: jest.fn(),
    getMetrics: jest.fn(() => ({ averageResponseTime: 100, errorRate: 0 }))
};

jest.mock('../../resources/js/queue-worker-polling.js', () => {
    return jest.fn().mockImplementation(() => mockPollingService);
});

// Import the class after mocking dependencies
const SetupStatusManager = require('../../resources/js/setup-status.js').default || 
                          require('../../resources/js/setup-status.js');

describe('Queue Worker Complete Workflow Tests', () => {
    let statusManager;
    let mockFetch;
    let mockDocument;
    let mockConsole;
    let mockElements;

    beforeEach(() => {
        // Reset all mocks
        jest.clearAllMocks();
        
        // Mock global fetch
        mockFetch = jest.fn();
        global.fetch = mockFetch;
        
        // Mock console methods
        mockConsole = {
            log: jest.fn(),
            error: jest.fn(),
            warn: jest.fn()
        };
        global.console = mockConsole;
        
        // Mock DOM elements
        mockElements = new Map();
        
        // Create mock elements for all status steps
        const statusSteps = ['database', 'mail', 'google_drive', 'migrations', 'admin_user', 'queue_worker'];
        statusSteps.forEach(step => {
            mockElements.set(`${step}-status`, {
                textContent: '',
                className: '',
                classList: { add: jest.fn(), remove: jest.fn() },
                style: { display: '' }
            });
        });
        
        // Create mock buttons
        mockElements.set('refresh-status-btn', {
            disabled: false,
            classList: { add: jest.fn(), remove: jest.fn() },
            addEventListener: jest.fn(),
            removeEventListener: jest.fn()
        });
        
        mockElements.set('test-queue-worker-btn', {
            disabled: false,
            classList: { add: jest.fn(), remove: jest.fn() },
            addEventListener: jest.fn(),
            removeEventListener: jest.fn()
        });
        
        // Mock document
        mockDocument = {
            querySelector: jest.fn((selector) => {
                if (selector === 'meta[name="csrf-token"]') {
                    return { getAttribute: () => 'test-csrf-token' };
                }
                return mockElements.get(selector) || null;
            }),
            querySelectorAll: jest.fn(() => []),
            getElementById: jest.fn((id) => mockElements.get(id) || null),
            createElement: jest.fn(() => ({
                name: '',
                content: '',
                setAttribute: jest.fn(),
                getAttribute: jest.fn()
            })),
            head: { appendChild: jest.fn() },
            addEventListener: jest.fn(),
            removeEventListener: jest.fn()
        };
        global.document = mockDocument;
        
        // Mock window
        global.window = {
            location: { reload: jest.fn() },
            addEventListener: jest.fn(),
            removeEventListener: jest.fn()
        };
        
        // Mock localStorage
        global.localStorage = {
            getItem: jest.fn(),
            setItem: jest.fn(),
            removeItem: jest.fn()
        };
        
        // Mock setTimeout and clearTimeout
        global.setTimeout = jest.fn((callback, delay) => {
            callback();
            return 'timeout-id';
        });
        global.clearTimeout = jest.fn();
        
        // Create status manager instance
        statusManager = new SetupStatusManager({ autoInit: false });
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    describe('Complete Fresh Setup Workflow', () => {
        test('should handle complete fresh setup to successful test workflow', async () => {
            // Step 1: Initial page load - check cached status
            const initialStatusResponse = {
                ok: true,
                json: () => Promise.resolve({
                    status: 'not_tested',
                    message: 'Click the Test Queue Worker button below',
                    can_retry: true
                })
            };
            
            mockFetch.mockResolvedValueOnce(initialStatusResponse);
            
            await statusManager.loadCachedQueueWorkerStatus();
            
            const queueElement = mockElements.get('queue_worker-status');
            expect(queueElement.textContent).toBe('Click the Test Queue Worker button below');
            expect(queueElement.classList.add).toHaveBeenCalledWith('text-gray-600');

            // Step 2: User clicks "Check Status" - should refresh general status and trigger queue test
            const generalStatusResponse = {
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    statuses: {
                        database: { status: 'success', message: 'Database connected' },
                        mail: { status: 'success', message: 'Mail configured' },
                        google_drive: { status: 'success', message: 'Google Drive configured' },
                        migrations: { status: 'success', message: 'Migrations complete' },
                        admin_user: { status: 'success', message: 'Admin user exists' }
                    }
                })
            };
            
            const queueTestResponse = {
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    job_id: 'test-job-123',
                    message: 'Test job dispatched successfully'
                })
            };
            
            mockFetch
                .mockResolvedValueOnce(generalStatusResponse)
                .mockResolvedValueOnce(queueTestResponse);
            
            await statusManager.refreshAllStatuses();
            
            // Verify general status refresh
            expect(mockFetch).toHaveBeenCalledWith('/setup/status/refresh', expect.objectContaining({
                method: 'POST',
                headers: expect.objectContaining({
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': 'test-csrf-token'
                }),
                body: JSON.stringify({
                    steps: ['database', 'mail', 'google_drive', 'migrations', 'admin_user']
                })
            }));
            
            // Verify queue worker test was triggered
            expect(mockFetch).toHaveBeenCalledWith('/setup/queue-worker/test', expect.any(Object));

            // Step 3: Simulate progressive status updates during test
            const progressUpdates = [
                { status: 'testing', message: 'Testing queue worker...' },
                { status: 'processing', message: 'Test job queued, waiting for worker...' },
                { status: 'processing', message: 'Test job is being processed...' }
            ];
            
            for (const update of progressUpdates) {
                statusManager.updateQueueWorkerStatus(update);
                expect(queueElement.textContent).toBe(update.message);
            }

            // Step 4: Test completion
            const completedStatus = {
                status: 'completed',
                message: 'Queue worker is functioning properly',
                processing_time: 1.23
            };
            
            statusManager.updateQueueWorkerStatus(completedStatus);
            
            expect(queueElement.textContent).toContain('Queue worker is functioning properly');
            expect(queueElement.textContent).toContain('1.23s');
            expect(queueElement.classList.add).toHaveBeenCalledWith('text-green-600');
        });
    });

    describe('Error Scenarios and Recovery Workflow', () => {
        test('should handle dispatch failure and recovery workflow', async () => {
            // Step 1: Attempt to start test - dispatch fails
            const dispatchFailureResponse = {
                ok: true,
                json: () => Promise.resolve({
                    success: false,
                    message: 'Failed to dispatch test job: Queue connection failed',
                    error_details: 'Connection to Redis failed'
                })
            };
            
            mockFetch.mockResolvedValueOnce(dispatchFailureResponse);
            
            await statusManager.triggerQueueWorkerTest();
            
            const queueElement = mockElements.get('queue_worker-status');
            expect(queueElement.textContent).toContain('Failed to dispatch test job');
            expect(queueElement.classList.add).toHaveBeenCalledWith('text-red-600');

            // Step 2: Check status shows error with troubleshooting
            const errorStatusResponse = {
                ok: true,
                json: () => Promise.resolve({
                    status: 'failed',
                    message: 'Failed to dispatch test job',
                    error_message: 'Queue connection failed',
                    troubleshooting: [
                        'Check queue configuration in .env file',
                        'Verify QUEUE_CONNECTION setting',
                        'Ensure Redis/database is running'
                    ],
                    can_retry: true
                })
            };
            
            mockFetch.mockResolvedValueOnce(errorStatusResponse);
            
            await statusManager.loadCachedQueueWorkerStatus();
            
            expect(queueElement.textContent).toContain('Failed to dispatch test job');
            expect(statusManager.lastErrorDetails).toBeDefined();

            // Step 3: User clicks retry - should work this time
            const retrySuccessResponse = {
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    job_id: 'retry-job-456',
                    message: 'Test job dispatched successfully'
                })
            };
            
            mockFetch.mockResolvedValueOnce(retrySuccessResponse);
            
            await statusManager.triggerQueueWorkerTest();
            
            expect(queueElement.textContent).toContain('Testing queue worker');
            expect(queueElement.classList.add).toHaveBeenCalledWith('text-blue-600');
        });

        test('should handle timeout scenario with appropriate messaging', async () => {
            // Step 1: Start test successfully
            const testStartResponse = {
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    job_id: 'timeout-job-789',
                    message: 'Test job dispatched'
                })
            };
            
            mockFetch.mockResolvedValueOnce(testStartResponse);
            
            await statusManager.triggerQueueWorkerTest();

            // Step 2: Simulate timeout after polling
            const timeoutStatusResponse = {
                ok: true,
                json: () => Promise.resolve({
                    status: 'timeout',
                    message: 'Queue worker test timed out after 30 seconds',
                    troubleshooting: [
                        'Queue worker may not be running',
                        'Run: php artisan queue:work',
                        'Check if jobs are being processed'
                    ],
                    can_retry: true
                })
            };
            
            mockFetch.mockResolvedValueOnce(timeoutStatusResponse);
            
            // Simulate timeout handling
            statusManager.handleTestTimeout('timeout-job-789');
            
            const queueElement = mockElements.get('queue_worker-status');
            expect(queueElement.textContent).toContain('timed out');
            expect(queueElement.classList.add).toHaveBeenCalledWith('text-yellow-600');
        });

        test('should handle network errors gracefully', async () => {
            // Step 1: Network error during status refresh
            const networkError = new Error('Network request failed');
            mockFetch.mockRejectedValueOnce(networkError);
            
            await statusManager.refreshGeneralStatuses();
            
            expect(mockConsole.error).toHaveBeenCalledWith('Error refreshing general statuses:', networkError);

            // Step 2: Network error during queue test
            mockFetch.mockRejectedValueOnce(networkError);
            
            await statusManager.triggerQueueWorkerTest();
            
            expect(mockConsole.error).toHaveBeenCalledWith('Error triggering queue worker test:', networkError);
            
            const queueElement = mockElements.get('queue_worker-status');
            expect(queueElement.textContent).toContain('Error testing queue worker');
        });
    });

    describe('Button State Management During Operations', () => {
        test('should manage button states correctly during concurrent operations', async () => {
            const refreshBtn = mockElements.get('refresh-status-btn');
            const queueBtn = mockElements.get('test-queue-worker-btn');
            
            // Step 1: Start general status refresh
            statusManager.refreshInProgress = true;
            statusManager.updateButtonStates();
            
            expect(refreshBtn.disabled).toBe(true);
            expect(queueBtn.disabled).toBe(true);

            // Step 2: Start queue worker test while refresh is running
            statusManager.queueWorkerTestInProgress = true;
            statusManager.updateButtonStates();
            
            expect(refreshBtn.disabled).toBe(true);
            expect(queueBtn.disabled).toBe(true);

            // Step 3: Complete general refresh but queue test still running
            statusManager.refreshInProgress = false;
            statusManager.updateButtonStates();
            
            expect(refreshBtn.disabled).toBe(true); // Still disabled due to queue test
            expect(queueBtn.disabled).toBe(true);

            // Step 4: Complete queue test
            statusManager.queueWorkerTestInProgress = false;
            statusManager.updateButtonStates();
            
            expect(refreshBtn.disabled).toBe(false);
            expect(queueBtn.disabled).toBe(false);
        });

        test('should prevent rapid button clicks with debouncing', async () => {
            const now = Date.now();
            statusManager.lastRefreshTime = now - 500; // 500ms ago
            statusManager.debounceDelay = 1000; // 1 second debounce
            
            // Mock Date.now to return consistent time
            const originalDateNow = Date.now;
            Date.now = jest.fn(() => now);
            
            const shouldDebounce = statusManager.shouldDebounceRefresh();
            expect(shouldDebounce).toBe(true);
            
            // Test after debounce period
            Date.now = jest.fn(() => now + 1100);
            const shouldNotDebounce = statusManager.shouldDebounceRefresh();
            expect(shouldNotDebounce).toBe(false);
            
            // Restore original Date.now
            Date.now = originalDateNow;
        });

        test('should show loading indicators during operations', async () => {
            const refreshBtn = mockElements.get('refresh-status-btn');
            const queueBtn = mockElements.get('test-queue-worker-btn');
            
            // Start operation
            statusManager.showLoadingState();
            
            expect(refreshBtn.classList.add).toHaveBeenCalledWith('loading');
            expect(queueBtn.classList.add).toHaveBeenCalledWith('loading');
            
            // End operation
            statusManager.hideLoadingState();
            
            expect(refreshBtn.classList.remove).toHaveBeenCalledWith('loading');
            expect(queueBtn.classList.remove).toHaveBeenCalledWith('loading');
        });
    });

    describe('Status Persistence and Cache Management', () => {
        test('should persist status across page refreshes', async () => {
            // Step 1: Set up completed status
            const completedStatus = {
                status: 'completed',
                message: 'Queue worker is functioning properly',
                test_completed_at: new Date().toISOString(),
                processing_time: 2.34,
                can_retry: false
            };
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(completedStatus)
            });
            
            await statusManager.loadCachedQueueWorkerStatus();
            
            const queueElement = mockElements.get('queue_worker-status');
            expect(queueElement.textContent).toContain('functioning properly');
            expect(queueElement.textContent).toContain('2.34s');

            // Step 2: Simulate page refresh (reload cached status)
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(completedStatus)
            });
            
            await statusManager.loadCachedQueueWorkerStatus();
            
            // Should still show completed status
            expect(queueElement.textContent).toContain('functioning properly');
        });

        test('should handle expired cache correctly', async () => {
            const expiredStatus = {
                status: 'completed',
                message: 'Queue worker is functioning properly',
                test_completed_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(), // 2 hours ago
                processing_time: 1.0
            };
            
            const isExpired = statusManager.isStatusExpired(expiredStatus);
            expect(isExpired).toBe(true);
            
            // Should show not_tested for expired status
            const notTestedStatus = {
                status: 'not_tested',
                message: 'Click the Test Queue Worker button below',
                can_retry: true
            };
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(notTestedStatus)
            });
            
            await statusManager.loadCachedQueueWorkerStatus();
            
            const queueElement = mockElements.get('queue_worker-status');
            expect(queueElement.textContent).toBe('Click the Test Queue Worker button below');
        });
    });

    describe('Progressive Status Updates and Polling', () => {
        test('should handle progressive status updates correctly', async () => {
            const queueElement = mockElements.get('queue_worker-status');
            
            // Test different status phases
            const statusPhases = [
                { status: 'testing', message: 'Testing queue worker...', expectedClass: 'text-blue-600' },
                { status: 'processing', message: 'Test job queued, waiting for worker...', expectedClass: 'text-blue-600' },
                { status: 'processing', message: 'Test job is being processed...', expectedClass: 'text-blue-600' },
                { status: 'completed', message: 'Queue worker is functioning properly', processing_time: 1.5, expectedClass: 'text-green-600' }
            ];
            
            for (const phase of statusPhases) {
                statusManager.updateQueueWorkerStatus(phase);
                
                expect(queueElement.textContent).toContain(phase.message);
                expect(queueElement.classList.add).toHaveBeenCalledWith(phase.expectedClass);
                
                if (phase.processing_time) {
                    expect(queueElement.textContent).toContain(`${phase.processing_time}s`);
                }
            }
        });

        test('should integrate with polling service correctly', async () => {
            // Test polling status update
            const statusUpdate = {
                status: 'processing',
                message: 'Test job is being processed...',
                progress: 50
            };
            
            statusManager.handlePollingStatusUpdate(statusUpdate);
            
            expect(statusManager.queueWorkerTestInProgress).toBe(true);
            
            // Test polling completion
            const completionUpdate = {
                status: 'completed',
                message: 'Queue worker is functioning properly',
                processing_time: 2.1
            };
            
            statusManager.handlePollingStatusUpdate(completionUpdate);
            
            expect(statusManager.queueWorkerTestInProgress).toBe(false);
        });
    });

    describe('Error Message Display and User Guidance', () => {
        test('should display comprehensive error messages with troubleshooting', async () => {
            const errorStatus = {
                status: 'failed',
                message: 'Queue worker test failed',
                error_message: 'Database connection lost during job execution',
                troubleshooting: [
                    'Check database connection in .env file',
                    'Verify DB_CONNECTION setting',
                    'Check Laravel logs for detailed error information',
                    'Try running: php artisan queue:restart'
                ],
                can_retry: true
            };
            
            statusManager.updateQueueWorkerStatus(errorStatus);
            
            const queueElement = mockElements.get('queue_worker-status');
            expect(queueElement.textContent).toContain('Queue worker test failed');
            expect(queueElement.classList.add).toHaveBeenCalledWith('text-red-600');
            
            // Verify troubleshooting information is stored
            expect(statusManager.lastErrorDetails).toEqual(errorStatus);
        });

        test('should show retry options for failed tests', async () => {
            const failedStatus = {
                status: 'failed',
                message: 'Test failed',
                can_retry: true
            };
            
            statusManager.updateQueueWorkerStatus(failedStatus);
            
            // Should enable retry functionality
            expect(statusManager.canRetryTest).toBe(true);
            
            const queueBtn = mockElements.get('test-queue-worker-btn');
            expect(queueBtn.disabled).toBe(false);
        });
    });

    describe('Cleanup and Resource Management', () => {
        test('should cleanup resources properly on destroy', () => {
            statusManager.autoRefreshInterval = 'mock-interval';
            statusManager.pollingService = mockPollingService;
            
            global.clearInterval = jest.fn();
            
            statusManager.destroy();
            
            expect(global.clearInterval).toHaveBeenCalledWith('mock-interval');
            expect(mockPollingService.stop).toHaveBeenCalled();
            expect(statusManager.autoRefreshInterval).toBeNull();
        });

        test('should handle missing DOM elements gracefully', () => {
            // Mock getElementById to return null for missing elements
            mockDocument.getElementById.mockReturnValue(null);
            
            // Should not throw errors when elements are missing
            expect(() => {
                statusManager.updateQueueWorkerStatus({
                    status: 'completed',
                    message: 'Test message'
                });
            }).not.toThrow();
            
            expect(() => {
                statusManager.updateButtonStates();
            }).not.toThrow();
        });
    });

    describe('Integration with Setup Instructions Page', () => {
        test('should integrate properly with setup instructions workflow', async () => {
            // Test that status manager works within setup instructions context
            const setupInstructionsResponse = {
                ok: true,
                json: () => Promise.resolve({
                    setup_complete: false,
                    steps: {
                        database: { status: 'success' },
                        mail: { status: 'success' },
                        google_drive: { status: 'success' },
                        migrations: { status: 'success' },
                        admin_user: { status: 'success' },
                        queue_worker: { status: 'not_tested' }
                    }
                })
            };
            
            mockFetch.mockResolvedValueOnce(setupInstructionsResponse);
            
            // Should handle setup instructions context
            await statusManager.refreshAllStatuses();
            
            expect(mockFetch).toHaveBeenCalledWith('/setup/status/refresh', expect.any(Object));
        });
    });
});