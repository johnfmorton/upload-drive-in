/**
 * Core JavaScript tests for Admin Queue Testing functionality
 * 
 * These tests focus on the essential functionality without complex DOM mocking
 */

// Import the class to test
const AdminQueueTesting = require('../../resources/js/admin-queue-testing.js');

describe('AdminQueueTesting - Core Tests', () => {
    let queueTesting;

    beforeEach(() => {
        // Mock minimal globals needed for instantiation
        global.document = {
            getElementById: () => null,
            querySelector: () => null,
            createElement: () => ({ className: '', innerHTML: '', style: {} }),
            body: { appendChild: () => {} }
        };
        global.fetch = jest.fn();
        global.localStorage = { getItem: () => null, setItem: () => {} };
        global.setTimeout = jest.fn();
        global.setInterval = jest.fn();
        global.clearInterval = jest.fn();
        global.clearTimeout = jest.fn();
        global.confirm = jest.fn(() => true);
        global.Date.now = jest.fn(() => 1640995200000);
        global.console = { log: jest.fn(), error: jest.fn(), warn: jest.fn() };

        // Create instance
        queueTesting = new AdminQueueTesting();
    });

    describe('Basic Properties and Methods', () => {
        test('should initialize with correct default values', () => {
            expect(queueTesting.currentTestJobId).toBeNull();
            expect(queueTesting.testStartTime).toBeNull();
            expect(queueTesting.pollingInterval).toBeNull();
            expect(queueTesting.elapsedTimeInterval).toBeNull();
            expect(Array.isArray(queueTesting.testHistory)).toBe(true);
        });

        test('should have all required methods', () => {
            const requiredMethods = [
                'startQueueTest',
                'checkTestJobStatus',
                'loadQueueHealth',
                'stopTest',
                'displayTestResult',
                'handleTestSuccess',
                'handleTestFailure',
                'handleTestTimeout',
                'handleTestError',
                'createTestResultElement',
                'addResultAnimation',
                'animateResultRemoval',
                'loadTestHistory',
                'addToTestHistory',
                'clearTestHistory',
                'showDetailedError',
                'showSuccessNotification'
            ];

            requiredMethods.forEach(method => {
                expect(typeof queueTesting[method]).toBe('function');
            });
        });
    });

    describe('Test State Management', () => {
        test('should prevent concurrent tests', async () => {
            queueTesting.currentTestJobId = 'existing-test';

            await queueTesting.startQueueTest();

            expect(global.console.warn).toHaveBeenCalledWith('Test already in progress');
        });

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

        test('should handle test errors', () => {
            queueTesting.currentTestJobId = 'test-job-123';
            queueTesting.handleTestError('Network error');

            expect(queueTesting.currentTestJobId).toBeNull();
        });
    });

    describe('Visual Feedback and Animations', () => {
        test('should create test result elements with correct styling', () => {
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

        test('should create result elements with different status styles', () => {
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

    describe('Test History Management', () => {
        test('should add test result to history', () => {
            const result = {
                status: 'success',
                message: 'Test completed',
                timestamp: Date.now()
            };

            queueTesting.addToTestHistory(result);

            expect(queueTesting.testHistory[0]).toEqual(result);
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

        test('should clear test history when confirmed', () => {
            queueTesting.testHistory = [
                { status: 'success', message: 'Test', timestamp: Date.now() }
            ];

            queueTesting.clearTestHistory();

            expect(global.confirm).toHaveBeenCalledWith('Are you sure you want to clear the test history?');
            expect(queueTesting.testHistory).toEqual([]);
        });

        test('should not clear history if user cancels', () => {
            global.confirm = jest.fn(() => false);
            const originalHistory = [{ status: 'success', message: 'Test', timestamp: Date.now() }];
            queueTesting.testHistory = [...originalHistory];

            queueTesting.clearTestHistory();

            expect(queueTesting.testHistory).toEqual(originalHistory);
        });
    });

    describe('Utility Methods', () => {
        test('should create result details section with job details', () => {
            const result = {
                details: {
                    job_id: 'test-job-123',
                    error: 'Test error',
                    timeout_duration: 30
                }
            };

            const detailsSection = queueTesting.createResultDetailsSection(result);

            expect(detailsSection).toContain('Job ID: test-job-123');
            expect(detailsSection).toContain('Error: Test error');
            expect(detailsSection).toContain('Timeout: 30s');
        });

        test('should return empty string for result without details', () => {
            const result = { status: 'success', message: 'Test' };

            const detailsSection = queueTesting.createResultDetailsSection(result);

            expect(detailsSection).toBe('');
        });

        test('should update progress with animation', () => {
            const mockProgressElement = {
                style: { opacity: '1' },
                textContent: 'Old message'
            };
            queueTesting.testProgressMessage = mockProgressElement;

            queueTesting.updateProgressWithAnimation('New message');

            expect(mockProgressElement.style.opacity).toBe('0.5');
            expect(global.setTimeout).toHaveBeenCalled();
        });
    });

    describe('Error Handling', () => {
        test('should handle missing DOM elements gracefully', () => {
            // These should not throw errors even with null elements
            expect(() => {
                queueTesting.updateProgressMessage('Test message');
                queueTesting.setTestInProgress(true);
                queueTesting.displayTestResult({ status: 'success', message: 'Test', timestamp: Date.now() });
                queueTesting.showCurrentTestProgress();
                queueTesting.hideCurrentTestProgress();
            }).not.toThrow();
        });

        test('should handle animation on null elements gracefully', () => {
            expect(() => {
                queueTesting.animateResultRemoval(null);
                queueTesting.addResultAnimation(null, 'success');
            }).not.toThrow();
        });
    });

    describe('Real-time Progress Tracking', () => {
        test('should handle different job statuses correctly', () => {
            queueTesting.currentTestJobId = 'test-job-123';
            queueTesting.testStartTime = 1640995200000;

            // Test different status handling
            const statuses = [
                { status: 'pending' },
                { status: 'processing' },
                { status: 'completed', processing_time: 1.5 },
                { status: 'failed', error_message: 'Job failed' },
                { status: 'timeout' }
            ];

            statuses.forEach(status => {
                // Reset for each test
                queueTesting.currentTestJobId = 'test-job-123';
                
                const mockResponse = { success: true, status };
                
                // Simulate status check response handling
                if (status.status === 'completed') {
                    queueTesting.handleTestSuccess(status);
                    expect(queueTesting.currentTestJobId).toBeNull();
                } else if (status.status === 'failed') {
                    queueTesting.handleTestFailure(status);
                    expect(queueTesting.currentTestJobId).toBeNull();
                } else if (status.status === 'timeout') {
                    queueTesting.handleTestTimeout();
                    expect(queueTesting.currentTestJobId).toBeNull();
                }
            });
        });
    });
});

describe('AdminQueueTesting - Module Export', () => {
    test('should export AdminQueueTesting class', () => {
        expect(AdminQueueTesting).toBeDefined();
        expect(typeof AdminQueueTesting).toBe('function');
        expect(AdminQueueTesting.prototype.constructor).toBe(AdminQueueTesting);
    });

    test('should be instantiable', () => {
        // Mock minimal globals for instantiation
        global.document = {
            getElementById: () => null,
            querySelector: () => null,
            createElement: () => ({ className: '', innerHTML: '', style: {} }),
            body: { appendChild: () => {} }
        };
        global.fetch = () => {};
        global.localStorage = { getItem: () => null, setItem: () => {} };

        const instance = new AdminQueueTesting();
        expect(instance).toBeInstanceOf(AdminQueueTesting);
    });
});

describe('AdminQueueTesting - Animation and Visual Enhancements', () => {
    let queueTesting;

    beforeEach(() => {
        // Mock globals
        global.document = {
            getElementById: () => null,
            querySelector: () => null,
            createElement: () => ({ className: '', innerHTML: '', style: {} }),
            body: { appendChild: () => {} }
        };
        global.fetch = jest.fn();
        global.localStorage = { getItem: () => null, setItem: () => {} };
        global.setTimeout = jest.fn();
        global.setInterval = jest.fn();
        global.clearInterval = jest.fn();
        global.Date.now = jest.fn(() => 1640995200000);
        global.console = { log: jest.fn(), error: jest.fn(), warn: jest.fn() };

        queueTesting = new AdminQueueTesting();
    });

    test('should provide enhanced visual feedback methods', () => {
        const enhancedMethods = [
            'addResultAnimation',
            'animateResultRemoval',
            'createResultDetailsSection',
            'updateProgressWithAnimation',
            'setLoadingStateWithAnimation',
            'showDetailedError',
            'showSuccessNotification'
        ];

        enhancedMethods.forEach(method => {
            expect(typeof queueTesting[method]).toBe('function');
        });
    });

    test('should handle success animations', () => {
        const mockElement = {
            classList: { add: jest.fn(), remove: jest.fn() },
            style: {}
        };

        queueTesting.addResultAnimation(mockElement, 'success');
        
        expect(mockElement.classList.add).toHaveBeenCalledWith('animate-success-glow');
        expect(global.setTimeout).toHaveBeenCalled();
    });

    test('should handle error animations', () => {
        const mockElement = {
            classList: { add: jest.fn(), remove: jest.fn() },
            style: {}
        };

        queueTesting.addResultAnimation(mockElement, 'failed');
        
        expect(mockElement.classList.add).toHaveBeenCalledWith('animate-error-shake');
        expect(global.setTimeout).toHaveBeenCalled();
    });

    test('should handle timeout animations', () => {
        const mockElement = {
            classList: { add: jest.fn(), remove: jest.fn() },
            style: {}
        };

        queueTesting.addResultAnimation(mockElement, 'timeout');
        
        expect(mockElement.classList.add).toHaveBeenCalledWith('animate-warning-pulse');
        expect(global.setTimeout).toHaveBeenCalled();
    });

    test('should create enhanced result elements with animations', () => {
        const result = {
            status: 'success',
            message: 'Test completed',
            timestamp: Date.now(),
            details: { processing_time: 1.5 }
        };

        const element = queueTesting.createTestResultElement(result);

        expect(element.className).toContain('animate-pulse-success');
        expect(element.className).toContain('transition-all');
        expect(element.className).toContain('hover:shadow-md');
        expect(element.innerHTML).toContain('animate-bounce-once');
    });
});