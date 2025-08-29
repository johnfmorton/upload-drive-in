/**
 * Button State Management Tests
 * 
 * Tests for proper button state management during testing operations,
 * including debouncing, loading states, and visual feedback.
 */

// Jest test file

// Mock DOM elements and methods
const mockElements = new Map();
const mockTimeouts = new Set();

// Mock setTimeout and clearTimeout
const originalSetTimeout = global.setTimeout;
const originalClearTimeout = global.clearTimeout;

global.setTimeout = jest.fn((callback, delay) => {
    const id = Math.random();
    mockTimeouts.add(id);
    // Execute immediately for testing
    callback();
    return id;
});

global.clearTimeout = jest.fn((id) => {
    mockTimeouts.delete(id);
});

// Mock fetch
global.fetch = jest.fn();

// Mock DOM methods
global.document = {
    getElementById: jest.fn((id) => mockElements.get(id)),
    querySelector: jest.fn(),
    querySelectorAll: jest.fn(() => []),
    createElement: jest.fn(() => ({
        className: '',
        classList: {
            add: jest.fn(),
            remove: jest.fn(),
            contains: jest.fn(() => false)
        },
        setAttribute: jest.fn(),
        removeAttribute: jest.fn(),
        appendChild: jest.fn(),
        insertBefore: jest.fn(),
        addEventListener: jest.fn()
    })),
    head: {
        appendChild: jest.fn()
    },
    body: {
        appendChild: jest.fn()
    },
    addEventListener: jest.fn()
};

// Create mock button elements
function createMockButton(id, textId = null, spinnerId = null) {
    const button = {
        id,
        get disabled() { return this._disabled || false; },
        set disabled(value) { this._disabled = value; },
        _disabled: false,
        classList: {
            add: jest.fn(),
            remove: jest.fn(),
            contains: jest.fn(() => false)
        },
        setAttribute: jest.fn(),
        removeAttribute: jest.fn(),
        addEventListener: jest.fn(),
        textContent: ''
    };

    const textElement = textId ? {
        id: textId,
        get textContent() { return this._textContent || 'Default Text'; },
        set textContent(value) { this._textContent = value; },
        _textContent: 'Default Text'
    } : null;

    const spinnerElement = spinnerId ? {
        id: spinnerId,
        classList: {
            add: jest.fn(),
            remove: jest.fn(),
            contains: jest.fn(() => false)
        }
    } : null;

    mockElements.set(id, button);
    if (textElement) mockElements.set(textId, textElement);
    if (spinnerElement) mockElements.set(spinnerId, spinnerElement);

    return { button, textElement, spinnerElement };
}

// Import the class after mocking
class SetupStatusManager {
    constructor(options = {}) {
        this.generalStatusSteps = ["database", "mail", "google_drive", "migrations", "admin_user"];
        this.statusSteps = ["database", "mail", "google_drive", "migrations", "admin_user", "queue_worker"];
        this.refreshInProgress = false;
        this.queueWorkerTestInProgress = false;
        this.retryAttempts = 0;
        this.maxRetryAttempts = 3;
        this.retryDelay = 2000;
        this.autoRefreshInterval = null;
        this.autoRefreshEnabled = false;
        this.autoInit = options.autoInit !== false;
        this.lastRefreshTime = 0;
        this.lastQueueTestTime = 0;
        this.debounceDelay = 1000;
        this.clickTimeouts = new Map();

        // Bind methods
        this.refreshAllStatuses = this.refreshAllStatuses.bind(this);
        this.debouncedRefreshAllStatuses = this.debouncedRefreshAllStatuses.bind(this);
        this.debouncedTestQueueWorker = this.debouncedTestQueueWorker.bind(this);
        this.debouncedRetryQueueWorkerTest = this.debouncedRetryQueueWorkerTest.bind(this);

        if (this.autoInit) {
            this.init();
        }
    }

    init() {
        // Mock initialization
    }

