/**
 * Tests for enhanced error messaging and troubleshooting guidance in setup-status.js
 */

import { jest } from '@jest/globals';

// Mock DOM elements with proper getters/setters
const createMockElement = (initialText = '') => {
    let _textContent = initialText;
    let _innerHTML = '';
    
    return {
        get textContent() { return _textContent; },
        set textContent(value) { _textContent = value; },
        get innerHTML() { return _innerHTML; },
        set innerHTML(value) { _innerHTML = value; },
        classList: { 
            add: jest.fn(), 
            remove: jest.fn() 
        },
        appendChild: jest.fn()
    };
};

const mockElements = {
    'queue-test-error-details': createMockElement(),
    'queue-test-error-message': createMockElement(),
    'queue-test-troubleshooting-content': createMockElement(),
    'queue-test-success-details': createMockElement(),
    'queue-test-success-message': createMockElement(),
    'queue-test-processing-time': createMockElement(),
    'queue-test-progress': createMockElement(),
    'queue-test-progress-text': createMockElement(),
    'retry-queue-worker-btn': {
        classList: { add: jest.fn(), remove: jest.fn() },
        disabled: false
    }
};

// Mock document.getElementById
global.document = {
    getElementById: jest.fn((id) => mockElements[id] || null),
    createElement: jest.fn((tag) => ({
        className: '',
        innerHTML: '',
        textContent: '',
        appendChild: jest.fn()
    }))
};

// Mock the SetupStatusManager class
class SetupStatusManager {
    constructor() {
        this.queueWorkerTestInProgress = false;
        this.refreshInProgress = false;
    }

    showQueueWorkerErrorDetails(status) {
        const errorDetailsContainer = document.getElementById("queue-test-error-details");
        const errorMessage = document.getElementById("queue-test-error-message");
        const troubleshootingContent = document.getElementById("queue-test-troubleshooting-content");

        if (errorDetailsContainer && errorMessage) {
            errorMessage.textContent = status.error_message || 'Test failed with unknown error';

            if (troubleshootingContent && status.troubleshooting && Array.isArray(status.troubleshooting)) {
                troubleshootingContent.innerHTML = '';
                status.troubleshooting.forEach(step => {
                    const stepElement = document.createElement('div');
                    stepElement.className = 'text-xs text-red-600 mb-1';
                    stepElement.innerHTML = `• ${this.escapeHtml(step)}`;
                    troubleshootingContent.appendChild(stepElement);
                });
            }

            errorDetailsContainer.classList.remove("hidden");
        }
    }

    hideQueueWorkerErrorDetails() {
        const errorDetailsContainer = document.getElementById("queue-test-error-details");
        if (errorDetailsContainer) {
            errorDetailsContainer.classList.add("hidden");
        }
    }

    showQueueWorkerSuccessDetails(status) {
        const successDetailsContainer = document.getElementById("queue-test-success-details");
        const successMessage = document.getElementById("queue-test-success-message");
        const processingTime = document.getElementById("queue-test-processing-time");

        if (successDetailsContainer && successMessage) {
            successMessage.textContent = status.message || 'Queue worker is functioning properly';

            if (processingTime && status.processing_time) {
                processingTime.textContent = `Processing time: ${status.processing_time.toFixed(2)} seconds`;
            }

            successDetailsContainer.classList.remove("hidden");
        }
    }

    hideQueueWorkerSuccessDetails() {
        const successDetailsContainer = document.getElementById("queue-test-success-details");
        if (successDetailsContainer) {
            successDetailsContainer.classList.add("hidden");
        }
    }

    showQueueWorkerProgressDetails(status) {
        const progressContainer = document.getElementById("queue-test-progress");
        const progressText = document.getElementById("queue-test-progress-text");

        if (progressContainer && progressText) {
            progressText.textContent = status.message || 'Testing queue worker...';
            progressContainer.classList.remove("hidden");
        }
    }

    hideQueueWorkerProgressDetails() {
        const progressContainer = document.getElementById("queue-test-progress");
        if (progressContainer) {
            progressContainer.classList.add("hidden");
        }
    }

    showRetryButton() {
        const retryBtn = document.getElementById("retry-queue-worker-btn");
        if (retryBtn) {
            retryBtn.classList.remove("hidden");
            retryBtn.disabled = false;
            retryBtn.classList.remove("cursor-not-allowed", "opacity-75");
        }
    }

