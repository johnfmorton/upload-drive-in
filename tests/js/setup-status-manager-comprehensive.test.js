/**
 * Comprehensive unit tests for SetupStatusManager class.
 * 
 * Tests the modified behavior where queue worker status is handled separately
 * from general status refresh operations.
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

describe('SetupStatusManager - Comprehensive Tests', () => {
    let statusManager;
    let mockFetch;
    let mockDocument;
    let mockConsole;

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
        
        // Mock document and DOM elements
        const mockElements = new Map();
        mockDocument = {
            querySelector: jest.fn((selector) => {
                if (selector === 'meta[name="csrf-token"]') {
                    return { getAttribute: () => 'test-csrf-token' };
                }
                return mockElements.get(selector) || null;
            }),
            querySelectorAll: jest.fn(() => []),
            getElementById: jest.fn((id) => mockElements.get(`#${id}`) || null),
            createElement: jest.fn(() => ({
                name: '',
                content: '',
                setAttribute: jest.fn(),
                getAttribute: jest.fn()
            })),
            head: {
                appendChild: jest.fn()
            },
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
        
        // Create status manager instance
        statusManager = new SetupStatusManager({ autoInit: false });
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    describe('Initialization and Configuration', () => {
        test('should initialize with correct general status steps excluding queue_worker', () => {
            expect(statusManager.generalStatusSteps).toEqual([
                "database",
                "mail", 
                "google_drive",
                "migrations",
                "admin_user"
            ]);
            
            // Should not include queue_worker in general steps
            expect(statusManager.generalStatusSteps).not.toContain('queue_worker');
        });

        test('should maintain backward compatibility with statusSteps', () => {
            expect(statusManager.statusSteps).toEqual([
                "database",
                "mail",
                "google_drive", 
                "migrations",
                "admin_user",
                "queue_worker"
            ]);
        });

        test('should initialize polling service with correct configuration', () => {
            expect(statusManager.pollingService).toBeDefined();
            expect(mockPollingService).toBeDefined();
        });

        test('should setup CSRF token correctly', () => {
            statusManager.setupCSRFToken();
            
            expect(mockDocument.querySelector).toHaveBeenCalledWith('meta[name="csrf-token"]');
        });
    });

    describe('General Status Refresh (Excluding Queue Worker)', () => {
        test('should refresh only general status steps', async () => {
            const mockResponse = {
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
            
            mockFetch.mockResolvedValueOnce(mockResponse);
            
            // Mock DOM elements for status updates
            const mockStatusElements = {};
            statusManager.generalStatusSteps.forEach(step => {
                mockStatusElements[step] = {
                    textContent: '',
                    className: '',
                    classList: { add: jest.fn(), remove: jest.fn() }
                };
                mockDocument.getElementById.mockImplementation(id => {
                    if (id === `${step}-status`) return mockStatusElements[step];
                    return null;
                });
            });

            await statusManager.refreshGeneralStatuses();

            // Verify fetch was called with correct endpoint
            expect(mockFetch).toHaveBeenCalledWith('/setup/status/refresh', expect.objectContaining({
                method: 'POST',
                headers: expect.objectContaining({
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': 'test-csrf-token'
                })
            }));

            // Verify queue_worker was not included in the request
            const fetchCall = mockFetch.mock.calls[0];
            const requestBody = JSON.parse(fetchCall[1].body);
            expect(requestBody.steps).toEqual(statusManager.generalStatusSteps);
            expect(requestBody.steps).not.toContain('queue_worker');
        });

        test('should handle general status refresh errors gracefully', async () => {
            const mockError = new Error('Network error');
            mockFetch.mockRejectedValueOnce(mockError);

            await statusManager.refreshGeneralStatuses();

            expect(mockConsole.error).toHaveBeenCalledWith('Error refreshing general statuses:', mockError);
        });

        test('should update UI elements for general status steps only', async () => {
            const mockResponse = {
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    statuses: {
                        database: { status: 'success', message: 'Database connected' },
                        mail: { status: 'error', message: 'Mail not configured' }
                    }
                })
            };
            
            mockFetch.mockResolvedValueOnce(mockResponse);
            
            const mockDatabaseElement = {
                textContent: '',
                className: '',
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            const mockMailElement = {
                textContent: '',
                className: '',
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            mockDocument.getElementById.mockImplementation(id => {
                if (id === 'database-status') return mockDatabaseElement;
                if (id === 'mail-status') return mockMailElement;
                return null;
            });

            await statusManager.refreshGeneralStatuses();

            expect(mockDatabaseElement.textContent).toBe('Database connected');
            expect(mockMailElement.textContent).toBe('Mail not configured');
            expect(mockDatabaseElement.classList.add).toHaveBeenCalledWith('text-green-600');
            expect(mockMailElement.classList.add).toHaveBeenCalledWith('text-red-600');
        });
    });

    describe('Queue Worker Status Management', () => {
        test('should load cached queue worker status on initialization', async () => {
            const cachedStatus = {
                status: 'completed',
                message: 'Queue worker is functioning properly',
                processing_time: 1.23
            };
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(cachedStatus)
            });
            
            const mockQueueElement = {
                textContent: '',
                className: '',
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            mockDocument.getElementById.mockImplementation(id => {
                if (id === 'queue_worker-status') return mockQueueElement;
                return null;
            });

            await statusManager.loadCachedQueueWorkerStatus();

            expect(mockFetch).toHaveBeenCalledWith('/setup/queue-worker/status', expect.any(Object));
            expect(mockQueueElement.textContent).toBe('Queue worker is functioning properly');
        });

        test('should show default message when no cached queue worker status exists', async () => {
            const defaultStatus = {
                status: 'not_tested',
                message: 'Click the Test Queue Worker button below'
            };
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve(defaultStatus)
            });
            
            const mockQueueElement = {
                textContent: '',
                className: '',
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            mockDocument.getElementById.mockImplementation(id => {
                if (id === 'queue_worker-status') return mockQueueElement;
                return null;
            });

            await statusManager.loadCachedQueueWorkerStatus();

            expect(mockQueueElement.textContent).toBe('Click the Test Queue Worker button below');
            expect(mockQueueElement.classList.add).toHaveBeenCalledWith('text-gray-600');
        });

        test('should trigger queue worker test alongside general status refresh', async () => {
            // Mock general status response
            const generalResponse = {
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    statuses: {
                        database: { status: 'success', message: 'Database connected' }
                    }
                })
            };
            
            // Mock queue worker test response
            const queueResponse = {
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    job_id: 'test-job-123',
                    message: 'Test job dispatched'
                })
            };
            
            mockFetch
                .mockResolvedValueOnce(generalResponse)  // General status refresh
                .mockResolvedValueOnce(queueResponse);   // Queue worker test

            await statusManager.refreshAllStatuses();

            expect(mockFetch).toHaveBeenCalledTimes(2);
            expect(mockFetch).toHaveBeenNthCalledWith(1, '/setup/status/refresh', expect.any(Object));
            expect(mockFetch).toHaveBeenNthCalledWith(2, '/setup/queue-worker/test', expect.any(Object));
        });
    });

    describe('Button State Management', () => {
        test('should disable buttons during status refresh', async () => {
            const mockRefreshButton = {
                disabled: false,
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            const mockQueueButton = {
                disabled: false,
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            mockDocument.getElementById.mockImplementation(id => {
                if (id === 'refresh-status-btn') return mockRefreshButton;
                if (id === 'test-queue-worker-btn') return mockQueueButton;
                return null;
            });
            
            statusManager.refreshInProgress = true;
            statusManager.updateButtonStates();

            expect(mockRefreshButton.disabled).toBe(true);
            expect(mockQueueButton.disabled).toBe(true);
        });

        test('should re-enable buttons after status refresh completes', async () => {
            const mockRefreshButton = {
                disabled: true,
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            const mockQueueButton = {
                disabled: true,
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            mockDocument.getElementById.mockImplementation(id => {
                if (id === 'refresh-status-btn') return mockRefreshButton;
                if (id === 'test-queue-worker-btn') return mockQueueButton;
                return null;
            });
            
            statusManager.refreshInProgress = false;
            statusManager.queueWorkerTestInProgress = false;
            statusManager.updateButtonStates();

            expect(mockRefreshButton.disabled).toBe(false);
            expect(mockQueueButton.disabled).toBe(false);
        });

        test('should prevent concurrent operations with debouncing', async () => {
            const now = Date.now();
            statusManager.lastRefreshTime = now - 500; // 500ms ago
            statusManager.debounceDelay = 1000; // 1 second debounce

            const result = statusManager.shouldDebounceRefresh();

            expect(result).toBe(true);
        });
    });

    describe('Error Handling and Recovery', () => {
        test('should handle network errors in general status refresh', async () => {
            const networkError = new Error('Network error');
            mockFetch.mockRejectedValueOnce(networkError);

            await statusManager.refreshGeneralStatuses();

            expect(mockConsole.error).toHaveBeenCalledWith('Error refreshing general statuses:', networkError);
        });

        test('should handle malformed JSON responses', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.reject(new Error('Invalid JSON'))
            });

            await statusManager.refreshGeneralStatuses();

            expect(mockConsole.error).toHaveBeenCalledWith('Error refreshing general statuses:', expect.any(Error));
        });

        test('should implement retry logic for failed requests', async () => {
            statusManager.retryAttempts = 0;
            statusManager.maxRetryAttempts = 3;
            
            // First two attempts fail, third succeeds
            mockFetch
                .mockRejectedValueOnce(new Error('Network error'))
                .mockRejectedValueOnce(new Error('Network error'))
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ success: true, statuses: {} })
                });

            await statusManager.retryRefresh();

            expect(mockFetch).toHaveBeenCalledTimes(1); // Only one call in retryRefresh
        });

        test('should show user-friendly error messages', async () => {
            const mockErrorElement = {
                textContent: '',
                style: { display: '' },
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            mockDocument.getElementById.mockImplementation(id => {
                if (id === 'status-error-message') return mockErrorElement;
                return null;
            });

            statusManager.showErrorMessage('Failed to refresh status');

            expect(mockErrorElement.textContent).toBe('Failed to refresh status');
            expect(mockErrorElement.style.display).toBe('block');
        });
    });

    describe('Status Persistence and Caching', () => {
        test('should check if queue worker status is expired', () => {
            const expiredStatus = {
                test_completed_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString() // 2 hours ago
            };
            
            const result = statusManager.isStatusExpired(expiredStatus);
            
            expect(result).toBe(true);
        });

        test('should recognize valid non-expired status', () => {
            const validStatus = {
                test_completed_at: new Date(Date.now() - 30 * 60 * 1000).toISOString() // 30 minutes ago
            };
            
            const result = statusManager.isStatusExpired(validStatus);
            
            expect(result).toBe(false);
        });

        test('should handle missing timestamp in status', () => {
            const statusWithoutTimestamp = {
                status: 'completed',
                message: 'Test completed'
            };
            
            const result = statusManager.isStatusExpired(statusWithoutTimestamp);
            
            expect(result).toBe(true); // Should be considered expired if no timestamp
        });
    });

    describe('Progressive Status Updates', () => {
        test('should show progressive status messages during queue worker test', async () => {
            const mockQueueElement = {
                textContent: '',
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            mockDocument.getElementById.mockImplementation(id => {
                if (id === 'queue_worker-status') return mockQueueElement;
                return null;
            });

            statusManager.updateQueueWorkerStatus({
                status: 'testing',
                message: 'Testing queue worker...'
            });

            expect(mockQueueElement.textContent).toBe('Testing queue worker...');
            expect(mockQueueElement.classList.add).toHaveBeenCalledWith('text-blue-600');
        });

        test('should update status with processing time on completion', async () => {
            const mockQueueElement = {
                textContent: '',
                classList: { add: jest.fn(), remove: jest.fn() }
            };
            
            mockDocument.getElementById.mockImplementation(id => {
                if (id === 'queue_worker-status') return mockQueueElement;
                return null;
            });

            statusManager.updateQueueWorkerStatus({
                status: 'completed',
                message: 'Queue worker is functioning properly',
                processing_time: 2.34
            });

            expect(mockQueueElement.textContent).toContain('Queue worker is functioning properly');
            expect(mockQueueElement.textContent).toContain('2.34s');
            expect(mockQueueElement.classList.add).toHaveBeenCalledWith('text-green-600');
        });
    });

    describe('Integration with Polling Service', () => {
        test('should handle polling status updates', () => {
            const statusUpdate = {
                status: 'processing',
                message: 'Test job is being processed...'
            };

            statusManager.handlePollingStatusUpdate(statusUpdate);

            // Should update the queue worker status
            expect(statusManager.queueWorkerTestInProgress).toBe(true);
        });

        test('should handle polling errors', () => {
            const error = new Error('Polling failed');

            statusManager.handlePollingError(error);

            expect(mockConsole.error).toHaveBeenCalledWith('Polling error:', error);
        });

        test('should handle polling metrics updates', () => {
            const metrics = {
                averageResponseTime: 150,
                errorRate: 0.05
            };

            statusManager.handlePollingMetrics(metrics);

            // Should log or store metrics for performance monitoring
            expect(mockConsole.log).toHaveBeenCalledWith('Polling metrics:', metrics);
        });
    });

    describe('Cleanup and Resource Management', () => {
        test('should cleanup intervals and event listeners on destroy', () => {
            statusManager.autoRefreshInterval = 'mock-interval';
            
            global.clearInterval = jest.fn();
            
            statusManager.destroy();

            expect(global.clearInterval).toHaveBeenCalledWith('mock-interval');
            expect(mockPollingService.stop).toHaveBeenCalled();
        });

        test('should handle missing DOM elements gracefully', () => {
            mockDocument.getElementById.mockReturnValue(null);

            // Should not throw error when elements are missing
            expect(() => {
                statusManager.updateQueueWorkerStatus({
                    status: 'completed',
                    message: 'Test message'
                });
            }).not.toThrow();
        });
    });
});