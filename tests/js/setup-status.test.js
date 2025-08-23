/**
 * JavaScript tests for Setup Status functionality
 * 
 * These tests verify the AJAX functionality and error handling
 * for the setup status refresh system.
 */

// Mock DOM elements and global objects
const mockDocument = {
    querySelector: jest.fn(),
    querySelectorAll: jest.fn(() => []),
    getElementById: jest.fn(),
    createElement: jest.fn(() => ({
        setAttribute: jest.fn(),
        classList: { add: jest.fn(), remove: jest.fn(), toggle: jest.fn() },
        style: {},
        innerHTML: '',
        textContent: '',
        addEventListener: jest.fn(),
        insertAdjacentElement: jest.fn(),
        remove: jest.fn()
    })),
    head: { appendChild: jest.fn() },
    body: { appendChild: jest.fn(), removeChild: jest.fn() },
    addEventListener: jest.fn()
};

const mockWindow = {
    addEventListener: jest.fn(),
    setTimeout: jest.fn((fn) => fn()),
    setInterval: jest.fn(),
    clearInterval: jest.fn(),
    clearTimeout: jest.fn(),
    fetch: jest.fn()
};

// Mock console for testing
const mockConsole = {
    log: jest.fn(),
    error: jest.fn(),
    warn: jest.fn()
};

// Setup global mocks
global.document = mockDocument;
global.window = mockWindow;
global.console = mockConsole;
global.setTimeout = mockWindow.setTimeout;
global.setInterval = mockWindow.setInterval;
global.clearInterval = mockWindow.clearInterval;
global.clearTimeout = mockWindow.clearTimeout;
global.fetch = mockWindow.fetch;

// Import the class to test
const SetupStatusManager = require('../../resources/js/setup-status.js');

