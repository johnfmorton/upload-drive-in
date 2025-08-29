/**
 * Unit tests for QueueWorkerPollingService
 * 
 * Tests the performance-optimized polling service with exponential backoff,
 * adaptive intervals, and resource leak prevention.
 */

import QueueWorkerPollingService from '../../resources/js/queue-worker-polling.js';

// Mock fetch globally
global.fetch = jest.fn();

// Mock performance API
global.performance = {
    now: jest.fn(() => Date.now())
};

// Mock document for CSRF token
global.document = {
    querySelector: jest.fn(() => ({
        getAttribute: jest.fn(() => 'mock-csrf-token')
    }))
};

describe('QueueWorkerPollingService', () => {
    let pollingService;
    let mockOnStatusUpdate;
    let mockOnError;
    let mockOnMetricsUpdate;

    beforeEach(() => {
        // Reset fetch mock
        fetch.mockClear();
        
        // Create mock callbacks
        mockOnStatusUpdate = jest.fn();
        mockOnError = jest.fn();
        mockOnMetricsUpdate = jest.fn();
        
        // Create polling service instance
        pollingService = new QueueWorkerPollingService({
            baseInterval: 100,  // Faster for testing
            maxInterval: 1000,  // Lower max for testing
            requestTimeout: 5000,
            onStatusUpdate: mockOnStatusUpdate,
            onError: mockOnError,
            onMetricsUpdate: mockOnMetricsUpdate
        });
    });

    afterEach(() => {
        // Clean up any active polling
        pollingService.stopPolling();
        
        // Clear any pending timeouts
        jest.clearAllTimers();
    });

    describe('Initialization', () => {
        test('should initialize with default configuration', () => {
            const service = new QueueWorkerPollingService();
            
            expect(service.baseInterval).toBe(1000);
            expect(service.maxInterval).toBe(30000);
            expect(service.backoffMultiplier).toBe(1.5);
            expect(service.maxRetries).toBe(5);
        });

        test('should initialize with custom configuration', () => {
            const service = new QueueWorkerPollingService({
                baseInterval: 500,
                maxInterval: 10000,
                backoffMultiplier: 2.0,
                maxRetries: 3
            });
            
            expect(service.baseInterval).toBe(500);
            expect(service.maxInterval).toBe(10000);
            expect(service.backoffMultiplier).toBe(2.0);
            expect(service.maxRetries).toBe(3);
        });
    });

    describe('Polling Lifecycle', () => {
        test('should start polling with job ID', () => {
            const jobId = 'test_job_123';
            
            pollingService.startPolling(jobId);
            
            expect(pollingService.isPolling).toBe(true);
            expect(pollingService.jobId).toBe(jobId);
            expect(pollingService.retryCount).toBe(0);
        });

        test('should stop previous polling when starting new poll', () => {
            pollingService.startPolling('job_1');
            expect(pollingService.jobId).toBe('job_1');
            
            pollingService.startPolling('job_2');
            expect(pollingService.jobId).toBe('job_2');
        });

        test('should stop polling and clean up resources', () => {
            pollingService.startPolling('test_job');
            pollingService.stopPolling();
            
            expect(pollingService.isPolling).toBe(false);
            expect(pollingService.jobId).toBeNull();
            expect(pollingService.retryCount).toBe(0);
        });
    });

    describe('Successful Polling', () => {
        test('should handle successful response and call status update callback', async () => {
            const mockResponse = {
                status: 'testing',
                message: 'Test job is running',
                test_job_id: 'test_job_123'
            };

            fetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue(mockResponse)
            });

            pollingService.startPolling('test_job_123');
            
            // Wait for the poll to complete
            await new Promise(resolve => setTimeout(resolve, 50));

            expect(mockOnStatusUpdate).toHaveBeenCalledWith(mockResponse);
            expect(mockOnMetricsUpdate).toHaveBeenCalled();
        });

        test('should stop polling on terminal status', async () => {
            const mockResponse = {
                status: 'completed',
                message: 'Test completed successfully',
                processing_time: 1.5
            };

            fetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue(mockResponse)
            });

            pollingService.startPolling('test_job_123');
            
            // Wait for the poll to complete
            await new Promise(resolve => setTimeout(resolve, 50));

            expect(pollingService.isPolling).toBe(false);
        });

        test('should continue polling on non-terminal status', async () => {
            const mockResponse = {
                status: 'processing',
                message: 'Test job is processing'
            };

            fetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue(mockResponse)
            });

            pollingService.startPolling('test_job_123');
            
            // Wait for the poll to complete
            await new Promise(resolve => setTimeout(resolve, 50));

            // Should still be polling
            expect(pollingService.isPolling).toBe(true);
        });
    });

    describe('Error Handling and Exponential Backoff', () => {
        test('should handle network errors with exponential backoff', async () => {
            const networkError = new Error('Network error');
            fetch.mockRejectedValue(networkError);

            pollingService.startPolling('test_job_123');
            
            // Wait for error handling
            await new Promise(resolve => setTimeout(resolve, 50));

            expect(mockOnError).toHaveBeenCalledWith(networkError, 1);
            expect(pollingService.retryCount).toBe(1);
            expect(pollingService.consecutiveErrors).toBe(1);
        });

        test('should calculate exponential backoff intervals correctly', () => {
            pollingService.retryCount = 1;
            const interval1 = pollingService.calculateBackoffInterval();
            
            pollingService.retryCount = 2;
            const interval2 = pollingService.calculateBackoffInterval();
            
            pollingService.retryCount = 3;
            const interval3 = pollingService.calculateBackoffInterval();

            // Each interval should be larger than the previous (with some jitter tolerance)
            expect(interval2).toBeGreaterThan(interval1 * 0.9); // Allow for jitter
            expect(interval3).toBeGreaterThan(interval2 * 0.9);
        });

        test('should stop polling after max retries', async () => {
            const error = new Error('Persistent error');
            fetch.mockRejectedValue(error);

            // Set low max retries for testing
            pollingService.maxRetries = 2;
            pollingService.startPolling('test_job_123');
            
            // Wait for multiple retry attempts
            await new Promise(resolve => setTimeout(resolve, 200));

            expect(pollingService.isPolling).toBe(false);
            expect(pollingService.retryCount).toBe(2);
        });

        test('should handle HTTP error responses', async () => {
            fetch.mockResolvedValueOnce({
                ok: false,
                status: 500,
                statusText: 'Internal Server Error'
            });

            pollingService.startPolling('test_job_123');
            
            await new Promise(resolve => setTimeout(resolve, 50));

            expect(mockOnError).toHaveBeenCalled();
            const errorCall = mockOnError.mock.calls[0];
            expect(errorCall[0].message).toContain('HTTP 500');
        });
    });

    describe('Adaptive Polling Intervals', () => {
        test('should use status-specific intervals', () => {
            const testCases = [
                { status: 'testing', expectedInterval: 1000 },
                { status: 'completed', expectedInterval: 30000 },
                { status: 'failed', expectedInterval: 5000 },
                { status: 'timeout', expectedInterval: 10000 },
                { status: 'not_tested', expectedInterval: 60000 }
            ];

            testCases.forEach(({ status, expectedInterval }) => {
                const interval = pollingService.calculateNextInterval(status);
                expect(interval).toBe(expectedInterval);
            });
        });

        test('should fall back to base interval for unknown status', () => {
            const interval = pollingService.calculateNextInterval('unknown_status');
            expect(interval).toBe(pollingService.baseInterval);
        });

        test('should allow custom intervals via options', () => {
            const customIntervals = {
                'testing': 500,
                'processing': 2000
            };

            pollingService.startPolling('test_job', { intervals: customIntervals });

            expect(pollingService.calculateNextInterval('testing')).toBe(500);
            expect(pollingService.calculateNextInterval('processing')).toBe(2000);
        });
    });

    describe('Performance Metrics', () => {
        test('should track request metrics', async () => {
            fetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue({ status: 'completed' })
            });

            pollingService.startPolling('test_job_123');
            
            await new Promise(resolve => setTimeout(resolve, 50));

            const metrics = pollingService.getMetrics();
            
            expect(metrics.totalRequests).toBe(1);
            expect(metrics.successfulRequests).toBe(1);
            expect(metrics.failedRequests).toBe(0);
            expect(metrics.successRate).toBe(100);
        });

        test('should track response times', async () => {
            // Mock performance.now to return predictable values
            let callCount = 0;
            performance.now.mockImplementation(() => {
                callCount++;
                return callCount === 1 ? 1000 : 1100; // 100ms response time
            });

            fetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValue({ status: 'completed' })
            });

            pollingService.startPolling('test_job_123');
            
            await new Promise(resolve => setTimeout(resolve, 50));

            const metrics = pollingService.getMetrics();
            
            expect(metrics.responseTimes).toHaveLength(1);
            expect(metrics.averageResponseTime).toBe(100);
        });

        test('should limit response time history', async () => {
            // Simulate many requests
            for (let i = 0; i < 150; i++) {
                pollingService.updateResponseTimeMetrics(100 + i);
            }

            const metrics = pollingService.getMetrics();
            
            // Should keep only last 100 response times
            expect(metrics.responseTimes).toHaveLength(100);
        });

        test('should calculate success rate correctly', async () => {
            // Simulate mixed success/failure
            pollingService.metrics.totalRequests = 10;
            pollingService.metrics.successfulRequests = 7;
            pollingService.metrics.failedRequests = 3;

            const metrics = pollingService.getMetrics();
            
            expect(metrics.successRate).toBe(70);
        });
    });

    describe('Resource Management', () => {
        test('should abort ongoing requests when stopping', () => {
            const mockAbort = jest.fn();
            pollingService.abortController = { abort: mockAbort };
            
            pollingService.stopPolling();
            
            expect(mockAbort).toHaveBeenCalled();
        });

        test('should handle request timeout', async () => {
            // Mock a request that never resolves
            fetch.mockImplementation(() => new Promise(() => {}));

            pollingService.requestTimeout = 100; // Short timeout for testing
            pollingService.startPolling('test_job_123');
            
            await new Promise(resolve => setTimeout(resolve, 150));

            expect(mockOnError).toHaveBeenCalled();
            const errorCall = mockOnError.mock.calls[0];
            expect(errorCall[0].name).toBe('AbortError');
        });

        test('should clean up timeouts when stopping', () => {
            const clearTimeoutSpy = jest.spyOn(global, 'clearTimeout');
            
            pollingService.startPolling('test_job_123');
            pollingService.pollTimeoutId = 12345; // Mock timeout ID
            pollingService.stopPolling();
            
            expect(clearTimeoutSpy).toHaveBeenCalledWith(12345);
        });
    });

    describe('Status Information', () => {
        test('should provide current polling status', () => {
            pollingService.startPolling('test_job_123');
            
            const status = pollingService.getStatus();
            
            expect(status.isPolling).toBe(true);
            expect(status.jobId).toBe('test_job_123');
            expect(status.currentInterval).toBe(pollingService.baseInterval);
            expect(status.retryCount).toBe(0);
            expect(status.consecutiveErrors).toBe(0);
            expect(status.metrics).toBeDefined();
        });

        test('should reset metrics when requested', () => {
            pollingService.metrics.totalRequests = 10;
            pollingService.metrics.successfulRequests = 8;
            
            pollingService.resetMetrics();
            
            expect(pollingService.metrics.totalRequests).toBe(0);
            expect(pollingService.metrics.successfulRequests).toBe(0);
        });
    });

    describe('CSRF Token Handling', () => {
        test('should retrieve CSRF token from meta tag', () => {
            const token = pollingService.getCSRFToken();
            expect(token).toBe('mock-csrf-token');
        });

        test('should handle missing CSRF token gracefully', () => {
            document.querySelector.mockReturnValueOnce(null);
            
            const token = pollingService.getCSRFToken();
            expect(token).toBe('');
        });
    });
});