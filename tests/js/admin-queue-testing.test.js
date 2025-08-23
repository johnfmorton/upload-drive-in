/**
 * JavaScript tests for Admin Queue Testing functionality
 * 
 * These tests verify the core queue testing functionality including:
 * - Test job dispatch and monitoring
 * - Real-time progress tracking
 * - Queue health metrics display
 * - Historical test results
 * - Error handling and retry logic
 */

// Import the class to test
const AdminQueueTesting = require('../../resources/js/admin-queue-testing.js');

describe('AdminQueueTesting - Core Functionality', () => {
    let queueTesting;
    let mockFetch;
    let mockDocument;
    let mockLocalStorage;

    beforeEach(() => {
        // Reset all mocks
        jest.clearAllMocks();
        
        // Mock fetch
        mockFetch = jest.fn();
        global.fetch = mockFetch;
        
        // Mock localStorage
        mockLocalStorage = {
            getItem: jest.fn(() => null),
            setItem: jest.fn(),
            removeItem: jest.fn()
        };
        global.localStorage = mockLocalStorage;
        
        // Mock document with minimal required elements
        mockDocument = {
            getElementById: jest.fn((id) => {
                // Return mock elements for required IDs
                const mockElement = {
                    addEventListener: jest.fn(),
                    disabled: false,
                    textContent: '',
                    className: '',
                    classList: { add: jest.fn(), remove: jest.fn() },
                    insertBefore: jest.fn(),
                    appendChild: jest.fn(),
                    removeChild: jest.fn(),
                    children: [],
                    innerHTML: '',
                    style: {},
                    querySelector: jest.fn()
                };
                return mockElement;
            }),
            querySelector: jest.fn((selector) => {
                if (selector === 'meta[name="csrf-token"]') {
                    return { getAttribute: () => 'test-csrf-token' };
                }
                return null;
            }),
            createElement: jest.fn(() => ({
                className: '',
                innerHTML: '',
                style: {},
                classList: { add: jest.fn(), remove: jest.fn() },
                addEventListener: jest.fn(),
                remove: jest.fn()
            })),
            body: { appendChild: jest.fn() }
        };
        global.document = mockDocument;
        
        // Mock other globals
        global.setTimeout = jest.fn((fn) => { fn(); return 'timeout-id'; });
        global.setInterval = jest.fn(() => 'interval-id');
        global.clearInterval = jest.fn();
        global.clearTimeout = jest.fn();
        global.confirm = jest.fn(() => true);
        global.Date.now = jest.fn(() => 1640995200000);
        global.console = { log: jest.fn(), error: jest.fn(), warn: jest.fn() };

        // Create new instance for each test
        queueTesting = new AdminQueueTesting();
    });

    describe('Initialization', () => {
        test('should initialize with correct default values', () => {
            expect(queueTesting.currentTestJobId).toBeNull();
            expect(queueTesting.testStartTime).toBeNull();
            expect(queueTesting.pollingInterval).toBeNull();
            expect(queueTesting.elapsedTimeInterval).toBeNull();
            expect(Array.isArray(queueTesting.testHistory)).toBe(true);
        });

        test('should have required methods', () => {
            expect(typeof queueTesting.startQueueTest).toBe('function');
            expect(typeof queueTesting.checkTestJobStatus).toBe('function');
            expect(typeof queueTesting.loadQueueHealth).toBe('function');
            expect(typeof queueTesting.stopTest).toBe('function');
            expect(typeof queueTesting.displayTestResult).toBe('function');
        });
    });

    describe('Queue Test Dispatch', () => {
        test('should dispatch test job successfully', async () => {
            const mockResponse = {
                success: true,
                test_job_id: 'test-job-123'
            };

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValueOnce(mockResponse)
            });

            await queueTesting.dispatchTestJob();

            expect(mockFetch).toHaveBeenCalledWith('/admin/queue/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': 'test-csrf-token'
                },
                body: JSON.stringify({
                    delay: 0
                })
            });
        });

        test('should handle test job dispatch failure', async () => {
            const mockError = new Error('Dispatch failed');
            mockFetch.mockRejectedValueOnce(mockError);

            await expect(queueTesting.dispatchTestJob()).rejects.toThrow('Dispatch failed');
        });

        test('should prevent multiple concurrent tests', async () => {
            queueTesting.currentTestJobId = 'existing-test';

            await queueTesting.startQueueTest();

            expect(global.console.warn).toHaveBeenCalledWith('Test already in progress');
        });
    });

    describe('Real-time Progress Tracking', () => {
        beforeEach(() => {
            queueTesting.currentTestJobId = 'test-job-123';
            queueTesting.testStartTime = 1640995200000;
        });

        test('should check test job status via polling', async () => {
            const mockStatusResponse = {
                success: true,
                status: {
                    status: 'processing',
                    processing_time: null,
                    completed_at: null
                }
            };

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValueOnce(mockStatusResponse)
            });

            await queueTesting.checkTestJobStatus();

            expect(mockFetch).toHaveBeenCalledWith(
                '/admin/queue/test/status?test_job_id=test-job-123',
                {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': 'test-csrf-token'
                    }
                }
            );
        });

        test('should handle polling errors gracefully', async () => {
            const mockError = new Error('Polling failed');
            mockFetch.mockRejectedValueOnce(mockError);

            await queueTesting.checkTestJobStatus();

            expect(global.console.error).toHaveBeenCalledWith('Polling error:', mockError);
        });
    });

    describe('Test Result Handling', () => {
        test('should handle successful test completion', () => {
            const status = {
                status: 'completed',
                processing_time: 1.5,
                completed_at: '2022-01-01T12:00:00Z'
            };

            queueTesting.currentTestJobId = 'test-job-123';
            queueTesting.testStartTime = 1640995200000;

            queueTesting.handleTestSuccess(status);

            expect(queueTesting.currentTestJobId).toBeNull();
        });

        test('should handle test failure', () => {
            const status = {
                status: 'failed',
                error_message: 'Job execution failed',
                failed_at: '2022-01-01T12:00:00Z'
            };

            queueTesting.currentTestJobId = 'test-job-123';
            queueTesting.handleTestFailure(status);

            expect(queueTesting.currentTestJobId).toBeNull();
        });

        test('should handle test timeout', () => {
            queueTesting.currentTestJobId = 'test-job-123';
            queueTesting.handleTestTimeout();

            expect(queueTesting.currentTestJobId).toBeNull();
        });

        test('should create test result elements with correct styling', () => {
            const mockElement = {
                className: '',
                innerHTML: '',
                style: {}
            };
            mockDocument.createElement.mockReturnValue(mockElement);

            const successResult = {
                status: 'success',
                message: 'Test completed successfully',
                timestamp: Date.now(),
                details: { processing_time: 1.5, total_time: 2.0 }
            };

            const element = queueTesting.createTestResultElement(successResult);

            expect(element.className).toContain('bg-green-50');
            expect(element.innerHTML).toContain('Test completed successfully');
        });
    });

    describe('Queue Health Monitoring', () => {
        test('should load queue health successfully', async () => {
            const mockHealthResponse = {
                success: true,
                metrics: {
                    status: 'healthy',
                    recent_jobs_count: 15,
                    failed_jobs_count: 2
                }
            };

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValueOnce(mockHealthResponse)
            });

            await queueTesting.loadQueueHealth();

            expect(mockFetch).toHaveBeenCalledWith('/admin/queue/health', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': 'test-csrf-token'
                }
            });
        });

        test('should handle queue health loading failure', async () => {
            const mockError = new Error('Health check failed');
            mockFetch.mockRejectedValueOnce(mockError);

            await queueTesting.loadQueueHealth();

            expect(global.console.error).toHaveBeenCalledWith('Failed to load queue health:', mockError);
        });
    });

    describe('Test History Management', () => {
        test('should load test history from localStorage', () => {
            const mockHistory = [
                { status: 'success', message: 'Test 1', timestamp: Date.now() - 1000 },
                { status: 'failed', message: 'Test 2', timestamp: Date.now() - 2000 }
            ];

            mockLocalStorage.getItem.mockReturnValue(JSON.stringify(mockHistory));

            const history = queueTesting.loadTestHistory();

            expect(mockLocalStorage.getItem).toHaveBeenCalledWith('admin_queue_test_history');
            expect(history).toEqual(mockHistory);
        });

        test('should handle corrupted localStorage data', () => {
            mockLocalStorage.getItem.mockReturnValue('invalid-json');

            const history = queueTesting.loadTestHistory();

            expect(global.console.error).toHaveBeenCalledWith('Failed to load test history:', expect.any(Error));
            expect(history).toEqual([]);
        });

        test('should add test result to history', () => {
            const result = {
                status: 'success',
                message: 'Test completed',
                timestamp: Date.now()
            };

            queueTesting.addToTestHistory(result);

            expect(queueTesting.testHistory[0]).toEqual(result);
            expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
                'admin_queue_test_history',
                expect.any(String)
            );
        });

        test('should limit history to 10 results', () => {
            // Fill history with 10 items
            queueTesting.testHistory = Array(10).fill().map((_, i) => ({
                status: 'success',
                message: `Test ${i}`,
                timestamp: Date.now() - i * 1000
            }));

            const newResult = {
                status: 'success',
                message: 'New test',
                timestamp: Date.now()
            };

            queueTesting.addToTestHistory(newResult);

            expect(queueTesting.testHistory.length).toBe(10);
            expect(queueTesting.testHistory[0]).toEqual(newResult);
        });

        test('should clear test history', () => {
            queueTesting.testHistory = [
                { status: 'success', message: 'Test', timestamp: Date.now() }
            ];

            queueTesting.clearTestHistory();

            expect(global.confirm).toHaveBeenCalledWith('Are you sure you want to clear the test history?');
            expect(queueTesting.testHistory).toEqual([]);
            expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
                'admin_queue_test_history',
                '[]'
            );
        });
    });

    describe('Cleanup and State Management', () => {
        test('should stop test and cleanup intervals', () => {
            queueTesting.pollingInterval = 'polling-interval';
            queueTesting.elapsedTimeInterval = 'elapsed-interval';
            queueTesting.currentTestJobId = 'test-job-123';

            queueTesting.stopTest();

            expect(global.clearInterval).toHaveBeenCalledWith('polling-interval');
            expect(global.clearInterval).toHaveBeenCalledWith('elapsed-interval');
            expect(queueTesting.currentTestJobId).toBeNull();
            expect(queueTesting.testStartTime).toBeNull();
        });

        test('should handle missing DOM elements gracefully', () => {
            mockDocument.getElementById.mockReturnValue(null);

            // These should not throw errors
            expect(() => {
                queueTesting.updateProgressMessage('Test message');
                queueTesting.setTestInProgress(true);
                queueTesting.displayTestResult({ status: 'success', message: 'Test', timestamp: Date.now() });
            }).not.toThrow();
        });
    });

    describe('Error Handling and Edge Cases', () => {
        test('should handle network timeouts', async () => {
            const timeoutError = new Error('Request timeout');
            timeoutError.name = 'AbortError';
            mockFetch.mockRejectedValueOnce(timeoutError);

            await queueTesting.startQueueTest();

            expect(global.console.error).toHaveBeenCalledWith('Queue test failed:', timeoutError);
        });

        test('should handle malformed JSON responses', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockRejectedValueOnce(new Error('Invalid JSON'))
            });

            await queueTesting.startQueueTest();

            expect(global.console.error).toHaveBeenCalledWith('Queue test failed:', expect.any(Error));
        });

        test('should handle localStorage errors', () => {
            mockLocalStorage.setItem.mockImplementation(() => {
                throw new Error('Storage quota exceeded');
            });

            const result = { status: 'success', message: 'Test', timestamp: Date.now() };
            
            queueTesting.addToTestHistory(result);

            expect(global.console.error).toHaveBeenCalledWith('Failed to save test history:', expect.any(Error));
        });
    });

    describe('Animation and Visual Feedback', () => {
        test('should create result elements with appropriate animations', () => {
            const mockElement = {
                className: '',
                innerHTML: '',
                style: {}
            };
            mockDocument.createElement.mockReturnValue(mockElement);

            const results = [
                { status: 'success', message: 'Success', timestamp: Date.now() },
                { status: 'failed', message: 'Failed', timestamp: Date.now() },
                { status: 'timeout', message: 'Timeout', timestamp: Date.now() },
                { status: 'error', message: 'Error', timestamp: Date.now() }
            ];

            results.forEach(result => {
                const element = queueTesting.createTestResultElement(result);
                
                switch (result.status) {
                    case 'success':
                        expect(element.className).toContain('bg-green-50');
                        break;
                    case 'failed':
                    case 'error':
                        expect(element.className).toContain('bg-red-50');
                        break;
                    case 'timeout':
                        expect(element.className).toContain('bg-yellow-50');
                        break;
                }
            });
        });

        test('should add animation classes to result elements', () => {
            const mockElement = {
                classList: { add: jest.fn(), remove: jest.fn() },
                style: {}
            };

            queueTesting.addResultAnimation(mockElement, 'success');
            expect(mockElement.classList.add).toHaveBeenCalledWith('animate-success-glow');

            queueTesting.addResultAnimation(mockElement, 'error');
            expect(mockElement.classList.add).toHaveBeenCalledWith('animate-error-shake');

            queueTesting.addResultAnimation(mockElement, 'timeout');
            expect(mockElement.classList.add).toHaveBeenCalledWith('animate-warning-pulse');
        });

        test('should animate result removal', () => {
            const mockElement = {
                style: {},
                parentNode: {
                    removeChild: jest.fn()
                }
            };

            queueTesting.animateResultRemoval(mockElement);

            expect(mockElement.style.opacity).toBe('0');
            expect(mockElement.style.transform).toBe('translateX(100%)');
        });
    });
});