    debouncedRefreshAllStatuses(event) {
        event.preventDefault();
        
        const now = Date.now();
        const timeSinceLastRefresh = now - this.lastRefreshTime;
        
        if (this.refreshInProgress || this.queueWorkerTestInProgress) {
            console.log("Operation already in progress, ignoring click");
            return;
        }
        
        if (timeSinceLastRefresh < this.debounceDelay) {
            console.log("Debouncing refresh request");
            if (this.clickTimeouts.has('refresh')) {
                clearTimeout(this.clickTimeouts.get('refresh'));
            }
            
            const timeoutId = setTimeout(() => {
                this.refreshAllStatuses();
                this.clickTimeouts.delete('refresh');
            }, this.debounceDelay - timeSinceLastRefresh);
            
            this.clickTimeouts.set('refresh', timeoutId);
            return;
        }
        
        this.lastRefreshTime = now;
        this.refreshAllStatuses();
    }

    debouncedTestQueueWorker(event) {
        event.preventDefault();
        
        const now = Date.now();
        const timeSinceLastTest = now - this.lastQueueTestTime;
        
        if (this.queueWorkerTestInProgress || this.refreshInProgress) {
            console.log("Operation already in progress, ignoring click");
            return;
        }
        
        if (timeSinceLastTest < this.debounceDelay) {
            console.log("Debouncing queue test request");
            if (this.clickTimeouts.has('queueTest')) {
                clearTimeout(this.clickTimeouts.get('queueTest'));
            }
            
            const timeoutId = setTimeout(() => {
                this.testQueueWorker();
                this.clickTimeouts.delete('queueTest');
            }, this.debounceDelay - timeSinceLastTest);
            
            this.clickTimeouts.set('queueTest', timeoutId);
            return;
        }
        
        this.lastQueueTestTime = now;
        this.testQueueWorker();
    }

    debouncedRetryQueueWorkerTest(event) {
        event.preventDefault();
        
        const now = Date.now();
        const timeSinceLastTest = now - this.lastQueueTestTime;
        
        if (this.queueWorkerTestInProgress || this.refreshInProgress) {
            console.log("Operation already in progress, ignoring click");
            return;
        }
        
        if (timeSinceLastTest < this.debounceDelay) {
            console.log("Debouncing retry request");
            if (this.clickTimeouts.has('retry')) {
                clearTimeout(this.clickTimeouts.get('retry'));
            }
            
            const timeoutId = setTimeout(() => {
                this.retryQueueWorkerTest();
                this.clickTimeouts.delete('retry');
            }, this.debounceDelay - timeSinceLastTest);
            
            this.clickTimeouts.set('retry', timeoutId);
            return;
        }
        
        this.lastQueueTestTime = now;
        this.retryQueueWorkerTest();
    }

    async refreshAllStatuses() {
        this.refreshInProgress = true;
        this.setLoadingState(true);
        
        // Simulate async operation
        await new Promise(resolve => setTimeout(resolve, 100));
        
        this.refreshInProgress = false;
        this.setLoadingState(false);
    }

    async testQueueWorker() {
        this.queueWorkerTestInProgress = true;
        this.setQueueWorkerTestButtonState(true);
        
        // Simulate async operation
        await new Promise(resolve => setTimeout(resolve, 100));
        
        this.queueWorkerTestInProgress = false;
        this.setQueueWorkerTestButtonState(false);
    }

    async retryQueueWorkerTest() {
        return this.testQueueWorker();
    }

    setLoadingState(isLoading) {
        this.refreshInProgress = isLoading;
        this.setRefreshButtonState(isLoading);
        this.setQueueWorkerTestButtonState(this.queueWorkerTestInProgress || isLoading);
    }

