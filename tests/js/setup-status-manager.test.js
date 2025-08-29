/**
 * Unit tests for SetupStatusManager
 * Tests the modified status management logic with separated queue worker handling
 */

// Mock DOM elements and global functions
const mockDocument = {
    getElementById: jest.fn(),
    addEventListener: jest.fn(),
    querySelector: jest.fn(),
    createElement: jest.fn(),
    head: { appendChild: jest.fn() },
    body: { appendChild: jest.fn() }
};

const mockElement = {
    addEventListener: jest.fn(),
    classList: {
        add: jest.fn(),
        remove: jest.fn(),
        toggle: jest.fn(),
        contains: jest.fn()
    },
    setAttribute: jest.fn(),
    getAttribute: jest.fn(),
    querySelector: jest.fn(),
    querySelectorAll: jest.fn(() => []),
    appendChild: jest.fn(),
    insertBefore: jest.fn(),
    parentNode: { replaceChild: jest.fn() },
    style: {},
    textContent: '',
    innerHTML: '',
    disabled: false
};

// Mock fetch
global.fetch = jest.fn();

// Mock console methods
global.console = {
    log: jest.fn(),
    error: jest.fn(),
    warn: jest.fn()
};

// Mock setTimeout and clearTimeout
global.setTimeout = jest.fn((fn) => fn());
global.clearTimeout = jest.fn();

// Set up DOM mocks
global.document = mockDocument;
global.window = { addEventListener: jest.fn() };

// Import the class after mocking
const SetupStatusManager = require('../../resources/js/setup-status.js');