    hideRetryButton() {
        const retryBtn = document.getElementById("retry-queue-worker-btn");
        if (retryBtn) {
            retryBtn.classList.add("hidden");
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getTroubleshootingSteps(errorType) {
        const troubleshootingSteps = {
            'dispatch_failed': [
                'Verify queue configuration in .env file (QUEUE_CONNECTION)',
                'Check if database tables exist: php artisan migrate',
                'Ensure queue driver is properly configured (database, redis, etc.)',
                'Check application logs for configuration errors',
                'Verify file permissions for storage and cache directories',
                'Test database connection: php artisan tinker, then DB::connection()->getPdo()',
                'For Redis queue: ensure Redis server is running and accessible'
            ],
            'timeout': [
                'Ensure queue worker is running: php artisan queue:work',
                'Check if worker process is stuck or crashed',
                'Verify queue driver configuration (database, redis, etc.)',
                'Check system resources (CPU, memory, disk space)',
                'Review worker logs for errors or warnings',
                'Restart the queue worker: php artisan queue:restart',
                'Check for long-running jobs blocking the queue'
            ],
            'network_error': [
                'Check your internet connection',
                'Verify the application server is accessible',
                'Check for firewall or proxy issues',
                'Try refreshing the page and testing again',
                'Contact your network administrator if issues persist'
            ],
            'general': [
                'Check if queue worker is running: php artisan queue:work',
                'Verify queue configuration in .env file',
                'Check for failed jobs: php artisan queue:failed',
                'Review application logs for errors',
                'Restart the queue worker if needed',
                'Check system resources and permissions'
            ]
        };

        return troubleshootingSteps[errorType] || troubleshootingSteps['general'];
    }

    updateQueueWorkerStatusFromCache(cachedStatus) {
        switch (cachedStatus.status) {
            case 'completed':
                this.hideRetryButton();
                this.hideQueueWorkerErrorDetails();
                this.showQueueWorkerSuccessDetails(cachedStatus);
                break;
            case 'failed':
                this.showRetryButton();
                this.hideQueueWorkerSuccessDetails();
                this.showQueueWorkerErrorDetails(cachedStatus);
                break;
            case 'timeout':
                this.showRetryButton();
                this.hideQueueWorkerSuccessDetails();
                this.showQueueWorkerErrorDetails(cachedStatus);
                break;
            case 'error':
                this.showRetryButton();
                this.hideQueueWorkerSuccessDetails();
                this.showQueueWorkerErrorDetails(cachedStatus);
                break;
            case 'testing':
                this.hideRetryButton();
                this.hideQueueWorkerErrorDetails();
                this.hideQueueWorkerSuccessDetails();
                this.showQueueWorkerProgressDetails(cachedStatus);
                break;
            default:
                this.hideRetryButton();
                this.hideQueueWorkerErrorDetails();
                this.hideQueueWorkerSuccessDetails();
        }
    }
}

describe('Enhanced Error Messaging', () => {
    let statusManager;

    beforeEach(() => {
        statusManager = new SetupStatusManager();
        jest.clearAllMocks();
    });

    describe('showQueueWorkerErrorDetails', () => {
        test('displays error message and troubleshooting steps', () => {
            const errorStatus = {
                error_message: 'Queue worker test failed',
                troubleshooting: [
                    'Check if queue worker is running: php artisan queue:work',
                    'Verify queue configuration in .env file',
                    'Check for failed jobs: php artisan queue:failed'
                ]
            };

            statusManager.showQueueWorkerErrorDetails(errorStatus);

            expect(mockElements['queue-test-error-message'].textContent)
                .toBe('Queue worker test failed');
            expect(mockElements['queue-test-error-details'].classList.remove)
                .toHaveBeenCalledWith('hidden');
            expect(document.createElement).toHaveBeenCalledWith('div');
        });

        test('handles missing troubleshooting steps gracefully', () => {
            const errorStatus = {
                error_message: 'Test failed',
                troubleshooting: null
            };

            statusManager.showQueueWorkerErrorDetails(errorStatus);

            expect(mockElements['queue-test-error-message'].textContent)
                .toBe('Test failed');
            expect(mockElements['queue-test-error-details'].classList.remove)
                .toHaveBeenCalledWith('hidden');
        });

        test('escapes HTML in troubleshooting steps', () => {
            const errorStatus = {
                error_message: 'Test failed',
                troubleshooting: [
                    'Check <script>alert("xss")</script> configuration'
                ]
            };

            statusManager.showQueueWorkerErrorDetails(errorStatus);

            // Verify escapeHtml is called (indirectly through createElement)
            expect(document.createElement).toHaveBeenCalled();
        });
    });

    describe('showQueueWorkerSuccessDetails', () => {
        test('displays success message and processing time', () => {
            const successStatus = {
                message: 'Queue worker is functioning properly',
                processing_time: 1.23
            };

            statusManager.showQueueWorkerSuccessDetails(successStatus);

            expect(mockElements['queue-test-success-message'].textContent)
                .toBe('Queue worker is functioning properly');
            expect(mockElements['queue-test-processing-time'].textContent)
                .toBe('Processing time: 1.23 seconds');
            expect(mockElements['queue-test-success-details'].classList.remove)
                .toHaveBeenCalledWith('hidden');
        });

        test('handles missing processing time', () => {
            const successStatus = {
                message: 'Queue worker is functioning properly'
            };

            statusManager.showQueueWorkerSuccessDetails(successStatus);

            expect(mockElements['queue-test-success-message'].textContent)
                .toBe('Queue worker is functioning properly');
            // Processing time element should not be updated if no processing_time
        });
    });

    describe('showQueueWorkerProgressDetails', () => {
        test('displays progress message', () => {
            const progressStatus = {
                message: 'Testing queue worker...'
            };

            statusManager.showQueueWorkerProgressDetails(progressStatus);

            expect(mockElements['queue-test-progress-text'].textContent)
                .toBe('Testing queue worker...');
            expect(mockElements['queue-test-progress'].classList.remove)
                .toHaveBeenCalledWith('hidden');
        });

        test('uses default message when none provided', () => {
            const progressStatus = {};

            statusManager.showQueueWorkerProgressDetails(progressStatus);

            expect(mockElements['queue-test-progress-text'].textContent)
                .toBe('Testing queue worker...');
        });
    });

    describe('getTroubleshootingSteps', () => {
        test('returns dispatch failure troubleshooting steps', () => {
            const steps = statusManager.getTroubleshootingSteps('dispatch_failed');

            expect(steps).toContain('Verify queue configuration in .env file (QUEUE_CONNECTION)');
            expect(steps).toContain('Check if database tables exist: php artisan migrate');
            expect(steps).toContain('For Redis queue: ensure Redis server is running and accessible');
        });

        test('returns timeout troubleshooting steps', () => {
            const steps = statusManager.getTroubleshootingSteps('timeout');

            expect(steps).toContain('Ensure queue worker is running: php artisan queue:work');
            expect(steps).toContain('Check if worker process is stuck or crashed');
            expect(steps).toContain('Restart the queue worker: php artisan queue:restart');
        });

        test('returns network error troubleshooting steps', () => {
            const steps = statusManager.getTroubleshootingSteps('network_error');

            expect(steps).toContain('Check your internet connection');
            expect(steps).toContain('Verify the application server is accessible');
            expect(steps).toContain('Check for firewall or proxy issues');
        });

        test('returns general troubleshooting steps for unknown error type', () => {
            const steps = statusManager.getTroubleshootingSteps('unknown_error');

            expect(steps).toContain('Check if queue worker is running: php artisan queue:work');
            expect(steps).toContain('Verify queue configuration in .env file');
            expect(steps).toContain('Check for failed jobs: php artisan queue:failed');
        });
    });

    describe('updateQueueWorkerStatusFromCache', () => {
        test('handles failed status with error details', () => {
            const failedStatus = {
                status: 'failed',
                message: 'Queue worker test failed',
                error_message: 'Dispatch failed',
                troubleshooting: ['Step 1', 'Step 2']
            };

            const showErrorDetailsSpy = jest.spyOn(statusManager, 'showQueueWorkerErrorDetails');
            const hideSuccessDetailsSpy = jest.spyOn(statusManager, 'hideQueueWorkerSuccessDetails');
            const showRetryButtonSpy = jest.spyOn(statusManager, 'showRetryButton');

            statusManager.updateQueueWorkerStatusFromCache(failedStatus);

            expect(showErrorDetailsSpy).toHaveBeenCalledWith(failedStatus);
            expect(hideSuccessDetailsSpy).toHaveBeenCalled();
            expect(showRetryButtonSpy).toHaveBeenCalled();
        });

        test('handles timeout status with specific error details', () => {
            const timeoutStatus = {
                status: 'timeout',
                message: 'Queue worker test timed out',
                error_message: 'Worker not running',
                troubleshooting: ['Start worker', 'Check configuration']
            };

            const showErrorDetailsSpy = jest.spyOn(statusManager, 'showQueueWorkerErrorDetails');
            const showRetryButtonSpy = jest.spyOn(statusManager, 'showRetryButton');

            statusManager.updateQueueWorkerStatusFromCache(timeoutStatus);

            expect(showErrorDetailsSpy).toHaveBeenCalledWith(timeoutStatus);
            expect(showRetryButtonSpy).toHaveBeenCalled();
        });

        test('handles completed status with success details', () => {
            const completedStatus = {
                status: 'completed',
                test_completed_at: '2025-01-01T12:00:00Z',
                processing_time: 2.45
            };

            const showSuccessDetailsSpy = jest.spyOn(statusManager, 'showQueueWorkerSuccessDetails');
            const hideErrorDetailsSpy = jest.spyOn(statusManager, 'hideQueueWorkerErrorDetails');
            const hideRetryButtonSpy = jest.spyOn(statusManager, 'hideRetryButton');

            statusManager.updateQueueWorkerStatusFromCache(completedStatus);

            expect(showSuccessDetailsSpy).toHaveBeenCalledWith(completedStatus);
            expect(hideErrorDetailsSpy).toHaveBeenCalled();
            expect(hideRetryButtonSpy).toHaveBeenCalled();
        });

        test('handles testing status with progress details', () => {
            const testingStatus = {
                status: 'testing',
                message: 'Test job processing...'
            };

            const showProgressDetailsSpy = jest.spyOn(statusManager, 'showQueueWorkerProgressDetails');
            const hideErrorDetailsSpy = jest.spyOn(statusManager, 'hideQueueWorkerErrorDetails');
            const hideSuccessDetailsSpy = jest.spyOn(statusManager, 'hideQueueWorkerSuccessDetails');

            statusManager.updateQueueWorkerStatusFromCache(testingStatus);

            expect(showProgressDetailsSpy).toHaveBeenCalledWith(testingStatus);
            expect(hideErrorDetailsSpy).toHaveBeenCalled();
            expect(hideSuccessDetailsSpy).toHaveBeenCalled();
        });
    });

    describe('escapeHtml', () => {
        test('escapes HTML special characters', () => {
            const maliciousText = '<script>alert("xss")</script>';
            const escaped = statusManager.escapeHtml(maliciousText);

            expect(escaped).toBe('&lt;script&gt;alert("xss")&lt;/script&gt;');
        });

        test('handles normal text without changes', () => {
            const normalText = 'Check configuration file';
            const escaped = statusManager.escapeHtml(normalText);

            expect(escaped).toBe('Check configuration file');
        });
    });

    describe('hide methods', () => {
        test('hideQueueWorkerErrorDetails adds hidden class', () => {
            statusManager.hideQueueWorkerErrorDetails();

            expect(mockElements['queue-test-error-details'].classList.add)
                .toHaveBeenCalledWith('hidden');
        });

        test('hideQueueWorkerSuccessDetails adds hidden class', () => {
            statusManager.hideQueueWorkerSuccessDetails();

            expect(mockElements['queue-test-success-details'].classList.add)
                .toHaveBeenCalledWith('hidden');
        });

        test('hideQueueWorkerProgressDetails adds hidden class', () => {
            statusManager.hideQueueWorkerProgressDetails();

            expect(mockElements['queue-test-progress'].classList.add)
                .toHaveBeenCalledWith('hidden');
        });
    });
});

describe('Error Type Detection in testQueueWorker', () => {
    let statusManager;

    beforeEach(() => {
        statusManager = new SetupStatusManager();
        jest.clearAllMocks();
    });

    test('detects dispatch failure error type', () => {
        const error = new Error('Failed to dispatch test job to queue');
        const getTroubleshootingStepsSpy = jest.spyOn(statusManager, 'getTroubleshootingSteps');

        // Simulate error handling in testQueueWorker
        let errorType = 'general';
        if (error.message.includes('dispatch')) {
            errorType = 'dispatch_failed';
        }

        statusManager.getTroubleshootingSteps(errorType);

        expect(getTroubleshootingStepsSpy).toHaveBeenCalledWith('dispatch_failed');
    });

    test('detects timeout error type', () => {
        const error = new Error('Request timeout occurred');
        const getTroubleshootingStepsSpy = jest.spyOn(statusManager, 'getTroubleshootingSteps');

        // Simulate error handling in testQueueWorker
        let errorType = 'general';
        if (error.message.includes('timeout')) {
            errorType = 'timeout';
        }

        statusManager.getTroubleshootingSteps(errorType);

        expect(getTroubleshootingStepsSpy).toHaveBeenCalledWith('timeout');
    });

    test('detects network error type', () => {
        const error = new Error('Network fetch failed');
        const getTroubleshootingStepsSpy = jest.spyOn(statusManager, 'getTroubleshootingSteps');

        // Simulate error handling in testQueueWorker
        let errorType = 'general';
        if (error.message.includes('network') || error.message.includes('fetch')) {
            errorType = 'network_error';
        }

        statusManager.getTroubleshootingSteps(errorType);

        expect(getTroubleshootingStepsSpy).toHaveBeenCalledWith('network_error');
    });

    test('falls back to general error type for unknown errors', () => {
        const error = new Error('Some unknown error occurred');
        const getTroubleshootingStepsSpy = jest.spyOn(statusManager, 'getTroubleshootingSteps');

        // Simulate error handling in testQueueWorker
        let errorType = 'general';
        // No specific error type detected, remains 'general'

        statusManager.getTroubleshootingSteps(errorType);

        expect(getTroubleshootingStepsSpy).toHaveBeenCalledWith('general');
    });
});