describe('SetupStatusManager', () => {
    let statusManager;
    let mockElements;

    beforeEach(() => {
        // Reset all mocks
        jest.clearAllMocks();
        
        // Setup mock DOM elements
        mockElements = {
            refreshButton: {
                addEventListener: jest.fn(),
                disabled: false,
                classList: { add: jest.fn(), remove: jest.fn() }
            },
            buttonText: {
                textContent: 'Check Status'
            },
            spinner: {
                classList: { add: jest.fn(), remove: jest.fn() }
            },
            csrfToken: {
                getAttribute: jest.fn(() => 'mock-csrf-token')
            },
            statusIndicators: {},
            statusTexts: {},
            detailsTexts: {}
        };

        // Setup status steps mock elements
        const statusSteps = ['database', 'mail', 'google_drive', 'migrations', 'admin_user', 'queue_worker'];
        statusSteps.forEach(step => {
            mockElements.statusIndicators[step] = {
                classList: { add: jest.fn(), remove: jest.fn() },
                querySelector: jest.fn(() => ({ innerHTML: '' })),
                setAttribute: jest.fn()
            };
            mockElements.statusTexts[step] = {
                textContent: 'Checking...'
            };
            mockElements.detailsTexts[step] = {
                textContent: ''
            };
        });

        // Mock getElementById to return appropriate elements
        mockDocument.getElementById.mockImplementation((id) => {
            if (id === 'refresh-status-btn') return mockElements.refreshButton;
            if (id === 'refresh-btn-text') return mockElements.buttonText;
            if (id === 'refresh-spinner') return mockElements.spinner;
            if (id === 'last-checked') return { classList: { remove: jest.fn() } };
            if (id === 'last-checked-time') return { textContent: '' };
            
            // Status indicators
            const stepMatch = id.match(/^status-(.+)$/);
            if (stepMatch) {
                return mockElements.statusIndicators[stepMatch[1]];
            }
            
            // Status texts
            const textMatch = id.match(/^status-(.+)-text$/);
            if (textMatch) {
                return mockElements.statusTexts[textMatch[1]];
            }
            
            // Details texts
            const detailsMatch = id.match(/^details-(.+)-text$/);
            if (detailsMatch) {
                return mockElements.detailsTexts[detailsMatch[1]];
            }
            
            return null;
        });

        // Mock querySelector for CSRF token
        mockDocument.querySelector.mockImplementation((selector) => {
            if (selector === 'meta[name="csrf-token"]') {
                return mockElements.csrfToken;
            }
            if (selector === '.text-center.mb-8') {
                return { insertAdjacentElement: jest.fn() };
            }
            return null;
        });

        // Mock querySelectorAll
        mockDocument.querySelectorAll.mockReturnValue([]);

        // Create new instance for each test with auto-init disabled
        statusManager = new SetupStatusManager({ autoInit: false });
    });

    describe('Initialization', () => {
        test('should initialize with correct default values', () => {
            expect(statusManager.statusSteps).toEqual([
                'database', 'mail', 'google_drive', 'migrations', 'admin_user', 'queue_worker'
            ]);
            expect(statusManager.refreshInProgress).toBe(false);
            expect(statusManager.retryAttempts).toBe(0);
            expect(statusManager.maxRetryAttempts).toBe(3);
            expect(statusManager.retryDelay).toBe(2000);
        });

        test('should bind event listeners on initialization', () => {
            // Clear previous calls
            jest.clearAllMocks();
            
            // Initialize manually
            statusManager.init();
            
            expect(mockElements.refreshButton.addEventListener).toHaveBeenCalledWith(
                'click', 
                statusManager.refreshAllStatuses
            );
            expect(mockDocument.addEventListener).toHaveBeenCalledWith(
                'keydown', 
                expect.any(Function)
            );
        });

        test('should setup CSRF token on initialization', () => {
            // Clear previous calls
            jest.clearAllMocks();
            
            // Initialize manually
            statusManager.init();
            
            expect(mockDocument.querySelector).toHaveBeenCalledWith('meta[name="csrf-token"]');
        });
    });

    describe('AJAX Status Refresh', () => {
        test('should make successful AJAX request for all statuses', async () => {
            const mockResponse = {
                success: true,
                data: {
                    statuses: {
                        database: { status: 'completed', message: 'Connected', details: 'Database is working' },
                        mail: { status: 'incomplete', message: 'Not configured', details: 'Mail not set up' }
                    }
                }
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValueOnce(mockResponse)
            });

            await statusManager.refreshAllStatuses();

            expect(global.fetch).toHaveBeenCalledWith('/setup/status/refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': 'mock-csrf-token',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: expect.any(AbortSignal)
            });

            expect(mockElements.statusIndicators.database.classList.add).toHaveBeenCalledWith('status-completed');
            expect(mockElements.statusTexts.database.textContent).toBe('Connected');
        });

        test('should handle AJAX request failure with retry logic', async () => {
            const mockError = new Error('Network error');
            global.fetch.mockRejectedValueOnce(mockError);

            await statusManager.refreshAllStatuses();

            expect(statusManager.retryAttempts).toBe(1);
            expect(mockConsole.error).toHaveBeenCalledWith(
                'Refresh error (attempt 1):',
                mockError
            );
        });

        test('should handle timeout errors', async () => {
            // Mock AbortError
            const abortError = new Error('The operation was aborted');
            abortError.name = 'AbortError';
            global.fetch.mockRejectedValueOnce(abortError);

            await statusManager.refreshAllStatuses();

            expect(statusManager.retryAttempts).toBe(1);
        });

        test('should prevent multiple concurrent refresh requests', async () => {
            statusManager.refreshInProgress = true;

            await statusManager.refreshAllStatuses();

            expect(global.fetch).not.toHaveBeenCalled();
            expect(mockConsole.log).toHaveBeenCalledWith('Refresh already in progress, skipping...');
        });
    });

    describe('Single Step Refresh', () => {
        test('should refresh single step successfully', async () => {
            const mockResponse = {
                success: true,
                data: {
                    step: 'database',
                    status: {
                        status: 'completed',
                        message: 'Connected',
                        details: 'Database is working',
                        step_name: 'Database'
                    }
                }
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: jest.fn().mockResolvedValueOnce(mockResponse)
            });

            await statusManager.refreshSingleStep('database');

            expect(global.fetch).toHaveBeenCalledWith('/setup/status/refresh-step', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': 'mock-csrf-token',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ step: 'database' }),
                signal: expect.any(AbortSignal)
            });

            expect(mockElements.statusIndicators.database.classList.add).toHaveBeenCalledWith('status-completed');
        });

        test('should reject invalid step names', async () => {
            await statusManager.refreshSingleStep('invalid_step');

            expect(global.fetch).not.toHaveBeenCalled();
            expect(mockConsole.error).toHaveBeenCalledWith('Invalid step name:', 'invalid_step');
        });

        test('should handle single step refresh failure', async () => {
            const mockError = new Error('Step refresh failed');
            global.fetch.mockRejectedValueOnce(mockError);

            await statusManager.refreshSingleStep('database');

            expect(mockConsole.error).toHaveBeenCalledWith(
                'Error refreshing database status:',
                mockError
            );
        });
    });

    describe('Status Indicator Updates', () => {
        test('should update status indicator correctly', () => {
            statusManager.updateStatusIndicator('database', 'completed', 'Connected', 'Database working');

            expect(mockElements.statusIndicators.database.classList.remove).toHaveBeenCalledWith(
                'status-completed', 'status-incomplete', 'status-error', 
                'status-checking', 'status-cannot-verify', 'status-needs_attention'
            );
            expect(mockElements.statusIndicators.database.classList.add).toHaveBeenCalledWith('status-completed');
            expect(mockElements.statusIndicators.database.setAttribute).toHaveBeenCalledWith(
                'aria-label', 
                'database status: Connected'
            );
        });

        test('should handle missing DOM elements gracefully', () => {
            mockDocument.getElementById.mockReturnValue(null);

            statusManager.updateStatusIndicator('nonexistent', 'completed', 'Test');

            expect(mockConsole.error).toHaveBeenCalledWith(
                'Could not find status elements for step: nonexistent'
            );
        });

        test('should update status icons correctly', () => {
            const mockIcon = { innerHTML: '' };
            mockElements.statusIndicators.database.querySelector.mockReturnValue(mockIcon);

            statusManager.updateStatusIcon(mockElements.statusIndicators.database, 'completed');

            expect(mockIcon.innerHTML).toContain('M5 13l4 4L19 7');
        });
    });

    describe('Loading States', () => {
        test('should set loading state correctly', () => {
            statusManager.setLoadingState(true);

            expect(mockElements.refreshButton.disabled).toBe(true);
            expect(mockElements.buttonText.textContent).toBe('Checking...');
            expect(mockElements.spinner.classList.remove).toHaveBeenCalledWith('hidden');
        });

        test('should clear loading state correctly', () => {
            statusManager.setLoadingState(false);

            expect(mockElements.refreshButton.disabled).toBe(false);
            expect(mockElements.buttonText.textContent).toBe('Check Status');
            expect(mockElements.spinner.classList.add).toHaveBeenCalledWith('hidden');
        });
    });

    describe('Error Handling', () => {
        test('should handle errors with retry logic', () => {
            const mockError = new Error('Test error');
            
            statusManager.handleRefreshError(mockError, 'all');

            expect(statusManager.retryAttempts).toBe(1);
            expect(mockWindow.setTimeout).toHaveBeenCalled();
        });

        test('should show error state after max retries', () => {
            statusManager.retryAttempts = 3; // Set to max
            const mockError = new Error('Max retries reached');

            statusManager.handleRefreshError(mockError, 'all');

            expect(statusManager.retryAttempts).toBe(0); // Should reset
        });

        test('should reset retry attempts on success', () => {
            statusManager.retryAttempts = 2;
            
            statusManager.resetRetryAttempts();

            expect(statusManager.retryAttempts).toBe(0);
        });
    });

    describe('Message System', () => {
        test('should show success message', () => {
            const mockMessageContainer = {
                innerHTML: '',
                remove: jest.fn()
            };
            mockDocument.createElement.mockReturnValue(mockMessageContainer);

            statusManager.showSuccessMessage('Test success');

            expect(mockDocument.createElement).toHaveBeenCalledWith('div');
            expect(mockWindow.setTimeout).toHaveBeenCalled(); // Auto-dismiss
        });

        test('should show error message with retry button', () => {
            const mockMessageContainer = {
                innerHTML: '',
                remove: jest.fn()
            };
            mockDocument.createElement.mockReturnValue(mockMessageContainer);

            statusManager.showErrorMessage('Test error', true);

            expect(mockMessageContainer.innerHTML).toContain('Retry Now');
        });

        test('should clear messages', () => {
            const mockMessages = [
                { remove: jest.fn() },
                { remove: jest.fn() }
            ];
            mockDocument.querySelectorAll.mockReturnValue(mockMessages);

            statusManager.clearMessages();

            expect(mockMessages[0].remove).toHaveBeenCalled();
            expect(mockMessages[1].remove).toHaveBeenCalled();
        });
    });

    describe('Auto-refresh Functionality', () => {
        test('should enable auto-refresh', () => {
            statusManager.toggleAutoRefresh(true);

            expect(statusManager.autoRefreshEnabled).toBe(true);
            expect(mockWindow.setInterval).toHaveBeenCalledWith(
                expect.any(Function),
                30000
            );
        });

        test('should disable auto-refresh', () => {
            statusManager.autoRefreshInterval = 'mock-interval';
            
            statusManager.toggleAutoRefresh(false);

            expect(statusManager.autoRefreshEnabled).toBe(false);
            expect(mockWindow.clearInterval).toHaveBeenCalledWith('mock-interval');
        });
    });

    describe('Keyboard Navigation', () => {
        test('should handle Ctrl+R keyboard shortcut', () => {
            const mockEvent = {
                ctrlKey: true,
                key: 'r',
                preventDefault: jest.fn()
            };

            // Simulate the keydown event
            const keydownHandler = mockDocument.addEventListener.mock.calls
                .find(call => call[0] === 'keydown')[1];
            
            keydownHandler(mockEvent);

            expect(mockEvent.preventDefault).toHaveBeenCalled();
        });

        test('should not handle keyboard shortcut when refresh in progress', () => {
            statusManager.refreshInProgress = true;
            
            const mockEvent = {
                ctrlKey: true,
                key: 'r',
                preventDefault: jest.fn()
            };

            const keydownHandler = mockDocument.addEventListener.mock.calls
                .find(call => call[0] === 'keydown')[1];
            
            keydownHandler(mockEvent);

            expect(mockEvent.preventDefault).not.toHaveBeenCalled();
        });
    });

    describe('Cleanup', () => {
        test('should cleanup intervals on cleanup', () => {
            statusManager.autoRefreshInterval = 'mock-interval';
            
            statusManager.cleanup();

            expect(mockWindow.clearInterval).toHaveBeenCalledWith('mock-interval');
        });
    });

    describe('Utility Functions', () => {
        test('should get CSRF token', () => {
            // Reset the mock to return the token properly
            mockElements.csrfToken.getAttribute.mockReturnValue('mock-csrf-token');
            
            const token = statusManager.getCSRFToken();
            
            expect(token).toBe('mock-csrf-token');
            expect(mockElements.csrfToken.getAttribute).toHaveBeenCalledWith('content');
        });

        test('should handle missing CSRF token', () => {
            // Mock querySelector to return null (no CSRF token element)
            const originalImplementation = mockDocument.querySelector.getMockImplementation();
            mockDocument.querySelector.mockImplementation((selector) => {
                if (selector === 'meta[name="csrf-token"]') {
                    return null;
                }
                return originalImplementation ? originalImplementation(selector) : null;
            });
            
            const token = statusManager.getCSRFToken();
            
            expect(token).toBeUndefined();
            expect(mockConsole.warn).toHaveBeenCalledWith('CSRF token not found');
            
            // Restore original implementation
            mockDocument.querySelector.mockImplementation(originalImplementation);
        });

        test('should toggle status details', () => {
            const mockDetails = {
                classList: { toggle: jest.fn() }
            };
            mockDocument.getElementById.mockReturnValue(mockDetails);

            statusManager.toggleStatusDetails('database');

            expect(mockDetails.classList.toggle).toHaveBeenCalledWith('show');
        });

        test('should update last checked timestamp', () => {
            const mockLastChecked = { classList: { remove: jest.fn() } };
            const mockLastCheckedTime = { textContent: '' };
            
            mockDocument.getElementById
                .mockReturnValueOnce(mockLastChecked)
                .mockReturnValueOnce(mockLastCheckedTime);

            statusManager.updateLastChecked();

            expect(mockLastChecked.classList.remove).toHaveBeenCalledWith('hidden');
            expect(mockLastCheckedTime.textContent).toBeTruthy();
        });
    });
});

describe('Global Functions', () => {
    test('should provide toggleStatusDetails global function', () => {
        // This would be tested in a browser environment
        // Here we just verify the function exists
        expect(typeof toggleStatusDetails).toBe('function');
    });
});