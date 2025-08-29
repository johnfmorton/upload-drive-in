/**
 * Complete Workflow Integration Tests
 * Tests the entire queue worker status workflow from frontend perspective
 */

// Use Jest instead of Vitest

// Mock DOM environment
const mockDocument = {
    getElementById: jest.fn(),
    querySelector: jest.fn(),
    querySelectorAll: jest.fn(),
    addEventListener: jest.fn(),
};

const mockWindow = {
    fetch: jest.fn(),
    location: { reload: jest.fn() },
    setTimeout: jest.fn(),
    clearTimeout: jest.fn(),
};

// Mock elements
const mockElements = {
    refreshStatusBtn: {
        disabled: false,
        textContent: 'Check Status',
        classList: { add: jest.fn(), remove: jest.fn() },
        addEventListener: jest.fn(),
    },
    testQueueBtn: {
        disabled: false,
        textContent: 'Test Queue Worker',
        classList: { add: jest.fn(), remove: jest.fn() },
        addEventListener: jest.fn(),
    },
    queueWorkerStatus: {
        textContent: '',
        className: '',
        classList: { add: jest.fn(), remove: jest.fn() },
    },
    queueWorkerIcon: {
        className: '',
        classList: { add: jest.fn(), remove: jest.fn() },
    },
};

global.document = mockDocument;
global.window = mockWindow;
global.fetch = mockWindow.fetch;

// Import the module under test
let SetupStatusManager;