describe('AdminQueueTesting - Integration Tests', () => {
    test('should complete full test workflow', async () => {
        // Setup mocks
        global.fetch = jest.fn();
        global.document = {
            getElementById: jest.fn(() => ({
                addEventListener: jest.fn(),
                disabled: false,
                textContent: '',
                classList: { add: jest.fn(), remove: jest.fn() },
                style: {}
            })),
            querySelector: jest.fn(() => ({ getAttribute: () => 'test-csrf-token' })),
            createElement: jest.fn(() => ({ className: '', innerHTML: '', style: {} })),
            body: { appendChild: jest.fn() }
        };
        global.localStorage = { getItem: jest.fn(() => null), setItem: jest.fn() };
        global.setTimeout = jest.fn((fn) => { fn(); return 'timeout-id'; });
        global.setInterval = jest.fn(() => 'interval-id');
        global.clearInterval = jest.fn();
        global.Date.now = jest.fn(() => 1640995200000);
        global.console = { log: jest.fn(), error: jest.fn(), warn: jest.fn() };

        const queueTesting = new AdminQueueTesting();
        
        // Mock successful dispatch
        global.fetch.mockResolvedValueOnce({
            ok: true,
            json: jest.fn().mockResolvedValueOnce({
                success: true,
                test_job_id: 'test-job-123'
            })
        });

        // Mock successful status check
        global.fetch.mockResolvedValueOnce({
            ok: true,
            json: jest.fn().mockResolvedValueOnce({
                success: true,
                status: {
                    status: 'completed',
                    processing_time: 1.5,
                    completed_at: '2022-01-01T12:00:00Z'
                }
            })
        });

        // Start test
        await queueTesting.startQueueTest();
        
        // Verify test was started
        expect(queueTesting.currentTestJobId).toBe('test-job-123');
        
        // Simulate polling
        await queueTesting.checkTestJobStatus();
        
        // Verify test was completed
        expect(queueTesting.currentTestJobId).toBeNull();
    });
});

describe('Module Export', () => {
    test('should export AdminQueueTesting class', () => {
        expect(AdminQueueTesting).toBeDefined();
        expect(typeof AdminQueueTesting).toBe('function');
        expect(AdminQueueTesting.prototype.constructor).toBe(AdminQueueTesting);
    });
});