    setRefreshButtonState(isLoading) {
        const button = document.getElementById("refresh-status-btn");
        const buttonText = document.getElementById("refresh-btn-text");
        const spinner = document.getElementById("refresh-spinner");

        if (button && buttonText && spinner) {
            button.disabled = isLoading || this.queueWorkerTestInProgress;
            buttonText.textContent = isLoading ? "Checking..." : "Check Status";

            if (isLoading) {
                spinner.classList.remove("hidden");
                button.classList.add("cursor-not-allowed", "opacity-75");
            } else {
                spinner.classList.add("hidden");
                button.classList.remove("cursor-not-allowed", "opacity-75");
            }

            if (button.disabled) {
                button.setAttribute("aria-disabled", "true");
                button.setAttribute("title", "Please wait for current operation to complete");
            } else {
                button.removeAttribute("aria-disabled");
                button.setAttribute("title", "Check all setup statuses");
            }
        }
    }

    setQueueWorkerTestButtonState(isLoading) {
        const testBtn = document.getElementById("test-queue-worker-btn");
        const testBtnText = document.getElementById("test-queue-worker-btn-text");
        const testSpinner = document.getElementById("test-queue-worker-spinner");
        const retryBtn = document.getElementById("retry-queue-worker-btn");

        if (testBtn && testBtnText) {
            testBtn.disabled = isLoading || this.refreshInProgress;
            testBtnText.textContent = isLoading ? "Testing..." : "Test Queue Worker";

            if (testSpinner) {
                if (isLoading) {
                    testSpinner.classList.remove("hidden");
                } else {
                    testSpinner.classList.add("hidden");
                }
            }

            if (testBtn.disabled) {
                testBtn.classList.add("cursor-not-allowed", "opacity-75");
                testBtn.setAttribute("aria-disabled", "true");
                testBtn.setAttribute("title", "Please wait for current operation to complete");
            } else {
                testBtn.classList.remove("cursor-not-allowed", "opacity-75");
                testBtn.removeAttribute("aria-disabled");
                testBtn.setAttribute("title", "Test queue worker functionality");
            }
        }

        if (retryBtn) {
            retryBtn.disabled = isLoading || this.refreshInProgress;
            if (retryBtn.disabled) {
                retryBtn.classList.add("cursor-not-allowed", "opacity-75");
                retryBtn.setAttribute("aria-disabled", "true");
            } else {
                retryBtn.classList.remove("cursor-not-allowed", "opacity-75");
                retryBtn.removeAttribute("aria-disabled");
            }
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

    clearAllTimeouts() {
        this.clickTimeouts.forEach((timeoutId) => {
            clearTimeout(timeoutId);
        });
        this.clickTimeouts.clear();
    }
}

describe('Button State Management', () => {
    let manager;
    let refreshButton, refreshText, refreshSpinner;
    let testButton, testText, testSpinner;
    let retryButton;

    beforeEach(() => {
        // Clear all mocks
        mockElements.clear();
        mockTimeouts.clear();
        jest.clearAllMocks();

        // Create mock elements
        const refreshElements = createMockButton('refresh-status-btn', 'refresh-btn-text', 'refresh-spinner');
        refreshButton = refreshElements.button;
        refreshText = refreshElements.textElement;
        refreshSpinner = refreshElements.spinnerElement;

        const testElements = createMockButton('test-queue-worker-btn', 'test-queue-worker-btn-text', 'test-queue-worker-spinner');
        testButton = testElements.button;
        testText = testElements.textElement;
        testSpinner = testElements.spinnerElement;

        const retryElements = createMockButton('retry-queue-worker-btn');
        retryButton = retryElements.button;

        // Create manager instance
        manager = new SetupStatusManager({ autoInit: false });
    });

    afterEach(() => {
        // Clean up timeouts
        manager?.clearAllTimeouts();
        
        // Restore original functions
        global.setTimeout = originalSetTimeout;
        global.clearTimeout = originalClearTimeout;
    });

    describe('Refresh Button State Management', () => {
        it('should disable refresh button during refresh operation', async () => {
            manager.setRefreshButtonState(true);

            expect(refreshButton.disabled).toBe(true);
            expect(refreshText.textContent).toBe('Checking...');
            expect(refreshSpinner.classList.remove).toHaveBeenCalledWith('hidden');
            expect(refreshButton.classList.add).toHaveBeenCalledWith('cursor-not-allowed', 'opacity-75');
            expect(refreshButton.setAttribute).toHaveBeenCalledWith('aria-disabled', 'true');
        });

        it('should re-enable refresh button after refresh completion', () => {
            manager.setRefreshButtonState(false);

            expect(refreshButton.disabled).toBe(false);
            expect(refreshText.textContent).toBe('Check Status');
            expect(refreshSpinner.classList.add).toHaveBeenCalledWith('hidden');
            expect(refreshButton.classList.remove).toHaveBeenCalledWith('cursor-not-allowed', 'opacity-75');
            expect(refreshButton.removeAttribute).toHaveBeenCalledWith('aria-disabled');
        });

        it('should disable refresh button when queue worker test is in progress', () => {
            manager.queueWorkerTestInProgress = true;
            manager.setRefreshButtonState(false);

            expect(refreshButton.disabled).toBe(true);
        });
    });

    describe('Queue Worker Test Button State Management', () => {
        it('should disable test button during test operation', () => {
            manager.setQueueWorkerTestButtonState(true);

            expect(testButton.disabled).toBe(true);
            expect(testText.textContent).toBe('Testing...');
            expect(testSpinner.classList.remove).toHaveBeenCalledWith('hidden');
            expect(testButton.classList.add).toHaveBeenCalledWith('cursor-not-allowed', 'opacity-75');
            expect(testButton.setAttribute).toHaveBeenCalledWith('aria-disabled', 'true');
        });

        it('should re-enable test button after test completion', () => {
            manager.setQueueWorkerTestButtonState(false);

            expect(testButton.disabled).toBe(false);
            expect(testText.textContent).toBe('Test Queue Worker');
            expect(testSpinner.classList.add).toHaveBeenCalledWith('hidden');
            expect(testButton.classList.remove).toHaveBeenCalledWith('cursor-not-allowed', 'opacity-75');
            expect(testButton.removeAttribute).toHaveBeenCalledWith('aria-disabled');
        });

        it('should disable test button when refresh is in progress', () => {
            manager.refreshInProgress = true;
            manager.setQueueWorkerTestButtonState(false);

            expect(testButton.disabled).toBe(true);
        });

        it('should disable retry button during test operation', () => {
            manager.setQueueWorkerTestButtonState(true);

            expect(retryButton.disabled).toBe(true);
            expect(retryButton.classList.add).toHaveBeenCalledWith('cursor-not-allowed', 'opacity-75');
            expect(retryButton.setAttribute).toHaveBeenCalledWith('aria-disabled', 'true');
        });
    });

    describe('Debouncing Functionality', () => {
        it('should prevent rapid refresh button clicks', () => {
            const mockEvent = { preventDefault: jest.fn() };
            const refreshSpy = jest.spyOn(manager, 'refreshAllStatuses');

            // First click should work
            manager.debouncedRefreshAllStatuses(mockEvent);
            expect(refreshSpy).toHaveBeenCalledTimes(1);

            // Immediate second click should be debounced
            manager.lastRefreshTime = Date.now() - 500; // 500ms ago
            manager.debouncedRefreshAllStatuses(mockEvent);
            expect(refreshSpy).toHaveBeenCalledTimes(1); // Still only called once
        });

        it('should prevent rapid queue test button clicks', () => {
            const mockEvent = { preventDefault: jest.fn() };
            const testSpy = jest.spyOn(manager, 'testQueueWorker');

            // First click should work
            manager.debouncedTestQueueWorker(mockEvent);
            expect(testSpy).toHaveBeenCalledTimes(1);

            // Immediate second click should be debounced
            manager.lastQueueTestTime = Date.now() - 500; // 500ms ago
            manager.debouncedTestQueueWorker(mockEvent);
            expect(testSpy).toHaveBeenCalledTimes(1); // Still only called once
        });

        it('should ignore clicks when operations are in progress', () => {
            const mockEvent = { preventDefault: jest.fn() };
            const refreshSpy = jest.spyOn(manager, 'refreshAllStatuses');
            const testSpy = jest.spyOn(manager, 'testQueueWorker');

            // Set operations in progress
            manager.refreshInProgress = true;
            manager.queueWorkerTestInProgress = true;

            manager.debouncedRefreshAllStatuses(mockEvent);
            manager.debouncedTestQueueWorker(mockEvent);

            expect(refreshSpy).not.toHaveBeenCalled();
            expect(testSpy).not.toHaveBeenCalled();
        });

        it('should clear timeouts when clearAllTimeouts is called', () => {
            const mockEvent = { preventDefault: jest.fn() };
            
            // Set up a debounced operation
            manager.lastRefreshTime = Date.now() - 500;
            manager.debouncedRefreshAllStatuses(mockEvent);
            
            expect(manager.clickTimeouts.size).toBeGreaterThan(0);
            
            manager.clearAllTimeouts();
            
            expect(manager.clickTimeouts.size).toBe(0);
            expect(global.clearTimeout).toHaveBeenCalled();
        });
    });

    describe('Retry Button Management', () => {
        it('should show retry button', () => {
            manager.showRetryButton();

            expect(retryButton.classList.remove).toHaveBeenCalledWith('hidden');
            expect(retryButton.disabled).toBe(false);
            expect(retryButton.classList.remove).toHaveBeenCalledWith('cursor-not-allowed', 'opacity-75');
        });

        it('should hide retry button', () => {
            manager.hideRetryButton();

            expect(retryButton.classList.add).toHaveBeenCalledWith('hidden');
        });
    });

    describe('Coordinated Button States', () => {
        it('should coordinate button states during refresh operation', async () => {
            await manager.refreshAllStatuses();

            // Both buttons should be disabled during refresh
            expect(refreshButton.disabled).toBe(true);
            expect(testButton.disabled).toBe(true);
        });

        it('should coordinate button states during queue test operation', async () => {
            await manager.testQueueWorker();

            // Both buttons should be disabled during queue test
            expect(refreshButton.disabled).toBe(true);
            expect(testButton.disabled).toBe(true);
        });

        it('should properly re-enable buttons after operations complete', async () => {
            // Start both operations
            manager.refreshInProgress = true;
            manager.queueWorkerTestInProgress = true;
            
            manager.setLoadingState(true);
            manager.setQueueWorkerTestButtonState(true);

            // Complete operations
            manager.refreshInProgress = false;
            manager.queueWorkerTestInProgress = false;
            
            manager.setLoadingState(false);
            manager.setQueueWorkerTestButtonState(false);

            expect(refreshButton.disabled).toBe(false);
            expect(testButton.disabled).toBe(false);
        });
    });

    describe('Accessibility Features', () => {
        it('should set proper ARIA attributes when buttons are disabled', () => {
            manager.setRefreshButtonState(true);
            manager.setQueueWorkerTestButtonState(true);

            expect(refreshButton.setAttribute).toHaveBeenCalledWith('aria-disabled', 'true');
            expect(testButton.setAttribute).toHaveBeenCalledWith('aria-disabled', 'true');
            expect(retryButton.setAttribute).toHaveBeenCalledWith('aria-disabled', 'true');
        });

        it('should remove ARIA attributes when buttons are enabled', () => {
            manager.setRefreshButtonState(false);
            manager.setQueueWorkerTestButtonState(false);

            expect(refreshButton.removeAttribute).toHaveBeenCalledWith('aria-disabled');
            expect(testButton.removeAttribute).toHaveBeenCalledWith('aria-disabled');
            expect(retryButton.removeAttribute).toHaveBeenCalledWith('aria-disabled');
        });

        it('should set appropriate title attributes for disabled buttons', () => {
            manager.setRefreshButtonState(true);
            manager.setQueueWorkerTestButtonState(true);

            expect(refreshButton.setAttribute).toHaveBeenCalledWith('title', 'Please wait for current operation to complete');
            expect(testButton.setAttribute).toHaveBeenCalledWith('title', 'Please wait for current operation to complete');
        });
    });
});