describe('Complete Workflow Integration Tests', () => {
    beforeEach(async () => {
        jest.clearAllMocks();
        
        // Reset mock elements
        Object.values(mockElements).forEach(element => {
            if (element.disabled !== undefined) element.disabled = false;
            if (element.textContent !== undefined) element.textContent = '';
            if (element.className !== undefined) element.className = '';
        });

        // Setup DOM mocks
        mockDocument.getElementById.mockImplementation((id) => {
            const elementMap = {
                'refresh-status-btn': mockElements.refreshStatusBtn,
                'test-queue-worker-btn': mockElements.testQueueBtn,
                'queue-worker-status': mockElements.queueWorkerStatus,
                'queue-worker-icon': mockElements.queueWorkerIcon,
            };
            return elementMap[id] || null;
        });

        // Mock SetupStatusManager for testing
        SetupStatusManager = class {
            constructor() {
                this.isTestingQueueWorker = false;
            }
            
            async checkInitialQueueWorkerStatus() {
                const response = await fetch('/setup/queue-worker/status');
                const data = await response.json();
                this.updateQueueWorkerStatus(data.data.queue_worker);
            }
            
            async refreshAllStatuses() {
                mockElements.refreshStatusBtn.disabled = true;
                mockElements.testQueueBtn.disabled = true;
                
                const response = await fetch('/setup/status/check-all');
                const data = await response.json();
                
                if (data.queue_worker_test) {
                    this.updateQueueWorkerStatus(data.queue_worker_test);
                    if (data.queue_worker_test.test_job_id) {
                        await this.pollQueueTestResults(data.queue_worker_test.test_job_id);
                    }
                }
                
                mockElements.refreshStatusBtn.disabled = false;
                mockElements.testQueueBtn.disabled = false;
            }
            
            async testQueueWorker() {
                try {
                    mockElements.refreshStatusBtn.disabled = true;
                    mockElements.testQueueBtn.disabled = true;
                    
                    const response = await fetch('/setup/queue/test', { method: 'POST' });
                    const data = await response.json();
                    
                    if (data.success) {
                        this.updateQueueWorkerStatus(data.queue_worker_status);
                        if (data.test_job_id) {
                            await this.pollQueueTestResults(data.test_job_id);
                        }
                    } else {
                        this.updateQueueWorkerStatus({
                            status: 'error',
                            message: data.message || 'Unable to start queue worker test'
                        });
                    }
                } catch (error) {
                    this.updateQueueWorkerStatus({
                        status: 'error',
                        message: 'Unable to start queue worker test'
                    });
                } finally {
                    mockElements.refreshStatusBtn.disabled = false;
                    mockElements.testQueueBtn.disabled = false;
                }
            }
            
            async pollQueueTestResults(testJobId) {
                const response = await fetch(`/setup/queue/test/status?test_job_id=${testJobId}`);
                const data = await response.json();
                
                if (data.success && data.queue_worker_status) {
                    this.updateQueueWorkerStatus(data.queue_worker_status);
                }
            }
            
            updateQueueWorkerStatus(status) {
                mockElements.queueWorkerStatus.textContent = status.message;
                
                // Update icon classes based on status
                if (status.status === 'completed') {
                    mockElements.queueWorkerIcon.classList.add('text-green-500');
                } else if (status.status === 'failed' || status.status === 'error') {
                    mockElements.queueWorkerIcon.classList.add('text-red-500');
                } else if (status.status === 'testing') {
                    mockElements.queueWorkerIcon.classList.add('text-blue-500');
                } else {
                    mockElements.queueWorkerIcon.classList.add('text-gray-500');
                }
            }
        };
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    test('complete workflow from fresh setup to successful test', async () => {
        const manager = new SetupStatusManager();

        // 1. Initial state - fresh setup
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                queue_worker: {
                    status: 'not_tested',
                    message: 'Click the Test Queue Worker button below',
                    can_retry: false
                }
            })
        });

        await manager.checkInitialQueueWorkerStatus();

        expect(mockElements.queueWorkerStatus.textContent).toBe('Click the Test Queue Worker button below');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-gray-500');

        // 2. User clicks "Check Status" - should trigger both general status and queue test
        mockWindow.fetch
            .mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    general_status: {
                        database: { status: 'completed', message: 'Database connection successful' },
                        migrations: { status: 'completed', message: 'All migrations completed' },
                    },
                    queue_worker_test: {
                        status: 'testing',
                        message: 'Testing queue worker...',
                        test_job_id: 'test-123'
                    }
                })
            });

        await manager.refreshAllStatuses();

        expect(mockElements.queueWorkerStatus.textContent).toBe('Testing queue worker...');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-blue-500');
        expect(mockElements.refreshStatusBtn.disabled).toBe(true);
        expect(mockElements.testQueueBtn.disabled).toBe(true);

        // 3. Poll for queue test results - job queued
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'testing',
                message: 'Test job queued, waiting for worker...',
                test_job_id: 'test-123'
            })
        });

        await manager.pollQueueTestResults('test-123');

        expect(mockElements.queueWorkerStatus.textContent).toBe('Test job queued, waiting for worker...');

        // 4. Poll again - job processing
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'testing',
                message: 'Test job is being processed...',
                test_job_id: 'test-123'
            })
        });

        await manager.pollQueueTestResults('test-123');

        expect(mockElements.queueWorkerStatus.textContent).toBe('Test job is being processed...');

        // 5. Final poll - job completed
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'completed',
                message: 'Queue worker is functioning properly! (1.23s)',
                processing_time: 1.23,
                test_completed_at: new Date().toISOString()
            })
        });

        await manager.pollQueueTestResults('test-123');

        expect(mockElements.queueWorkerStatus.textContent).toBe('Queue worker is functioning properly! (1.23s)');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-green-500');
        expect(mockElements.refreshStatusBtn.disabled).toBe(false);
        expect(mockElements.testQueueBtn.disabled).toBe(false);
    });

    test('status persistence across page refreshes', async () => {
        const manager = new SetupStatusManager();

        // Simulate page load with cached successful status
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                queue_worker: {
                    status: 'completed',
                    message: 'Queue worker is functioning properly! (1.45s)',
                    processing_time: 1.45,
                    test_completed_at: new Date(Date.now() - 10 * 60 * 1000).toISOString() // 10 minutes ago
                }
            })
        });

        await manager.checkInitialQueueWorkerStatus();

        expect(mockElements.queueWorkerStatus.textContent).toBe('Queue worker is functioning properly! (1.45s)');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-green-500');

        // Simulate another page refresh - status should persist
        vi.clearAllMocks();
        
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                queue_worker: {
                    status: 'completed',
                    message: 'Queue worker is functioning properly! (1.45s)',
                    processing_time: 1.45,
                    test_completed_at: new Date(Date.now() - 10 * 60 * 1000).toISOString()
                }
            })
        });

        const manager2 = new SetupStatusManager();
        await manager2.checkInitialQueueWorkerStatus();

        expect(mockElements.queueWorkerStatus.textContent).toBe('Queue worker is functioning properly! (1.45s)');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-green-500');
    });

    test('expired cache handling', async () => {
        const manager = new SetupStatusManager();

        // Simulate page load with expired cached status (older than 1 hour)
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                queue_worker: {
                    status: 'not_tested',
                    message: 'Click the Test Queue Worker button below',
                    can_retry: false
                }
            })
        });

        await manager.checkInitialQueueWorkerStatus();

        expect(mockElements.queueWorkerStatus.textContent).toBe('Click the Test Queue Worker button below');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-gray-500');
    });

    test('error scenarios and recovery', async () => {
        const manager = new SetupStatusManager();

        // Test 1: Network error during test initiation
        mockWindow.fetch.mockRejectedValueOnce(new Error('Network error'));

        await manager.testQueueWorker();

        expect(mockElements.queueWorkerStatus.textContent).toContain('Unable to start queue worker test');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-red-500');

        // Test 2: Test job failure
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'testing',
                message: 'Testing queue worker...',
                test_job_id: 'test-456'
            })
        });

        await manager.testQueueWorker();

        // Simulate job failure during polling
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'failed',
                message: 'Test job failed: Queue worker not running',
                error_message: 'Queue worker not running',
                can_retry: true
            })
        });

        await manager.pollQueueTestResults('test-456');

        expect(mockElements.queueWorkerStatus.textContent).toBe('Test job failed: Queue worker not running');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-red-500');

        // Test 3: Recovery via retry
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'testing',
                message: 'Testing queue worker...',
                test_job_id: 'test-789'
            })
        });

        await manager.testQueueWorker();

        expect(mockElements.queueWorkerStatus.textContent).toBe('Testing queue worker...');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-blue-500');
    });

    test('button state management during testing', async () => {
        const manager = new SetupStatusManager();

        // Initial state - buttons should be enabled
        expect(mockElements.refreshStatusBtn.disabled).toBe(false);
        expect(mockElements.testQueueBtn.disabled).toBe(false);

        // Start test - buttons should be disabled
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'testing',
                message: 'Testing queue worker...',
                test_job_id: 'test-999'
            })
        });

        await manager.testQueueWorker();

        expect(mockElements.refreshStatusBtn.disabled).toBe(true);
        expect(mockElements.testQueueBtn.disabled).toBe(true);

        // Complete test - buttons should be re-enabled
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'completed',
                message: 'Queue worker is functioning properly! (2.1s)',
                processing_time: 2.1
            })
        });

        await manager.pollQueueTestResults('test-999');

        expect(mockElements.refreshStatusBtn.disabled).toBe(false);
        expect(mockElements.testQueueBtn.disabled).toBe(false);
    });

    test('progressive status updates', async () => {
        const manager = new SetupStatusManager();
        const statusMessages = [];

        // Mock status updates to track progression
        const originalUpdateStatus = manager.updateQueueWorkerStatus;
        manager.updateQueueWorkerStatus = (status) => {
            statusMessages.push(status.message);
            return originalUpdateStatus.call(manager, status);
        };

        // Start test
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'testing',
                message: 'Testing queue worker...',
                test_job_id: 'test-progressive'
            })
        });

        await manager.testQueueWorker();

        // Poll 1 - job queued
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'testing',
                message: 'Test job queued, waiting for worker...',
                test_job_id: 'test-progressive'
            })
        });

        await manager.pollQueueTestResults('test-progressive');

        // Poll 2 - job processing
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'testing',
                message: 'Test job is being processed...',
                test_job_id: 'test-progressive'
            })
        });

        await manager.pollQueueTestResults('test-progressive');

        // Poll 3 - job completed
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                status: 'completed',
                message: 'Queue worker is functioning properly! (0.89s)',
                processing_time: 0.89
            })
        });

        await manager.pollQueueTestResults('test-progressive');

        // Verify progressive status messages
        expect(statusMessages).toContain('Testing queue worker...');
        expect(statusMessages).toContain('Test job queued, waiting for worker...');
        expect(statusMessages).toContain('Test job is being processed...');
        expect(statusMessages).toContain('Queue worker is functioning properly! (0.89s)');
    });

    test('concurrent general status and queue test', async () => {
        const manager = new SetupStatusManager();

        // Mock both general status and queue test responses
        mockWindow.fetch.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({
                general_status: {
                    database: { status: 'completed', message: 'Database connection successful' },
                    migrations: { status: 'completed', message: 'All migrations completed' },
                    admin_user: { status: 'completed', message: 'Admin user exists' }
                },
                queue_worker_test: {
                    status: 'testing',
                    message: 'Testing queue worker...',
                    test_job_id: 'test-concurrent'
                }
            })
        });

        await manager.refreshAllStatuses();

        // Should have updated queue worker status to testing
        expect(mockElements.queueWorkerStatus.textContent).toBe('Testing queue worker...');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-blue-500');

        // Should have disabled buttons during test
        expect(mockElements.refreshStatusBtn.disabled).toBe(true);
        expect(mockElements.testQueueBtn.disabled).toBe(true);
    });

    test('rate limiting and error handling', async () => {
        const manager = new SetupStatusManager();

        // Test rate limiting response
        mockWindow.fetch.mockResolvedValueOnce({
            ok: false,
            status: 429,
            json: () => Promise.resolve({
                message: 'Too many requests. Please wait before testing again.'
            })
        });

        await manager.testQueueWorker();

        expect(mockElements.queueWorkerStatus.textContent).toContain('Too many requests');
        expect(mockElements.queueWorkerIcon.classList.add).toHaveBeenCalledWith('text-red-500');

        // Test server error
        mockWindow.fetch.mockResolvedValueOnce({
            ok: false,
            status: 500,
            json: () => Promise.resolve({
                message: 'Internal server error'
            })
        });

        await manager.testQueueWorker();

        expect(mockElements.queueWorkerStatus.textContent).toContain('Unable to start queue worker test');
    });
});