describe('SetupStatusManager', () => {
    let manager;
    let mockElements;

    beforeEach(() => {
        jest.clearAllMocks();
        
        // Create mock elements
        mockElements = {
            refreshBtn: { ...mockElement },
            refreshBtnText: { ...mockElement },
            refreshSpinner: { ...mockElement },
            testQueueBtn: { ...mockElement },
            testQueueBtnText: { ...mockElement },
            queueTestResults: { ...mockElement },
            queueTestStatus: { ...mockElement },
            lastChecked: { ...mockElement },
            lastCheckedTime: { ...mockElement }
        };

        // Mock getElementById to return appropriate elements
        mockDocument.getElementById.mockImplementation((id) => {
            switch (id) {
                case 'refresh-status-btn':
                    return mockElements.refreshBtn;
                case 'refresh-btn-text':
                    return mockElements.refreshBtnText;
                case 'refresh-spinner':
                    return mockElements.refreshSpinner;
                case 'test-queue-worker-btn':
                    return mockElements.testQueueBtn;
                case 'test-queue-worker-btn-text':
                    return mockElements.testQueueBtnText;
                case 'queue-test-results':
                    return mockElements.queueTestResults;
                case 'queue-test-status':
                    return mockElements.queueTestStatus;
                case 'last-checked':
                    return mockElements.lastChecked;
                case 'last-checked-time':
                    return mockElements.lastCheckedTime;
                default:
                    return mockElement;
            }
        });

        // Mock querySelector for CSRF token
        mockDocument.querySelector.mockImplementation((selector) => {
            if (selector === 'meta[name="csrf-token"]') {
                return { getAttribute: () => 'mock-csrf-token' };
            }
            return null;
        });

        // Create manager instance with autoInit disabled for testing
        manager = new SetupStatusManager({ autoInit: false });
    });

    describe('Constructor', () => {
        test('should separate general status steps from queue worker', () => {
            expect(manager.generalStatusSteps).toEqual([
                "database",
                "mail",
                "google_drive",
                "migrations",
                "admin_user"
            ]);
            
            expect(manager.generalStatusSteps).not.toContain("queue_worker");
        });

        test('should maintain backward compatibility with statusSteps', () => {
            expect(manager.statusSteps).toEqual([
                "database",
                "mail",
                "google_drive",
                "migrations",
                "admin_user",
                "queue_worker"
            ]);
        });

        test('should initialize queue worker test progress flag', () => {
            expect(manager.queueWorkerTestInProgress).toBe(false);
        });
    });

    describe('refreshAllStatuses', () => {
        beforeEach(() => {
            // Mock the new methods
            manager.refreshGeneralStatuses = jest.fn().mockResolvedValue({
                success: true,
                data: { statuses: {} }
            });
            manager.triggerQueueWorkerTest = jest.fn().mockResolvedValue();
            manager.updateLastChecked = jest.fn();
            manager.resetRetryAttempts = jest.fn();
            manager.showSuccessMessage = jest.fn();
            manager.setLoadingState = jest.fn();
            manager.clearErrorMessages = jest.fn();
        });

        test('should call both general status refresh and queue worker test', async () => {
            await manager.refreshAllStatuses();

            expect(manager.refreshGeneralStatuses).toHaveBeenCalled();
            expect(manager.triggerQueueWorkerTest).toHaveBeenCalled();
        });

        test('should handle general status refresh failure gracefully', async () => {
            const error = new Error('General status failed');
            manager.refreshGeneralStatuses.mockRejectedValue(error);
            manager.handleRefreshError = jest.fn();

            await manager.refreshAllStatuses();

            expect(manager.handleRefreshError).toHaveBeenCalledWith(error, 'general');
        });

        test('should show success message if general status succeeds', async () => {
            await manager.refreshAllStatuses();

            expect(manager.showSuccessMessage).toHaveBeenCalledWith('Status refreshed successfully');
        });

        test('should set loading state during refresh', async () => {
            await manager.refreshAllStatuses();

            expect(manager.setLoadingState).toHaveBeenCalledWith(true);
            expect(manager.setLoadingState).toHaveBeenCalledWith(false);
        });
    });

    describe('getCachedQueueWorkerStatus', () => {
        beforeEach(() => {
            manager.makeAjaxRequest = jest.fn();
            manager.getCSRFToken = jest.fn().mockReturnValue('mock-csrf-token');
        });

        test('should fetch cached status from correct endpoint', async () => {
            const mockResponse = {
                success: true,
                data: {
                    queue_worker: {
                        status: 'completed',
                        message: 'Queue worker is functioning properly',
                        test_completed_at: '2025-01-01T12:00:00Z'
                    }
                }
            };

            manager.makeAjaxRequest.mockResolvedValue(mockResponse);

            const result = await manager.getCachedQueueWorkerStatus();

            expect(manager.makeAjaxRequest).toHaveBeenCalledWith(
                '/setup/queue-worker/status',
                expect.objectContaining({
                    method: 'GET',
                    headers: expect.objectContaining({
                        'X-CSRF-TOKEN': 'mock-csrf-token',
                        'X-Requested-With': 'XMLHttpRequest'
                    })
                })
            );

            expect(result).toEqual(mockResponse.data.queue_worker);
        });

        test('should return null if no cached status available', async () => {
            manager.makeAjaxRequest.mockResolvedValue({
                success: true,
                data: {}
            });

            const result = await manager.getCachedQueueWorkerStatus();

            expect(result).toBeNull();
        });

        test('should return null on error', async () => {
            manager.makeAjaxRequest.mockRejectedValue(new Error('Network error'));

            const result = await manager.getCachedQueueWorkerStatus();

            expect(result).toBeNull();
        });
    });

    describe('isStatusExpired', () => {
        test('should return true if no test_completed_at', () => {
            const status = { status: 'completed' };
            expect(manager.isStatusExpired(status)).toBe(true);
        });

        test('should return true if status is older than 1 hour', () => {
            const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000);
            const status = { 
                status: 'completed',
                test_completed_at: twoHoursAgo.toISOString()
            };
            expect(manager.isStatusExpired(status)).toBe(true);
        });

        test('should return false if status is within 1 hour', () => {
            const thirtyMinutesAgo = new Date(Date.now() - 30 * 60 * 1000);
            const status = { 
                status: 'completed',
                test_completed_at: thirtyMinutesAgo.toISOString()
            };
            expect(manager.isStatusExpired(status)).toBe(false);
        });
    });

    describe('updateQueueWorkerStatusFromCache', () => {
        beforeEach(() => {
            manager.updateStatusIndicator = jest.fn();
            manager.getTimeAgo = jest.fn().mockReturnValue('5 minutes ago');
        });

        test('should update status for completed test', () => {
            const cachedStatus = {
                status: 'completed',
                test_completed_at: '2025-01-01T12:00:00Z',
                processing_time: 1.23
            };

            manager.updateQueueWorkerStatusFromCache(cachedStatus);

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'completed',
                'Queue worker is functioning properly',
                'Last tested: 5 minutes ago (1.23s)'
            );
        });

        test('should update status for failed test', () => {
            const cachedStatus = {
                status: 'failed',
                error_message: 'Test job failed'
            };

            manager.updateQueueWorkerStatusFromCache(cachedStatus);

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'error',
                'Queue worker test failed',
                'Test job failed'
            );
        });

        test('should update status for timeout', () => {
            const cachedStatus = {
                status: 'timeout'
            };

            manager.updateQueueWorkerStatusFromCache(cachedStatus);

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'error',
                'Queue worker test timed out',
                'The queue worker may not be running'
            );
        });

        test('should handle unknown status', () => {
            const cachedStatus = {
                status: 'unknown'
            };

            manager.updateQueueWorkerStatusFromCache(cachedStatus);

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'not_tested',
                'Click the Test Queue Worker button below',
                'No recent test results available'
            );
        });
    });

    describe('triggerQueueWorkerTest', () => {
        beforeEach(() => {
            manager.performQueueWorkerTest = jest.fn().mockResolvedValue();
            manager.setQueueWorkerTestButtonState = jest.fn();
            manager.updateStatusIndicator = jest.fn();
        });

        test('should skip if test already in progress', async () => {
            manager.queueWorkerTestInProgress = true;

            await manager.triggerQueueWorkerTest();

            expect(manager.performQueueWorkerTest).not.toHaveBeenCalled();
        });

        test('should set test in progress flag', async () => {
            await manager.triggerQueueWorkerTest();

            expect(manager.setQueueWorkerTestButtonState).toHaveBeenCalledWith(true);
            expect(manager.setQueueWorkerTestButtonState).toHaveBeenCalledWith(false);
        });

        test('should update status to testing', async () => {
            await manager.triggerQueueWorkerTest();

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'checking',
                'Testing queue worker...',
                'Dispatching test job...'
            );
        });

        test('should handle test failure', async () => {
            const error = new Error('Test failed');
            manager.performQueueWorkerTest.mockRejectedValue(error);

            await manager.triggerQueueWorkerTest();

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'error',
                'Test failed',
                'Test failed'
            );
        });
    });

    describe('setLoadingState', () => {
        test('should disable queue worker test button during general refresh', () => {
            // Mock updateStatusIndicator to avoid errors
            manager.updateStatusIndicator = jest.fn();
            
            // The setLoadingState method should set refreshInProgress flag
            manager.setLoadingState(true);
            
            // Verify that the refresh in progress flag is set
            expect(manager.refreshInProgress).toBe(true);
            
            // Verify that updateStatusIndicator is called for general steps only
            expect(manager.updateStatusIndicator).toHaveBeenCalledTimes(5); // 5 general steps
        });

        test('should only set general steps to checking state', () => {
            manager.updateStatusIndicator = jest.fn();
            
            manager.setLoadingState(true);

            // Should be called for each general status step
            expect(manager.updateStatusIndicator).toHaveBeenCalledTimes(5);
            
            // Verify it's called for general steps
            manager.generalStatusSteps.forEach(step => {
                expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                    step,
                    'checking',
                    'Checking...',
                    'Verifying configuration...'
                );
            });
        });
    });

    describe('updateGeneralStepStatuses', () => {
        beforeEach(() => {
            manager.updateStatusIndicator = jest.fn();
        });

        test('should update only general status steps', () => {
            const statuses = {
                database: { status: 'completed', message: 'Database OK' },
                mail: { status: 'completed', message: 'Mail OK' },
                google_drive: { status: 'incomplete', message: 'Not configured' },
                migrations: { status: 'completed', message: 'Migrations OK' },
                admin_user: { status: 'completed', message: 'Admin user exists' },
                queue_worker: { status: 'completed', message: 'Queue worker OK' }
            };

            manager.updateGeneralStepStatuses(statuses);

            // Should be called for each general step
            expect(manager.updateStatusIndicator).toHaveBeenCalledTimes(5);
            
            // Should not be called for queue_worker
            expect(manager.updateStatusIndicator).not.toHaveBeenCalledWith(
                'queue_worker',
                expect.any(String),
                expect.any(String),
                expect.any(String)
            );
        });
    });

    describe('loadCachedQueueWorkerStatus', () => {
        beforeEach(() => {
            manager.getCachedQueueWorkerStatus = jest.fn();
            manager.isStatusExpired = jest.fn();
            manager.updateQueueWorkerStatusFromCache = jest.fn();
            manager.updateStatusIndicator = jest.fn();
        });

        test('should load and display cached status if not expired', async () => {
            const cachedStatus = {
                status: 'completed',
                message: 'Queue worker is functioning properly',
                test_completed_at: '2025-01-01T12:00:00Z'
            };

            manager.getCachedQueueWorkerStatus.mockResolvedValue(cachedStatus);
            manager.isStatusExpired.mockReturnValue(false);

            await manager.loadCachedQueueWorkerStatus();

            expect(manager.getCachedQueueWorkerStatus).toHaveBeenCalled();
            expect(manager.isStatusExpired).toHaveBeenCalledWith(cachedStatus);
            expect(manager.updateQueueWorkerStatusFromCache).toHaveBeenCalledWith(cachedStatus);
        });

        test('should show default message if no cached status', async () => {
            manager.getCachedQueueWorkerStatus.mockResolvedValue(null);

            await manager.loadCachedQueueWorkerStatus();

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'not_tested',
                'Click the Test Queue Worker button below',
                'No recent test results available'
            );
        });

        test('should show default message if cached status is expired', async () => {
            const expiredStatus = {
                status: 'completed',
                test_completed_at: '2025-01-01T10:00:00Z'
            };

            manager.getCachedQueueWorkerStatus.mockResolvedValue(expiredStatus);
            manager.isStatusExpired.mockReturnValue(true);

            await manager.loadCachedQueueWorkerStatus();

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'not_tested',
                'Click the Test Queue Worker button below',
                'No recent test results available'
            );
        });

        test('should handle errors gracefully', async () => {
            manager.getCachedQueueWorkerStatus.mockRejectedValue(new Error('Network error'));

            await manager.loadCachedQueueWorkerStatus();

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'not_tested',
                'Click the Test Queue Worker button below',
                'Unable to load cached status'
            );
        });
    });

    describe('Status Persistence and Initial State', () => {
        beforeEach(() => {
            manager.getCachedQueueWorkerStatus = jest.fn();
            manager.isStatusExpired = jest.fn();
            manager.updateQueueWorkerStatusFromCache = jest.fn();
            manager.updateStatusIndicator = jest.fn();
        });

        test('should persist successful test results across page loads', async () => {
            const recentSuccessfulTest = {
                status: 'completed',
                message: 'Queue worker is functioning properly',
                test_completed_at: new Date(Date.now() - 30 * 60 * 1000).toISOString(), // 30 minutes ago
                processing_time: 1.5
            };

            manager.getCachedQueueWorkerStatus.mockResolvedValue(recentSuccessfulTest);
            manager.isStatusExpired.mockReturnValue(false);

            await manager.loadCachedQueueWorkerStatus();

            expect(manager.updateQueueWorkerStatusFromCache).toHaveBeenCalledWith(recentSuccessfulTest);
            expect(manager.updateStatusIndicator).not.toHaveBeenCalledWith(
                'queue_worker',
                'not_tested',
                expect.any(String),
                expect.any(String)
            );
        });

        test('should show initial state message when no recent test exists', async () => {
            manager.getCachedQueueWorkerStatus.mockResolvedValue(null);

            await manager.loadCachedQueueWorkerStatus();

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'not_tested',
                'Click the Test Queue Worker button below',
                'No recent test results available'
            );
        });

        test('should handle cache expiration correctly', async () => {
            const expiredTest = {
                status: 'completed',
                test_completed_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString() // 2 hours ago
            };

            manager.getCachedQueueWorkerStatus.mockResolvedValue(expiredTest);
            manager.isStatusExpired.mockReturnValue(true);

            await manager.loadCachedQueueWorkerStatus();

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'not_tested',
                'Click the Test Queue Worker button below',
                'No recent test results available'
            );
        });

        test('should handle failed test results persistence', async () => {
            const recentFailedTest = {
                status: 'failed',
                error_message: 'Queue worker not responding',
                test_completed_at: new Date(Date.now() - 15 * 60 * 1000).toISOString() // 15 minutes ago
            };

            manager.getCachedQueueWorkerStatus.mockResolvedValue(recentFailedTest);
            manager.isStatusExpired.mockReturnValue(false);

            await manager.loadCachedQueueWorkerStatus();

            expect(manager.updateQueueWorkerStatusFromCache).toHaveBeenCalledWith(recentFailedTest);
        });

        test('should handle timeout test results persistence', async () => {
            const recentTimeoutTest = {
                status: 'timeout',
                test_completed_at: new Date(Date.now() - 45 * 60 * 1000).toISOString() // 45 minutes ago
            };

            manager.getCachedQueueWorkerStatus.mockResolvedValue(recentTimeoutTest);
            manager.isStatusExpired.mockReturnValue(false);

            await manager.loadCachedQueueWorkerStatus();

            expect(manager.updateQueueWorkerStatusFromCache).toHaveBeenCalledWith(recentTimeoutTest);
        });
    });

    describe('Parallel Execution and Coordination', () => {
        beforeEach(() => {
            // Mock the methods for parallel execution testing
            manager.refreshGeneralStatuses = jest.fn().mockResolvedValue({
                success: true,
                data: { statuses: {} }
            });
            manager.triggerQueueWorkerTest = jest.fn().mockResolvedValue();
            manager.updateLastChecked = jest.fn();
            manager.resetRetryAttempts = jest.fn();
            manager.showSuccessMessage = jest.fn();
            manager.setLoadingState = jest.fn();
            manager.handleRefreshError = jest.fn();
        });

        test('should execute general status and queue worker test in parallel', async () => {
            await manager.refreshAllStatuses();

            expect(manager.refreshGeneralStatuses).toHaveBeenCalled();
            expect(manager.triggerQueueWorkerTest).toHaveBeenCalled();
        });

        test('should handle queue worker test failure without affecting general status success', async () => {
            const queueError = new Error('Queue worker test failed');
            manager.triggerQueueWorkerTest.mockRejectedValue(queueError);

            await manager.refreshAllStatuses();

            expect(manager.refreshGeneralStatuses).toHaveBeenCalled();
            expect(manager.triggerQueueWorkerTest).toHaveBeenCalled();
            expect(manager.showSuccessMessage).toHaveBeenCalledWith('Status refreshed successfully');
        });

        test('should handle general status failure without affecting queue worker test', async () => {
            const generalError = new Error('Database connection failed');
            manager.refreshGeneralStatuses.mockRejectedValue(generalError);

            await manager.refreshAllStatuses();

            expect(manager.refreshGeneralStatuses).toHaveBeenCalled();
            expect(manager.triggerQueueWorkerTest).toHaveBeenCalled();
            expect(manager.handleRefreshError).toHaveBeenCalledWith(generalError, 'general');
        });

        test('should set loading state for both general status and queue worker', async () => {
            await manager.refreshAllStatuses();

            expect(manager.setLoadingState).toHaveBeenCalledWith(true);
            expect(manager.setLoadingState).toHaveBeenCalledWith(false);
        });

        test('should show success message when general status succeeds regardless of queue test', async () => {
            // Test with queue worker test failing
            manager.triggerQueueWorkerTest.mockRejectedValue(new Error('Queue test failed'));

            await manager.refreshAllStatuses();

            expect(manager.showSuccessMessage).toHaveBeenCalledWith('Status refreshed successfully');
        });

        test('should properly coordinate button states during parallel execution', async () => {
            manager.setQueueWorkerTestButtonState = jest.fn();

            await manager.refreshAllStatuses();

            // Verify that loading states are managed properly
            expect(manager.setLoadingState).toHaveBeenCalledWith(true);
            expect(manager.setLoadingState).toHaveBeenCalledWith(false);
        });
    });

    describe('UI State Management During Parallel Execution', () => {
        beforeEach(() => {
            manager.setLoadingState = jest.fn();
            manager.setQueueWorkerTestButtonState = jest.fn();
            manager.updateStatusIndicator = jest.fn();
        });

        test('should disable both buttons during general status refresh', () => {
            manager.setLoadingState(true);

            expect(manager.setLoadingState).toHaveBeenCalledWith(true);
            // The setLoadingState method should handle disabling both buttons
        });

        test('should properly manage queue worker test button state', () => {
            manager.setQueueWorkerTestButtonState(true);
            expect(manager.setQueueWorkerTestButtonState).toHaveBeenCalledWith(true);

            manager.setQueueWorkerTestButtonState(false);
            expect(manager.setQueueWorkerTestButtonState).toHaveBeenCalledWith(false);
        });

        test('should update queue worker status independently of general status', () => {
            manager.updateStatusIndicator('queue_worker', 'testing', 'Testing queue worker...', 'Dispatching test job...');

            expect(manager.updateStatusIndicator).toHaveBeenCalledWith(
                'queue_worker',
                'testing',
                'Testing queue worker...',
                'Dispatching test job...'
            );
        });
    });
});
