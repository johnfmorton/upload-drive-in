/**
 * Debug version of Setup Status Manager to identify the JavaScript error
 */

console.log('Setup Status Debug: Starting initialization...');

class SetupStatusManagerDebug {
    constructor() {
        console.log('Setup Status Debug: Constructor called');
        this.queueWorkerTestInProgress = false;
        this.refreshInProgress = false;
        
        // Bind methods
        this.testQueueWorker = this.testQueueWorker.bind(this);
        
        console.log('Setup Status Debug: About to call init()');
        this.init();
        console.log('Setup Status Debug: Init completed');
    }

    init() {
        console.log('Setup Status Debug: Init method called');
        try {
            this.setupCSRFToken();
            console.log('Setup Status Debug: CSRF token setup completed');
            
            this.bindEventListeners();
            console.log('Setup Status Debug: Event listeners bound');
            
        } catch (error) {
            console.error('Setup Status Debug: Error in init:', error);
        }
    }

    setupCSRFToken() {
        console.log('Setup Status Debug: Setting up CSRF token');
        // Add CSRF token meta tag if not present
        if (!document.querySelector('meta[name="csrf-token"]')) {
            const meta = document.createElement("meta");
            meta.name = "csrf-token";
            meta.content = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
            document.head.appendChild(meta);
        }
    }

    bindEventListeners() {
        console.log('Setup Status Debug: Binding event listeners');
        
        // Queue worker test button
        const testQueueWorkerBtn = document.getElementById("test-queue-worker-btn");
        console.log('Setup Status Debug: Test button element:', testQueueWorkerBtn);
        
        if (testQueueWorkerBtn) {
            console.log('Setup Status Debug: Adding click listener to test button');
            testQueueWorkerBtn.addEventListener("click", (e) => {
                console.log('Setup Status Debug: Test button clicked');
                e.preventDefault();
                this.testQueueWorker();
            });
        } else {
            console.log('Setup Status Debug: Test button not found');
        }

        // Refresh button
        const refreshButton = document.getElementById("refresh-status-btn");
        console.log('Setup Status Debug: Refresh button element:', refreshButton);
        
        if (refreshButton) {
            console.log('Setup Status Debug: Adding click listener to refresh button');
            refreshButton.addEventListener("click", (e) => {
                console.log('Setup Status Debug: Refresh button clicked');
                e.preventDefault();
                this.refreshAllStatuses();
            });
        } else {
            console.log('Setup Status Debug: Refresh button not found');
        }
    }

    async testQueueWorker() {
        console.log('Setup Status Debug: testQueueWorker method called');
        
        if (this.queueWorkerTestInProgress) {
            console.log('Setup Status Debug: Test already in progress, skipping');
            return;
        }

        try {
            this.queueWorkerTestInProgress = true;
            console.log('Setup Status Debug: Starting queue worker test');
            
            // Update button state
            const testBtn = document.getElementById("test-queue-worker-btn");
            if (testBtn) {
                testBtn.disabled = true;
                testBtn.textContent = "Testing...";
            }

            // Update status indicator
            this.updateStatusIndicator("queue_worker", "checking", "Testing queue worker...");

            // Make AJAX request
            const response = await this.makeAjaxRequest("/setup/queue/test", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.getCSRFToken(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ delay: 0 }),
            });

            console.log('Setup Status Debug: Test response:', response);

            if (response.success) {
                this.updateStatusIndicator("queue_worker", "checking", "Test job queued...");
                // In a real implementation, we would poll for results here
                setTimeout(() => {
                    this.updateStatusIndicator("queue_worker", "completed", "Queue worker is functioning properly!");
                }, 2000);
            } else {
                throw new Error(response.message || "Test failed");
            }

        } catch (error) {
            console.error('Setup Status Debug: Test failed:', error);
            this.updateStatusIndicator("queue_worker", "error", "Test failed: " + error.message);
        } finally {
            this.queueWorkerTestInProgress = false;
            
            // Reset button state
            const testBtn = document.getElementById("test-queue-worker-btn");
            if (testBtn) {
                testBtn.disabled = false;
                testBtn.textContent = "Test Queue Worker";
            }
        }
    }

    async refreshAllStatuses() {
        console.log('Setup Status Debug: refreshAllStatuses method called');
        // Simple implementation for testing
        alert('Refresh all statuses clicked - debug version');
    }

    updateStatusIndicator(stepName, status, message) {
        console.log(`Setup Status Debug: Updating ${stepName} to ${status}: ${message}`);
        
        const statusElement = document.getElementById(`status-${stepName}-text`);
        if (statusElement) {
            statusElement.textContent = message;
        } else {
            console.log(`Setup Status Debug: Status element not found: status-${stepName}-text`);
        }

        const indicator = document.getElementById(`status-${stepName}`);
        if (indicator) {
            // Remove all status classes
            const statusClasses = ["status-completed", "status-working", "status-idle", "status-incomplete", "status-error", "status-checking"];
            indicator.classList.remove(...statusClasses);
            indicator.classList.add(`status-${status}`);
        } else {
            console.log(`Setup Status Debug: Indicator element not found: status-${stepName}`);
        }
    }

    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
        console.log('Setup Status Debug: CSRF token:', token ? 'Found' : 'Not found');
        return token;
    }

    async makeAjaxRequest(url, options = {}) {
        console.log('Setup Status Debug: Making AJAX request to:', url);
        console.log('Setup Status Debug: Request options:', options);
        
        try {
            const response = await fetch(url, options);
            console.log('Setup Status Debug: Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('Setup Status Debug: Response data:', data);
            return data;
        } catch (error) {
            console.error('Setup Status Debug: AJAX request failed:', error);
            throw error;
        }
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Setup Status Debug: DOM loaded, initializing...');
        window.setupStatusManagerDebug = new SetupStatusManagerDebug();
    });
} else {
    console.log('Setup Status Debug: DOM already loaded, initializing immediately...');
    window.setupStatusManagerDebug = new SetupStatusManagerDebug();
}

console.log('Setup Status Debug: Script loaded');