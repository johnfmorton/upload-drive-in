/**
 * Setup Status Management JavaScript
 *
 * Handles AJAX status refresh functionality for the setup instructions page.
 * Provides real-time status updates, error handling, and retry logic.
 */

// Import performance-optimized polling service
import QueueWorkerPollingService from './queue-worker-polling.js';

// Note: Shoelace components are imported in app.js to avoid conflicts

class SetupStatusManager {
    constructor(options = {}) {
        // Separate general status steps from queue worker
        this.generalStatusSteps = [
            "database",
            "mail",
            "google_drive",
            "migrations",
            "admin_user",
        ];
        
        // Keep statusSteps for backward compatibility
        this.statusSteps = [
            "database",
            "mail",
            "google_drive",
            "migrations",
            "admin_user",
            "queue_worker",
        ];
        
        this.refreshInProgress = false;
        this.queueWorkerTestInProgress = false;
        this.retryAttempts = 0;
        this.maxRetryAttempts = 3;
        this.retryDelay = 2000; // 2 seconds
        this.autoRefreshInterval = null;
        this.autoRefreshEnabled = false;
        this.autoInit = options.autoInit !== false; // Default to true unless explicitly disabled

        // Debouncing properties
        this.lastRefreshTime = 0;
        this.lastQueueTestTime = 0;
        this.debounceDelay = 1000; // 1 second debounce
        this.clickTimeouts = new Map(); // Track timeouts for different buttons
        
        // Performance-optimized polling service
        this.pollingService = new QueueWorkerPollingService({
            baseInterval: 1000,
            maxInterval: 30000,
            requestTimeout: 15000,
            onStatusUpdate: this.handlePollingStatusUpdate.bind(this),
            onError: this.handlePollingError.bind(this),
            onMetricsUpdate: this.handlePollingMetrics.bind(this)
        });

        // Bind methods to maintain context
        this.refreshAllStatuses = this.refreshAllStatuses.bind(this);
        this.refreshGeneralStatuses = this.refreshGeneralStatuses.bind(this);
        this.refreshSingleStep = this.refreshSingleStep.bind(this);
        this.handleRefreshError = this.handleRefreshError.bind(this);
        this.retryRefresh = this.retryRefresh.bind(this);
        this.getCachedQueueWorkerStatus = this.getCachedQueueWorkerStatus.bind(this);
        this.triggerQueueWorkerTest = this.triggerQueueWorkerTest.bind(this);

        if (this.autoInit) {
            this.init();
        }
    }

    /**
     * Initialize the status manager
     */
    init() {
        this.setupCSRFToken();
        this.bindEventListeners();
        this.setupKeyboardNavigation();
        this.loadCachedQueueWorkerStatus();
    }

    /**
     * Setup CSRF token for AJAX requests
     */
    setupCSRFToken() {
        // Add CSRF token meta tag if not present
        if (!document.querySelector('meta[name="csrf-token"]')) {
            const meta = document.createElement("meta");
            meta.name = "csrf-token";
            meta.content =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "";
            document.head.appendChild(meta);
        }
    }

    /**
     * Bind event listeners
     */
    bindEventListeners() {
        // Main refresh button with debouncing
        const refreshButton = document.getElementById("refresh-status-btn");
        if (refreshButton) {
            refreshButton.addEventListener("click", (e) => {
                this.debouncedRefreshAllStatuses(e);
            });
        }



        // Individual step refresh buttons (if they exist)
        this.statusSteps.forEach((step) => {
            const stepRefreshBtn = document.getElementById(
                `refresh-${step}-btn`
            );
            if (stepRefreshBtn) {
                stepRefreshBtn.addEventListener("click", () => {
                    if (step === "queue_worker") {
                        this.triggerQueueWorkerTest();
                    } else {
                        this.refreshSingleStep(step);
                    }
                });
            }
        });

        // Auto-refresh toggle (if it exists)
        const autoRefreshToggle = document.getElementById(
            "auto-refresh-toggle"
        );
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener("change", (e) => {
                this.toggleAutoRefresh(e.target.checked);
            });
        }

        // Retry button (dynamically created)
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("retry-refresh-btn")) {
                this.retryRefresh();
            }
        });

        // Queue worker test button with debouncing
        const testQueueWorkerBtn = document.getElementById(
            "test-queue-worker-btn"
        );
        if (testQueueWorkerBtn) {
            testQueueWorkerBtn.addEventListener("click", (e) => {
                this.debouncedTestQueueWorker(e);
            });
        }


    }

    /**
     * Setup keyboard navigation for accessibility
     */
    setupKeyboardNavigation() {
        document.addEventListener("keydown", (e) => {
            // Ctrl/Cmd + R to refresh status (prevent default browser refresh)
            if (
                (e.ctrlKey || e.metaKey) &&
                e.key === "r" &&
                !this.refreshInProgress
            ) {
                e.preventDefault();
                this.refreshAllStatuses();
            }
        });
    }

    /**
     * Get CSRF token from meta tag
     */
    getCSRFToken() {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");
        if (!token) {
            console.warn("CSRF token not found");
        }
        return token;
    }

    /**
     * Debounced refresh all statuses to prevent rapid clicking
     */
    debouncedRefreshAllStatuses(event) {
        event.preventDefault();
        
        const now = Date.now();
        const timeSinceLastRefresh = now - this.lastRefreshTime;
        
        // If already in progress or too soon since last refresh, ignore
        if (this.refreshInProgress || this.queueWorkerTestInProgress) {
            console.log("Operation already in progress, ignoring click");
            return;
        }
        
        if (timeSinceLastRefresh < this.debounceDelay) {
            console.log("Debouncing refresh request");
            // Clear any existing timeout for this button
            if (this.clickTimeouts.has('refresh')) {
                clearTimeout(this.clickTimeouts.get('refresh'));
            }
            
            // Set new timeout
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

    /**
     * Debounced test queue worker to prevent rapid clicking
     */
    debouncedTestQueueWorker(event) {
        event.preventDefault();
        
        const now = Date.now();
        const timeSinceLastTest = now - this.lastQueueTestTime;
        
        // If already in progress or too soon since last test, ignore
        if (this.queueWorkerTestInProgress || this.refreshInProgress) {
            console.log("Operation already in progress, ignoring click");
            return;
        }
        
        if (timeSinceLastTest < this.debounceDelay) {
            console.log("Debouncing queue test request");
            // Clear any existing timeout for this button
            if (this.clickTimeouts.has('queueTest')) {
                clearTimeout(this.clickTimeouts.get('queueTest'));
            }
            
            // Set new timeout
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

    /**
     * Debounced retry queue worker test to prevent rapid clicking
     */
    debouncedRetryQueueWorkerTest(event) {
        event.preventDefault();
        
        const now = Date.now();
        const timeSinceLastTest = now - this.lastQueueTestTime;
        
        // If already in progress or too soon since last test, ignore
        if (this.queueWorkerTestInProgress || this.refreshInProgress) {
            console.log("Operation already in progress, ignoring click");
            return;
        }
        
        if (timeSinceLastTest < this.debounceDelay) {
            console.log("Debouncing retry request");
            // Clear any existing timeout for this button
            if (this.clickTimeouts.has('retry')) {
                clearTimeout(this.clickTimeouts.get('retry'));
            }
            
            // Set new timeout
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

    /**
     * Refresh all step statuses via AJAX (now includes queue worker test)
     */
    async refreshAllStatuses() {
        if (this.refreshInProgress) {
            console.log("Refresh already in progress, skipping...");
            return;
        }

        try {
            this.setLoadingState(true);
            this.clearErrorMessages();

            // Run general status refresh and queue worker test in parallel
            const [generalStatusResult, queueWorkerResult] = await Promise.allSettled([
                this.refreshGeneralStatuses(),
                this.triggerQueueWorkerTest()
            ]);

            // Handle general status results
            if (generalStatusResult.status === 'fulfilled') {
                this.updateLastChecked();
                this.resetRetryAttempts();
            } else {
                console.error("General status refresh failed:", generalStatusResult.reason);
                this.handleRefreshError(generalStatusResult.reason, "general");
            }

            // Queue worker test result is handled by triggerQueueWorkerTest method
            if (queueWorkerResult.status === 'rejected') {
                console.error("Queue worker test failed:", queueWorkerResult.reason);
            }

            // Show success feedback if at least general status succeeded
            if (generalStatusResult.status === 'fulfilled') {
                this.showSuccessMessage("Status refreshed successfully");
            }
        } catch (error) {
            console.error("Error refreshing all statuses:", error);
            this.handleRefreshError(error, "all");
        } finally {
            this.setLoadingState(false);
            
            // Ensure queue worker button is also properly re-enabled
            if (!this.queueWorkerTestInProgress) {
                this.setQueueWorkerTestButtonState(false);
            }
        }
    }

    /**
     * Refresh only general status steps (excluding queue worker)
     */
    async refreshGeneralStatuses() {
        const response = await this.makeAjaxRequest(
            "/setup/status/refresh",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.getCSRFToken(),
                    "X-Requested-With": "XMLHttpRequest",
                },
            }
        );

        if (!response.success) {
            throw new Error(
                response.error?.message || "Failed to refresh status"
            );
        }

        // Update only general step statuses (excluding queue_worker)
        this.updateGeneralStepStatuses(response.data.statuses);
        return response;
    }

    /**
     * Refresh a single step status via AJAX
     */
    async refreshSingleStep(stepName) {
        if (this.refreshInProgress) {
            console.log("Refresh already in progress, skipping...");
            return;
        }

        if (!this.statusSteps.includes(stepName)) {
            console.error("Invalid step name:", stepName);
            return;
        }

        try {
            this.setSingleStepLoadingState(stepName, true);

            const response = await this.makeAjaxRequest(
                "/setup/status/refresh-step",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": this.getCSRFToken(),
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify({ step: stepName }),
                }
            );

            if (!response.success) {
                throw new Error(
                    response.error?.message ||
                        `Failed to refresh ${stepName} status`
                );
            }

            // Update the specific step status
            const details = response.data.status.details;
            const detailsToPass = typeof details === 'object' ? JSON.stringify(details) : (details || response.data.status.message);
            
            this.updateStatusIndicator(
                stepName,
                response.data.status.status,
                response.data.status.message,
                detailsToPass
            );

            this.updateLastChecked();
            this.showSuccessMessage(
                `${response.data.status.step_name} status refreshed`
            );
        } catch (error) {
            console.error(`Error refreshing ${stepName} status:`, error);
            this.handleRefreshError(error, stepName);
        } finally {
            this.setSingleStepLoadingState(stepName, false);
        }
    }

    /**
     * Make AJAX request with timeout and error handling
     */
    async makeAjaxRequest(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(
                    errorData.error?.message ||
                        `HTTP ${response.status}: ${response.statusText}`
                );
            }

            return await response.json();
        } catch (error) {
            clearTimeout(timeoutId);

            if (error.name === "AbortError") {
                throw new Error(
                    "Request timed out. Please check your connection and try again."
                );
            }

            throw error;
        }
    }

    /**
     * Update all step status indicators
     */
    updateAllStepStatuses(statuses) {
        this.statusSteps.forEach((step) => {
            if (statuses && statuses[step]) {
                const stepData = statuses[step];
                const details = stepData.details;
                const detailsToPass = typeof details === 'object' ? JSON.stringify(details) : (details || stepData.message);
                
                this.updateStatusIndicator(
                    step,
                    stepData.status,
                    stepData.message,
                    detailsToPass
                );
            } else {
                console.warn(`No status data found for step: ${step}`);
                this.updateStatusIndicator(
                    step,
                    "error",
                    "No Data",
                    "Status information not available"
                );
            }
        });
    }

    /**
     * Update only general step status indicators (excluding queue worker)
     */
    updateGeneralStepStatuses(statuses) {
        this.generalStatusSteps.forEach((step) => {
            if (statuses && statuses[step]) {
                const stepData = statuses[step];
                const details = stepData.details;
                const detailsToPass = typeof details === 'object' ? JSON.stringify(details) : (details || stepData.message);
                
                this.updateStatusIndicator(
                    step,
                    stepData.status,
                    stepData.message,
                    detailsToPass
                );
            } else {
                console.warn(`No status data found for step: ${step}`);
                this.updateStatusIndicator(
                    step,
                    "error",
                    "No Data",
                    "Status information not available"
                );
            }
        });
    }

    /**
     * Update a single status indicator
     */
    updateStatusIndicator(stepName, status, message, details = null) {
        const indicator = document.getElementById(`status-${stepName}`);
        const text = document.getElementById(`status-${stepName}-text`);
        const detailsText = document.getElementById(`details-${stepName}-text`);

        if (!indicator || !text) {
            console.error(
                `Could not find status elements for step: ${stepName}`
            );
            return;
        }

        // Remove all status classes
        const statusClasses = [
            "status-completed",
            "status-working",
            "status-idle",
            "status-incomplete",
            "status-error",
            "status-checking",
            "status-cannot-verify",
            "status-needs_attention",
        ];
        indicator.classList.remove(...statusClasses);

        // Add new status class
        indicator.classList.add(`status-${status}`);

        // Update text with animation
        this.animateTextChange(text, message);

        // Update icon based on status
        this.updateStatusIcon(indicator, status);

        // Update details if provided
        if (detailsText && details) {
            // Ensure details is properly formatted for display
            let processedDetails = details;
            if (typeof details === 'object' && details !== null) {
                // If it's an object, try to extract meaningful information
                if (details.message) {
                    processedDetails = details.message;
                } else if (details.error_message) {
                    processedDetails = details.error_message;
                } else {
                    // Convert object to JSON string as fallback
                    processedDetails = JSON.stringify(details);
                }
            }
            this.updateStatusDetails(stepName, status, processedDetails);
        }

        // Add accessibility attributes
        indicator.setAttribute("aria-label", `${stepName} status: ${message}`);
    }

    /**
     * Update status icon based on status
     */
    updateStatusIcon(indicator, status) {
        console.log(`SetupStatusManager: Updating icon for status: ${status}`);

        // Look for both SVG and emoji icon containers
        let iconElement = indicator.querySelector("svg");
        let isEmojiIcon = false;

        // If no SVG found, look for emoji icon container
        if (!iconElement) {
            iconElement = indicator.querySelector(".status-emoji");
            isEmojiIcon = true;
            console.log("SetupStatusManager: Found emoji icon element");
        }

        // If still no icon element, create an emoji container
        if (!iconElement) {
            console.log("SetupStatusManager: Creating new emoji icon element");
            iconElement = document.createElement("span");
            iconElement.className = "status-emoji w-4 h-4 mr-1.5 text-base";
            // Insert before the text element
            const textElement = indicator.querySelector("span");
            if (textElement) {
                indicator.insertBefore(iconElement, textElement);
            } else {
                indicator.appendChild(iconElement);
            }
            isEmojiIcon = true;
        }

        const statusEmojis = {
            completed: "✅",
            working: "✅",      // Queue worker is actively processing jobs
            idle: "✅",         // Queue worker is idle but functioning properly
            incomplete: "❌",
            error: "🚫",
            failed: "🚫",       // Queue worker test failed
            timeout: "⏰",      // Queue worker test timed out
            "cannot-verify": "❓",
            needs_attention: "⚠️",
            checking: "🔄",
            not_tested: "❓",   // Queue worker not tested yet
        };

        const emoji = statusEmojis[status] || statusEmojis.checking;
        console.log(`SetupStatusManager: Setting emoji to: ${emoji}`);

        if (isEmojiIcon) {
            iconElement.textContent = emoji;
        } else {
            // For backward compatibility with existing SVG icons, replace with emoji
            const emojiSpan = document.createElement("span");
            emojiSpan.className = "status-emoji w-4 h-4 mr-1.5 text-base";
            emojiSpan.textContent = emoji;
            iconElement.parentNode.replaceChild(emojiSpan, iconElement);
        }
    }

    /**
     * Animate text change for better UX
     */
    animateTextChange(element, newText) {
        element.style.opacity = "0.5";
        setTimeout(() => {
            element.textContent = newText;
            element.style.opacity = "1";
        }, 150);
    }

    /**
     * Set loading state for all status checks
     */
    setLoadingState(isLoading) {
        this.refreshInProgress = isLoading;

        // Handle main refresh button
        this.setRefreshButtonState(isLoading);

        // Disable queue worker test button during general refresh
        this.setQueueWorkerTestButtonState(this.queueWorkerTestInProgress || isLoading);

        // Set only general steps to checking state if loading
        if (isLoading) {
            this.generalStatusSteps.forEach((step) => {
                this.updateStatusIndicator(
                    step,
                    "checking",
                    "Checking...",
                    "Verifying configuration..."
                );
            });
        }
    }

    /**
     * Set refresh button state with loading spinner and debouncing
     */
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

            // Add visual feedback for disabled state
            if (button.disabled) {
                button.setAttribute("aria-disabled", "true");
                button.setAttribute("title", "Please wait for current operation to complete");
            } else {
                button.removeAttribute("aria-disabled");
                button.setAttribute("title", "Check all setup statuses");
            }
        }
    }

    /**
     * Set loading state for a single step
     */
    setSingleStepLoadingState(stepName, isLoading) {
        if (isLoading) {
            this.updateStatusIndicator(
                stepName,
                "checking",
                "Checking...",
                "Verifying configuration..."
            );
        }
    }

    /**
     * Handle refresh errors with retry logic
     */
    handleRefreshError(error, context = "all") {
        this.retryAttempts++;

        console.error(`Refresh error (attempt ${this.retryAttempts}):`, error);

        if (this.retryAttempts < this.maxRetryAttempts) {
            // Show retry message
            this.showRetryMessage(error.message, context);

            // Auto-retry after delay
            setTimeout(() => {
                if (context === "all") {
                    this.refreshAllStatuses();
                } else {
                    this.refreshSingleStep(context);
                }
            }, this.retryDelay * this.retryAttempts);
        } else {
            // Max retries reached, show error state
            this.showErrorState(error.message, context);
            this.resetRetryAttempts();
        }
    }

    /**
     * Show retry message to user
     */
    showRetryMessage(errorMessage, context) {
        const message = `Failed to refresh status (${errorMessage}). Retrying in ${
            (this.retryDelay * this.retryAttempts) / 1000
        } seconds... (Attempt ${this.retryAttempts}/${this.maxRetryAttempts})`;
        this.showMessage(message, "warning");
    }

    /**
     * Show error state when max retries reached
     */
    showErrorState(errorMessage, context) {
        if (context === "all") {
            this.statusSteps.forEach((step) => {
                this.updateStatusIndicator(
                    step,
                    "error",
                    "Check Failed",
                    "Unable to verify status. Please check your connection and try again."
                );
            });
        } else {
            this.updateStatusIndicator(
                context,
                "error",
                "Check Failed",
                "Unable to verify status. Please check your connection and try again."
            );
        }

        this.showMessage(
            `Failed to refresh status: ${errorMessage}. Please check your connection and try again.`,
            "error",
            true // Show retry button
        );
    }

    /**
     * Show toast notification using simple browser notifications
     */
    showMessage(message, type = "info", showRetryButton = false) {
        console.log(`${type.toUpperCase()}: ${message}`);
        
        // For now, use simple console logging and browser alerts for critical messages
        if (type === 'error' || showRetryButton) {
            // Show error messages as alerts
            alert(`${type.toUpperCase()}: ${message}`);
            
            if (showRetryButton) {
                const retry = confirm('Would you like to retry now?');
                if (retry) {
                    this.retryRefresh();
                }
            }
        } else if (type === 'success') {
            // Show success messages briefly
            console.log(`SUCCESS: ${message}`);
        }
    }





    /**
     * Hide retry button
     */
    hideRetryButton() {
        const retryBtn = document.querySelector(".retry-queue-test-btn");
        if (retryBtn) {
            retryBtn.remove();
        }
    }

    /**
     * Handle polling status updates from the performance-optimized polling service
     * 
     * @param {Object} data - Status data from polling
     */
    handlePollingStatusUpdate(data) {
        console.log('Polling status update received', data);
        
        // Update queue worker status display
        if (data.status && data.message) {
            this.updateQueueWorkerStatus(data.status, data.message, data.details);
        }
        
        // Handle completion or failure
        if (['completed', 'failed', 'timeout', 'error'].includes(data.status)) {
            this.queueWorkerTestInProgress = false;
            this.setQueueWorkerTestButtonState(false);
            
            if (data.status === 'completed') {
                this.showSuccessMessage('Queue worker test completed successfully');
            } else {
                this.showQueueWorkerErrorDetails(data);
            }
        }
    }
    
    /**
     * Handle polling errors from the performance-optimized polling service
     * 
     * @param {Error} error - The error that occurred
     * @param {number} retryCount - Current retry count
     */
    handlePollingError(error, retryCount) {
        console.error('Polling error occurred', {
            error: error.message,
            retryCount
        });
        
        // Show error message if max retries reached
        if (retryCount >= this.pollingService.maxRetries) {
            this.queueWorkerTestInProgress = false;
            this.setQueueWorkerTestButtonState(false);
            
            this.updateQueueWorkerStatus(
                'error',
                'Failed to check queue worker status',
                `Network error: ${error.message}`
            );
            
            this.showErrorMessage(
                `Queue worker test failed: ${error.message}. Please check your connection and try again.`
            );
        }
    }
    
    /**
     * Handle polling metrics updates for performance monitoring
     * 
     * @param {Object} metrics - Performance metrics
     */
    handlePollingMetrics(metrics) {
        // Log performance metrics for debugging
        if (metrics.averageResponseTime > 2000) { // 2 seconds
            console.warn('Slow polling performance detected', {
                averageResponseTime: Math.round(metrics.averageResponseTime),
                successRate: Math.round(metrics.successRate),
                totalRequests: metrics.totalRequests
            });
        }
        
        // Update performance indicators in UI if needed
        this.updatePerformanceIndicators(metrics);
    }
    
    /**
     * Update performance indicators in the UI
     * 
     * @param {Object} metrics - Performance metrics
     */
    updatePerformanceIndicators(metrics) {
        // This could update a performance indicator in the UI
        // For now, we'll just log significant performance issues
        
        if (metrics.successRate < 80 && metrics.totalRequests > 5) {
            console.warn('Low success rate detected in queue worker polling', {
                successRate: Math.round(metrics.successRate),
                totalRequests: metrics.totalRequests,
                consecutiveErrors: metrics.consecutiveErrors
            });
        }
    }

    /**
     * Show queue worker error details with troubleshooting guidance
     */
    showQueueWorkerErrorDetails(status) {
        const errorDetailsContainer = document.getElementById("queue-test-error-details");
        const errorMessage = document.getElementById("queue-test-error-message");
        const troubleshootingContent = document.getElementById("queue-test-troubleshooting-content");

        if (errorDetailsContainer && errorMessage) {
            // Set error message
            errorMessage.textContent = status.error_message || 'Test failed with unknown error';

            // Populate troubleshooting steps if available
            if (troubleshootingContent && status.troubleshooting && Array.isArray(status.troubleshooting)) {
                troubleshootingContent.innerHTML = '';
                status.troubleshooting.forEach(step => {
                    const stepElement = document.createElement('div');
                    stepElement.className = 'text-xs text-red-600 mb-1';
                    stepElement.innerHTML = `• ${this.escapeHtml(step)}`;
                    troubleshootingContent.appendChild(stepElement);
                });
            }

            // Show the error details container
            errorDetailsContainer.classList.remove("hidden");
        }
    }

    /**
     * Hide queue worker error details
     */
    hideQueueWorkerErrorDetails() {
        const errorDetailsContainer = document.getElementById("queue-test-error-details");
        if (errorDetailsContainer) {
            errorDetailsContainer.classList.add("hidden");
        }
    }

    /**
     * Show queue worker success details
     */
    showQueueWorkerSuccessDetails(status) {
        const successDetailsContainer = document.getElementById("queue-test-success-details");
        const successMessage = document.getElementById("queue-test-success-message");
        const processingTime = document.getElementById("queue-test-processing-time");

        if (successDetailsContainer && successMessage) {
            // Set success message
            successMessage.textContent = status.message || 'Queue worker is functioning properly';

            // Set processing time if available
            if (processingTime && status.processing_time) {
                processingTime.textContent = `Processing time: ${status.processing_time.toFixed(2)} seconds`;
            }

            // Show the success details container
            successDetailsContainer.classList.remove("hidden");
        }
    }

    /**
     * Hide queue worker success details
     */
    hideQueueWorkerSuccessDetails() {
        const successDetailsContainer = document.getElementById("queue-test-success-details");
        if (successDetailsContainer) {
            successDetailsContainer.classList.add("hidden");
        }
    }

    /**
     * Show queue worker progress details
     */
    showQueueWorkerProgressDetails(status) {
        const progressContainer = document.getElementById("queue-test-progress");
        const progressText = document.getElementById("queue-test-progress-text");

        if (progressContainer && progressText) {
            progressText.textContent = status.message || 'Testing queue worker...';
            progressContainer.classList.remove("hidden");
        }
    }

    /**
     * Hide queue worker progress details
     */
    hideQueueWorkerProgressDetails() {
        const progressContainer = document.getElementById("queue-test-progress");
        if (progressContainer) {
            progressContainer.classList.add("hidden");
        }
    }

    /**
     * Escape HTML to prevent XSS in troubleshooting content
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Get troubleshooting steps based on error type
     */
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

    /**
     * Clear all pending click timeouts
     */
    clearAllTimeouts() {
        this.clickTimeouts.forEach((timeoutId) => {
            clearTimeout(timeoutId);
        });
        this.clickTimeouts.clear();
    }

    /**
     * Get Shoelace icon name for message type
     */
    getIconName(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-triangle',
            info: 'info-circle',
        };
        return icons[type] || icons.info;
    }

    /**
     * Show success message
     */
    showSuccessMessage(message) {
        this.showMessage(message, "success");
    }

    /**
     * Show error message
     */
    showErrorMessage(message, showRetryButton = false) {
        this.showMessage(message, "error", showRetryButton);
    }

    /**
     * Clear all toast messages
     */
    clearMessages() {
        // Clear any existing console messages (no-op for now)
        console.log('Clearing messages...');
    }

    /**
     * Clear error messages specifically
     */
    clearErrorMessages() {
        // Clear error messages (no-op for now)
        console.log('Clearing error messages...');
    }

    /**
     * Load cached queue worker status on page load
     */
    async loadCachedQueueWorkerStatus() {
        try {
            const cachedStatus = await this.getCachedQueueWorkerStatus();
            if (cachedStatus && !this.isStatusExpired(cachedStatus)) {
                this.updateQueueWorkerStatusFromCache(cachedStatus);
            } else {
                this.updateStatusIndicator(
                    "queue_worker",
                    "not_tested",
                    "Click the Test Queue Worker button below",
                    "No recent test results available"
                );
            }
        } catch (error) {
            console.error("Error loading cached queue worker status:", error);
            this.updateStatusIndicator(
                "queue_worker",
                "not_tested",
                "Click the Test Queue Worker button below",
                "Unable to load cached status"
            );
        }
    }

    /**
     * Get cached queue worker status from server
     */
    async getCachedQueueWorkerStatus() {
        try {
            const response = await this.makeAjaxRequest(
                "/setup/queue-worker/status",
                {
                    method: "GET",
                    headers: {
                        "X-CSRF-TOKEN": this.getCSRFToken(),
                        "X-Requested-With": "XMLHttpRequest",
                    },
                }
            );

            if (response.success && response.data && response.data.queue_worker) {
                return response.data.queue_worker;
            }
            return null;
        } catch (error) {
            console.error("Error fetching cached queue worker status:", error);
            return null;
        }
    }

    /**
     * Check if cached status is expired (older than 1 hour)
     */
    isStatusExpired(status) {
        if (!status.test_completed_at) {
            return true;
        }

        const testTime = new Date(status.test_completed_at);
        const now = new Date();
        const hourInMs = 60 * 60 * 1000; // 1 hour in milliseconds
        
        return (now - testTime) > hourInMs;
    }

    /**
     * Update queue worker status from cached data
     */
    updateQueueWorkerStatusFromCache(cachedStatus) {
        let statusClass, message, details;

        switch (cachedStatus.status) {
            case 'completed':
                statusClass = 'completed';
                message = 'Queue worker is functioning properly';
                details = `Last tested: ${this.getTimeAgo(new Date(cachedStatus.test_completed_at))}`;
                if (cachedStatus.processing_time) {
                    details += ` (${cachedStatus.processing_time.toFixed(2)}s)`;
                }
                this.hideRetryButton();
                this.hideQueueWorkerErrorDetails();
                this.showQueueWorkerSuccessDetails(cachedStatus);
                break;
            case 'failed':
                statusClass = 'error';
                message = cachedStatus.message || 'Queue worker test failed';
                details = cachedStatus.error_message || 'Test failed with unknown error';
                this.hideQueueWorkerSuccessDetails();
                this.showQueueWorkerErrorDetails(cachedStatus);
                break;
            case 'timeout':
                statusClass = 'timeout';
                message = cachedStatus.message || 'Queue worker test timed out';
                details = cachedStatus.error_message || 'The queue worker may not be running';
                this.hideQueueWorkerSuccessDetails();
                this.showQueueWorkerErrorDetails(cachedStatus);
                break;
            case 'error':
                statusClass = 'error';
                message = cachedStatus.message || 'Error checking queue worker status';
                details = cachedStatus.error_message || 'System error occurred';
                this.hideQueueWorkerSuccessDetails();
                this.showQueueWorkerErrorDetails(cachedStatus);
                break;
            case 'testing':
                statusClass = 'checking';
                message = cachedStatus.message || 'Testing queue worker...';
                details = 'Test in progress...';
                this.hideRetryButton();
                this.hideQueueWorkerErrorDetails();
                this.hideQueueWorkerSuccessDetails();
                this.showQueueWorkerProgressDetails(cachedStatus);
                break;
            default:
                statusClass = 'not_tested';
                message = 'Click the Test Queue Worker button below';
                details = 'No recent test results available';
                this.hideRetryButton();
                this.hideQueueWorkerErrorDetails();
                this.hideQueueWorkerSuccessDetails();
        }

        this.updateStatusIndicator("queue_worker", statusClass, message, details);
    }

    /**
     * Trigger queue worker test (used by both refresh all and test button)
     */
    async triggerQueueWorkerTest() {
        if (this.queueWorkerTestInProgress) {
            console.log("Queue worker test already in progress, skipping...");
            return;
        }

        try {
            this.queueWorkerTestInProgress = true;
            
            // Update both button states
            this.setQueueWorkerTestButtonState(true);
            this.setRefreshButtonState(this.refreshInProgress);
            
            // Update queue worker status to testing
            this.updateStatusIndicator(
                "queue_worker",
                "checking",
                "Testing queue worker...",
                "Dispatching test job..."
            );

            // Call the existing testQueueWorker logic but without UI button management
            await this.performQueueWorkerTest();
            
        } catch (error) {
            console.error("Queue worker test failed:", error);
            
            // Ensure we pass a proper string for the error details
            let errorDetails = "Unknown error occurred";
            if (error && typeof error === 'object') {
                errorDetails = error.message || error.toString() || "Unknown error occurred";
            } else if (typeof error === 'string') {
                errorDetails = error;
            }
            
            this.updateStatusIndicator(
                "queue_worker",
                "error",
                "Test failed",
                errorDetails
            );

        } finally {
            this.queueWorkerTestInProgress = false;
            
            // Re-enable buttons
            this.setQueueWorkerTestButtonState(false);
            this.setRefreshButtonState(this.refreshInProgress);
        }
    }

    /**
     * Set queue worker test button state with enhanced visual feedback
     */
    setQueueWorkerTestButtonState(isLoading) {
        const testBtn = document.getElementById("test-queue-worker-btn");
        const testBtnText = document.getElementById("test-queue-worker-btn-text");
        const testSpinner = document.getElementById("test-queue-worker-spinner");
        const retryBtn = document.getElementById("retry-queue-worker-btn");

        if (testBtn && testBtnText) {
            testBtn.disabled = isLoading || this.refreshInProgress;
            testBtnText.textContent = isLoading ? "Testing..." : "Test Queue Worker";

            // Handle spinner visibility
            if (testSpinner) {
                if (isLoading) {
                    testSpinner.classList.remove("hidden");
                } else {
                    testSpinner.classList.add("hidden");
                }
            }

            // Add visual feedback for disabled state
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

        // Also disable retry button during testing
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

    /**
     * Perform the actual queue worker test with progressive status updates and enhanced error handling
     */
    async performQueueWorkerTest() {
        try {
            // Phase 1: Dispatching test job
            this.updateStatusIndicator(
                "queue_worker",
                "checking",
                "Testing queue worker...",
                "Dispatching test job..."
            );

            // Dispatch test job with enhanced error handling
            const response = await this.makeAjaxRequest("/setup/queue/test", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.getCSRFToken(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({
                    delay: 0,
                    timeout: 30 // Configurable timeout
                }),
            });

            if (!response.success) {
                throw new Error(response.error?.message || response.message || "Failed to dispatch test job");
            }

            if (!response.test_job_id) {
                throw new Error("No test job ID returned from server");
            }

            // Phase 2: Job dispatched successfully, start polling
            this.updateStatusIndicator(
                "queue_worker",
                "checking",
                "Test job queued...",
                "Waiting for queue worker to pick up job..."
            );

            // Poll for results with enhanced error handling
            await this.pollQueueTestResultWithEnhancedErrorHandling(response.test_job_id);

        } catch (error) {
            console.error("Queue worker test failed:", error);
            this.handleQueueWorkerTestError(error);
        }
    }

    /**
     * Handle queue worker test errors with specific error types and retry options
     */
    handleQueueWorkerTestError(error) {
        let statusClass = "error";
        let message = "Test failed";
        
        // Ensure we extract a proper string from the error
        let details = "Unknown error occurred";
        if (error && typeof error === 'object') {
            details = error.message || error.toString() || "Unknown error occurred";
        } else if (typeof error === 'string') {
            details = error;
        }
        
        let showRetryButton = true;

        // Determine error type and provide specific guidance
        if (this.isDispatchError(error)) {
            statusClass = "error";
            message = "Failed to dispatch test job";
            details = this.getDispatchErrorDetails(error);
        } else if (this.isNetworkError(error)) {
            statusClass = "error";
            message = "Network error during test";
            details = this.getNetworkErrorDetails(error);
        } else if (this.isTimeoutError(error)) {
            statusClass = "timeout";
            message = "Queue worker test timed out";
            details = this.getTimeoutErrorDetails(error);
        } else {
            // Generic error
            details = `${error.message}. Check the application logs for more details.`;
        }

        // Update status indicator with error information
        this.updateStatusIndicator("queue_worker", statusClass, message, details);

        // Show retry button in the details section
        if (showRetryButton) {
            this.addRetryButtonToQueueWorkerStatus();
        }
    }

    /**
     * Check if error is a dispatch-related error
     */
    isDispatchError(error) {
        const message = error.message.toLowerCase();
        return message.includes('dispatch') || 
               message.includes('queue connection') || 
               message.includes('database connection') ||
               message.includes('table') ||
               message.includes('configuration');
    }

    /**
     * Check if error is network-related
     */
    isNetworkError(error) {
        const message = error.message.toLowerCase();
        return message.includes('network') || 
               message.includes('connection refused') || 
               message.includes('timeout') ||
               message.includes('unreachable') ||
               message.includes('fetch');
    }

    /**
     * Check if error is timeout-related
     */
    isTimeoutError(error) {
        const message = error.message.toLowerCase();
        return message.includes('timeout') || message.includes('timed out');
    }

    /**
     * Get detailed error information for dispatch errors
     */
    getDispatchErrorDetails(error) {
        return `
            <div class="space-y-2">
                <p class="text-sm text-red-700">${error.message}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Verify queue configuration in .env file (QUEUE_CONNECTION)</li>
                        <li>Check if database tables exist: php artisan migrate</li>
                        <li>Ensure queue driver is properly configured</li>
                        <li>Check application logs for configuration errors</li>
                    </ul>
                </div>
            </div>
        `;
    }

    /**
     * Get detailed error information for network errors
     */
    getNetworkErrorDetails(error) {
        return `
            <div class="space-y-2">
                <p class="text-sm text-red-700">${error.message}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Check your internet connection</li>
                        <li>Verify the application server is accessible</li>
                        <li>Check for firewall or proxy issues</li>
                        <li>Try refreshing the page and testing again</li>
                    </ul>
                </div>
            </div>
        `;
    }

    /**
     * Get detailed error information for timeout errors
     */
    getTimeoutErrorDetails(error) {
        return `
            <div class="space-y-2">
                <p class="text-sm text-yellow-700">${error.message}</p>
                <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                    <h4 class="font-medium text-yellow-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                        <li>Ensure queue worker is running: php artisan queue:work</li>
                        <li>Check if worker process is stuck or crashed</li>
                        <li>Verify queue driver configuration</li>
                        <li>Check system resources (CPU, memory, disk space)</li>
                        <li>Review worker logs for errors or warnings</li>
                    </ul>
                </div>
            </div>
        `;
    }

    /**
     * Add retry button to queue worker status details
     */
    addRetryButtonToQueueWorkerStatus() {
        const detailsElement = document.getElementById("details-queue_worker-text");
        if (detailsElement) {
            // Check if retry button already exists
            if (!detailsElement.querySelector('.retry-queue-test-btn')) {
                const retryButton = document.createElement('button');
                retryButton.className = 'retry-queue-test-btn mt-3 inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150';
                retryButton.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Retry Test
                `;
                retryButton.addEventListener('click', () => {
                    this.retryQueueWorkerTest();
                });
                
                detailsElement.appendChild(retryButton);
            }
        }
    }

    /**
     * Retry queue worker test
     */
    async retryQueueWorkerTest() {
        console.log("Retrying queue worker test...");

        // Hide error details and retry button
        this.hideQueueWorkerErrorDetails();
        this.hideRetryButton();

        // Remove any existing retry buttons (legacy)
        const existingRetryBtn = document.querySelector('.retry-queue-test-btn');
        if (existingRetryBtn) {
            existingRetryBtn.remove();
        }

        // Clear any error messages
        this.clearErrorMessages();

        // Reset any previous test state
        this.queueWorkerTestInProgress = false;

        // Restart the test
        await this.testQueueWorker();
    }



    /**
     * Show queue worker error details with troubleshooting
     */
    showQueueWorkerErrorDetails(errorMessage, errorType = 'general') {
        const errorDetails = document.getElementById("queue-test-error-details");
        const errorMessageEl = document.getElementById("queue-test-error-message");
        const troubleshootingContent = document.getElementById("queue-test-troubleshooting-content");
        
        if (errorDetails && errorMessageEl) {
            errorMessageEl.textContent = errorMessage;
            
            // Add troubleshooting steps based on error type
            if (troubleshootingContent) {
                troubleshootingContent.innerHTML = this.getTroubleshootingSteps(errorType);
            }
            
            errorDetails.classList.remove("hidden");
            errorDetails.classList.add("error-message");
            
            // Show retry button
            this.showRetryButton();
        }
    }

    /**
     * Hide queue worker error details
     */
    hideQueueWorkerErrorDetails() {
        const errorDetails = document.getElementById("queue-test-error-details");
        if (errorDetails) {
            errorDetails.classList.add("hidden");
            errorDetails.classList.remove("error-message");
        }
    }

    /**
     * Show queue worker success details
     */
    showQueueWorkerSuccessDetails(message, processingTime = null) {
        const successDetails = document.getElementById("queue-test-success-details");
        const successMessage = document.getElementById("queue-test-success-message");
        const processingTimeEl = document.getElementById("queue-test-processing-time");
        
        if (successDetails && successMessage) {
            successMessage.textContent = message;
            
            if (processingTime && processingTimeEl) {
                processingTimeEl.textContent = `Processing time: ${processingTime}s`;
            }
            
            successDetails.classList.remove("hidden");
            successDetails.classList.add("success-message");
            
            // Hide retry button on success
            this.hideRetryButton();
        }
    }

    /**
     * Hide queue worker success details
     */
    hideQueueWorkerSuccessDetails() {
        const successDetails = document.getElementById("queue-test-success-details");
        if (successDetails) {
            successDetails.classList.add("hidden");
            successDetails.classList.remove("success-message");
        }
    }

    /**
     * Show progressive queue worker test status
     */
    showQueueWorkerProgress(message, details = null) {
        const progressEl = document.getElementById("queue-test-progress");
        const progressText = document.getElementById("queue-test-progress-text");
        const progressDetails = document.getElementById("queue-test-progress-details");
        
        if (progressEl && progressText) {
            progressText.textContent = message;
            
            if (details && progressDetails) {
                progressDetails.textContent = details;
                progressDetails.classList.remove("hidden");
            } else if (progressDetails) {
                progressDetails.classList.add("hidden");
            }
            
            progressEl.classList.remove("hidden");
            progressEl.classList.add("queue-test-progress");
        }
    }

    /**
     * Hide queue worker progress
     */
    hideQueueWorkerProgress() {
        const progressEl = document.getElementById("queue-test-progress");
        if (progressEl) {
            progressEl.classList.add("hidden");
            progressEl.classList.remove("queue-test-progress");
        }
    }

    /**
     * Get troubleshooting steps based on error type
     */
    getTroubleshootingSteps(errorType) {
        const steps = {
            'dispatch_failed': [
                'Check that your database connection is working properly',
                'Verify that the jobs table exists (run migrations if needed)',
                'Ensure your queue configuration in .env is correct',
                'Check Laravel logs for detailed error information'
            ],
            'timeout': [
                'Verify that a queue worker is running (php artisan queue:work)',
                'Check if the worker process is stuck or crashed',
                'Restart the queue worker process',
                'Check system resources (CPU, memory) on your server',
                'Review worker logs for any error messages'
            ],
            'job_failed': [
                'Check Laravel logs for the specific job failure reason',
                'Verify file permissions in storage directories',
                'Ensure all required dependencies are installed',
                'Check if there are any configuration issues',
                'Try running the queue worker with --tries=1 for debugging'
            ],
            'network_error': [
                'Check your internet connection',
                'Verify that the application server is running',
                'Check for any firewall or proxy issues',
                'Try refreshing the page and testing again'
            ],
            'general': [
                'Check Laravel logs in storage/logs/laravel.log',
                'Verify that the queue worker is running',
                'Ensure database connection is working',
                'Check system resources and server status',
                'Try running: php artisan queue:work --tries=1'
            ]
        };
        
        const errorSteps = steps[errorType] || steps['general'];
        return errorSteps.map(step => `<div class="troubleshooting-step">• ${step}</div>`).join('');
    }

    /**
     * Poll for queue test results with enhanced error handling and recovery
     */
    async pollQueueTestResultWithEnhancedErrorHandling(testJobId) {
        const maxAttempts = 30; // 30 seconds
        const maxNetworkRetries = 3;
        let attempts = 0;
        let networkRetries = 0;
        const startTime = Date.now();

        const poll = async () => {
            attempts++;

            try {
                const response = await this.makeAjaxRequest(
                    `/setup/queue/test/status?test_job_id=${testJobId}`,
                    {
                        method: "GET",
                        headers: {
                            "X-CSRF-TOKEN": this.getCSRFToken(),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    }
                );

                // Reset network retry counter on successful request
                networkRetries = 0;

                if (!response.success || !response.status) {
                    throw new Error(response.error?.message || "Invalid response from server");
                }

                const status = response.status;
                const elapsedTime = ((Date.now() - startTime) / 1000).toFixed(1);

                // Handle different status states
                switch (status.status) {
                    case "completed":
                        this.updateStatusIndicator(
                            "queue_worker",
                            "completed",
                            "Queue worker is functioning properly",
                            `Test completed successfully in ${(status.processing_time || 0).toFixed(2)}s (total: ${elapsedTime}s)`
                        );
                        return;

                    case "failed":
                        const errorMessage = status.error_message || "Test job failed without specific error";
                        this.updateStatusIndicator(
                            "queue_worker",
                            "error",
                            "Test job execution failed",
                            this.getJobFailureDetails(errorMessage, elapsedTime)
                        );
                        this.addRetryButtonToQueueWorkerStatus();
                        return;

                    case "timeout":
                        this.updateStatusIndicator(
                            "queue_worker",
                            "timeout",
                            "Queue worker test timed out",
                            this.getTimeoutErrorDetails({ message: `Test timed out after ${elapsedTime}s. The queue worker may not be running.` })
                        );
                        this.addRetryButtonToQueueWorkerStatus();
                        return;

                    case "processing":
                        this.updateStatusIndicator(
                            "queue_worker",
                            "checking",
                            "Test job processing...",
                            `Job is being processed by queue worker (${elapsedTime}s elapsed)`
                        );
                        break;

                    case "pending":
                        this.updateStatusIndicator(
                            "queue_worker",
                            "checking",
                            "Test job queued...",
                            `Waiting for queue worker to pick up job (${elapsedTime}s elapsed)`
                        );
                        break;

                    default:
                        this.updateStatusIndicator(
                            "queue_worker",
                            "checking",
                            "Testing queue worker...",
                            `Checking test job status (${elapsedTime}s elapsed)`
                        );
                        break;
                }

                // Continue polling if not completed or failed
                if (status.status === "processing" || status.status === "pending") {
                    if (attempts < maxAttempts) {
                        setTimeout(poll, 1000);
                    } else {
                        // Final timeout
                        this.updateStatusIndicator(
                            "queue_worker",
                            "timeout",
                            "Queue worker test timed out",
                            this.getTimeoutErrorDetails({ message: `Test timed out after ${elapsedTime}s. The queue worker may not be running.` })
                        );
                        this.addRetryButtonToQueueWorkerStatus();
                    }
                }

            } catch (error) {
                console.error("Polling error:", error);
                
                // Handle network errors with retry logic
                if (this.isNetworkError(error) && networkRetries < maxNetworkRetries) {
                    networkRetries++;
                    console.log(`Network error during polling, retrying... (${networkRetries}/${maxNetworkRetries})`);
                    
                    // Show temporary network error message
                    const elapsedTime = ((Date.now() - startTime) / 1000).toFixed(1);
                    this.updateStatusIndicator(
                        "queue_worker",
                        "checking",
                        "Network error, retrying...",
                        `Connection issue during status check (${elapsedTime}s elapsed, retry ${networkRetries}/${maxNetworkRetries})`
                    );
                    
                    // Retry after a short delay
                    setTimeout(poll, 2000);
                    return;
                }
                
                // Final error - no more retries
                const elapsedTime = ((Date.now() - startTime) / 1000).toFixed(1);
                this.updateStatusIndicator(
                    "queue_worker",
                    "error",
                    "Error checking test status",
                    this.getPollingErrorDetails(error, elapsedTime)
                );
                this.addRetryButtonToQueueWorkerStatus();
            }
        };

        // Start polling
        poll();
    }

    /**
     * Get detailed error information for job execution failures
     */
    getJobFailureDetails(errorMessage, elapsedTime) {
        return `
            <div class="space-y-2">
                <p class="text-sm text-red-700">Job failed after ${elapsedTime}s: ${errorMessage}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Check failed jobs table: php artisan queue:failed</li>
                        <li>Review worker logs for specific error details</li>
                        <li>Ensure all required dependencies are installed</li>
                        <li>Check memory limits and execution time settings</li>
                        <li>Verify database connectivity from worker process</li>
                    </ul>
                </div>
            </div>
        `;
    }

    /**
     * Get detailed error information for polling errors
     */
    getPollingErrorDetails(error, elapsedTime) {
        return `
            <div class="space-y-2">
                <p class="text-sm text-red-700">Status check failed after ${elapsedTime}s: ${error.message}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Check your internet connection</li>
                        <li>Verify the application server is accessible</li>
                        <li>Try refreshing the page and testing again</li>
                        <li>Check application logs for server errors</li>
                    </ul>
                </div>
            </div>
        `;
    }

    /**
     * Poll for queue test result and update status indicator
     */
    async pollQueueTestResultForStatus(testJobId) {
        const maxAttempts = 30; // 30 seconds
        let attempts = 0;

        const poll = async () => {
            attempts++;

            try {
                const response = await this.makeAjaxRequest(
                    `/setup/queue/test/status?test_job_id=${testJobId}`,
                    {
                        method: "GET",
                        headers: {
                            "X-CSRF-TOKEN": this.getCSRFToken(),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    }
                );

                if (!response.success || !response.status) {
                    throw new Error("Invalid response from server");
                }

                const status = response.status;

                switch (status.status) {
                    case "completed":
                        this.updateStatusIndicator(
                            "queue_worker",
                            "completed",
                            `Queue worker is functioning properly (${(status.processing_time || 0).toFixed(2)}s)`,
                            `Test completed successfully at ${new Date().toLocaleTimeString()}`
                        );
                        return;

                    case "failed":
                        this.updateStatusIndicator(
                            "queue_worker",
                            "error",
                            "Queue worker test failed",
                            status.error_message || "Test job failed with unknown error"
                        );
                        return;

                    case "timeout":
                        this.updateStatusIndicator(
                            "queue_worker",
                            "error",
                            "Queue worker test timed out",
                            "The queue worker may not be running"
                        );
                        return;

                    case "processing":
                        this.updateStatusIndicator(
                            "queue_worker",
                            "checking",
                            "Test job processing...",
                            `Job is being processed by worker (${attempts}s elapsed)`
                        );
                        break;

                    case "pending":
                        this.updateStatusIndicator(
                            "queue_worker",
                            "checking",
                            "Test job queued...",
                            `Waiting for queue worker to pick up job (${attempts}s elapsed)`
                        );
                        break;

                    default:
                        this.updateStatusIndicator(
                            "queue_worker",
                            "checking",
                            "Testing queue worker...",
                            `Checking test job status (${attempts}s elapsed)`
                        );
                        break;
                }

                // Continue polling if not completed or failed
                if (status.status === "processing" || status.status === "pending") {
                    if (attempts < maxAttempts) {
                        setTimeout(poll, 1000);
                    } else {
                        this.updateStatusIndicator(
                            "queue_worker",
                            "timeout",
                            "Queue worker test timed out (30s)",
                            "The queue worker may not be running. Check if 'php artisan queue:work' is active."
                        );
                    }
                }
            } catch (error) {
                console.error("Polling error:", error);
                this.updateStatusIndicator(
                    "queue_worker",
                    "error",
                    "Error checking test status",
                    error.message || "Unknown error"
                );
            }
        };

        // Start polling
        poll();
    }

    /**
     * Test queue worker functionality (called by test button)
     */
    async testQueueWorker() {
        // Simply delegate to the enhanced queue worker test logic
        // This avoids duplication and conflicts with the existing implementation
        try {
            await this.triggerQueueWorkerTest();
        } catch (error) {
            console.error("Queue worker test failed:", error);
            // Error handling is already done in triggerQueueWorkerTest
        }
    }

    /**
     * Poll for queue test result with progressive status updates for test results section
     */
    async pollQueueTestResultWithProgressiveUpdates(testJobId, statusElement) {
        const maxAttempts = 30; // 30 seconds
        let attempts = 0;
        const startTime = Date.now();

        const poll = async () => {
            attempts++;

            try {
                const response = await this.makeAjaxRequest(
                    `/setup/queue/test/status?test_job_id=${testJobId}`,
                    {
                        method: "GET",
                        headers: {
                            "X-CSRF-TOKEN": this.getCSRFToken(),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    }
                );

                if (response.success && response.status) {
                    const status = response.status;
                    const elapsedTime = ((Date.now() - startTime) / 1000).toFixed(1);

                    switch (status.status) {
                        case "completed":
                            // Hide progress
                            this.hideQueueWorkerProgress();
                            
                            // Show success details
                            const processingTime = (status.processing_time || 0).toFixed(2);
                            this.showQueueWorkerSuccessDetails(
                                `Queue worker is functioning properly! Job completed in ${processingTime}s (total test time: ${elapsedTime}s)`,
                                processingTime
                            );
                            
                            // Update main status indicator
                            this.updateStatusIndicator(
                                "queue_worker",
                                "completed",
                                "Queue worker is functioning properly",
                                `Last tested: just now (${processingTime}s)`
                            );
                            
                            // Update test results section
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-green-700">Queue worker is functioning properly! Job completed in ${processingTime}s (total test time: ${elapsedTime}s)</span>
                                </div>
                            `;
                            return;

                        case "failed":
                            // Hide progress
                            this.hideQueueWorkerProgress();
                            
                            // Show error details
                            const errorMessage = status.error_message || "Unknown error";
                            this.showQueueWorkerErrorDetails(errorMessage, 'job_failed');
                            
                            // Update main status indicator
                            this.updateStatusIndicator(
                                "queue_worker",
                                "failed",
                                "Queue worker test failed",
                                `Test failed: ${errorMessage}`
                            );
                            
                            // Update test results section
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-red-700">Queue test failed: ${errorMessage} (after ${elapsedTime}s)</span>
                                </div>
                            `;
                            return;

                        case "timeout":
                            // Hide progress
                            this.hideQueueWorkerProgress();
                            
                            // Show error details for timeout
                            this.showQueueWorkerErrorDetails(
                                `Test timed out after ${elapsedTime}s. The queue worker may not be running.`,
                                'timeout'
                            );
                            
                            // Update main status indicator
                            this.updateStatusIndicator(
                                "queue_worker",
                                "timeout",
                                "Queue worker test timed out",
                                "The queue worker may not be running"
                            );
                            
                            // Update test results section
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test timed out after ${elapsedTime}s. The queue worker may not be running.</span>
                                </div>
                            `;
                            return;

                        case "processing":
                            // Show progress
                            this.showQueueWorkerProgress(
                                "Test job is being processed...",
                                `${elapsedTime}s elapsed`
                            );
                            
                            // Update test results section
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-700">Test job is being processed... (${elapsedTime}s elapsed)</span>
                                </div>
                            `;
                            break;

                        case "pending":
                            // Show progress
                            this.showQueueWorkerProgress(
                                "Test job is queued, waiting for worker...",
                                `${elapsedTime}s elapsed`
                            );
                            
                            // Update test results section
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="animate-pulse h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test job is queued, waiting for worker... (${elapsedTime}s elapsed)</span>
                                </div>
                            `;
                            break;

                        default:
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-700">Testing queue worker... (${elapsedTime}s elapsed)</span>
                                </div>
                            `;
                            break;
                    }

                    // Continue polling if not completed or failed
                    if (
                        status.status === "processing" ||
                        status.status === "pending"
                    ) {
                        if (attempts < maxAttempts) {
                            setTimeout(poll, 1000);
                        } else {
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test timed out after ${elapsedTime}s. The queue worker may not be running.</span>
                                </div>
                            `;
                        }
                    }
                } else {
                    throw new Error("Invalid response from server");
                }
            } catch (error) {
                console.error("Polling error:", error);
                const elapsedTime = ((Date.now() - startTime) / 1000).toFixed(1);
                statusElement.innerHTML = `
                    <div class="flex items-center">
                        <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-red-700">Error checking test status: ${error.message} (after ${elapsedTime}s)</span>
                    </div>
                `;
            }
        };

        // Start polling
        poll();
    }

    /**
     * Poll for queue test result (legacy method for backward compatibility)
     */
    async pollQueueTestResult(testJobId, statusElement) {
        const maxAttempts = 30; // 30 seconds
        let attempts = 0;

        const poll = async () => {
            attempts++;

            try {
                const response = await this.makeAjaxRequest(
                    `/setup/queue/test/status?test_job_id=${testJobId}`,
                    {
                        method: "GET",
                        headers: {
                            "X-CSRF-TOKEN": this.getCSRFToken(),
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    }
                );

                if (response.success && response.status) {
                    const status = response.status;

                    switch (status.status) {
                        case "completed":
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-green-700">Queue worker is functioning properly! Job completed in ${(
                                        status.processing_time || 0
                                    ).toFixed(2)}s</span>
                                </div>
                            `;
                            return;

                        case "failed":
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-red-700">Queue test failed: ${
                                        status.error_message || "Unknown error"
                                    }</span>
                                </div>
                            `;
                            return;

                        case "processing":
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-700">Test job is being processed... (${attempts}s)</span>
                                </div>
                            `;
                            break;

                        case "pending":
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="animate-pulse h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test job is queued, waiting for worker... (${attempts}s)</span>
                                </div>
                            `;
                            break;
                    }

                    // Continue polling if not completed or failed
                    if (
                        status.status === "processing" ||
                        status.status === "pending"
                    ) {
                        if (attempts < maxAttempts) {
                            setTimeout(poll, 1000);
                        } else {
                            statusElement.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test timed out after 30 seconds. The queue worker may not be running.</span>
                                </div>
                            `;
                        }
                    }
                } else {
                    throw new Error("Invalid response from server");
                }
            } catch (error) {
                console.error("Polling error:", error);
                statusElement.innerHTML = `
                    <div class="flex items-center">
                        <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-red-700">Error checking test status: ${error.message}</span>
                    </div>
                `;
            }
        };

        // Start polling
        poll();
    }

    /**
     * Update last checked timestamp
     */
    updateLastChecked() {
        const lastCheckedElement = document.getElementById("last-checked");
        const lastCheckedTimeElement =
            document.getElementById("last-checked-time");

        if (lastCheckedElement && lastCheckedTimeElement) {
            const now = new Date();
            lastCheckedTimeElement.textContent = now.toLocaleTimeString();
            lastCheckedElement.classList.remove("hidden");
        }
    }

    /**
     * Reset retry attempts counter
     */
    resetRetryAttempts() {
        this.retryAttempts = 0;
    }

    /**
     * Retry refresh manually
     */
    retryRefresh() {
        this.resetRetryAttempts();
        this.clearMessages();
        this.refreshAllStatuses();
    }

    /**
     * Toggle auto-refresh functionality
     */
    toggleAutoRefresh(enabled) {
        this.autoRefreshEnabled = enabled;

        if (enabled) {
            // Refresh every 30 seconds
            this.autoRefreshInterval = setInterval(() => {
                if (!this.refreshInProgress) {
                    this.refreshAllStatuses();
                }
            }, 30000);
        } else {
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
                this.autoRefreshInterval = null;
            }
        }
    }

    /**
     * Update detailed status information
     */
    updateStatusDetails(stepName, status, details) {
        const detailsText = document.getElementById(`details-${stepName}-text`);
        if (!detailsText) return;

        let detailsHtml = "";

        // Handle different types of details
        if (typeof details === "object" && details !== null) {
            // Try to parse as JSON string first (in case it was stringified)
            let parsedDetails = details;
            if (typeof details === "string") {
                try {
                    parsedDetails = JSON.parse(details);
                } catch (e) {
                    // If parsing fails, treat as regular string
                    detailsHtml = `<div>${details}</div>`;
                    detailsText.innerHTML = detailsHtml;
                    return;
                }
            }

            if (parsedDetails.checked_at) {
                const checkedAt = new Date(parsedDetails.checked_at);
                const timeAgo = this.getTimeAgo(checkedAt);
                detailsHtml += `<div class="mb-2"><strong>Last checked:</strong> ${timeAgo}</div>`;
            }

            // Add status-specific details
            detailsHtml += this.getStatusSpecificDetails(
                stepName,
                status,
                parsedDetails
            );

            // Add troubleshooting guidance for incomplete/error states
            if (
                status === "incomplete" ||
                status === "error" ||
                status === "cannot_verify"
            ) {
                detailsHtml += this.getTroubleshootingGuidance(
                    stepName,
                    status,
                    parsedDetails
                );
            }
        } else if (typeof details === "string") {
            // Handle string details - check if it's a JSON string
            try {
                const parsedDetails = JSON.parse(details);
                // If successful, recursively call with parsed object
                this.updateStatusDetails(stepName, status, parsedDetails);
                return;
            } catch (e) {
                // Not JSON, treat as regular string
                detailsHtml = `<div>${details}</div>`;
            }
        } else {
            // Handle null, undefined, or other types
            detailsHtml = `<div>${String(details || "No additional details available.")}</div>`;
        }

        detailsText.innerHTML = detailsHtml || "No additional details available.";
    }

    /**
     * Get status-specific details for display
     */
    getStatusSpecificDetails(stepName, status, details) {
        let html = "";

        switch (stepName) {
            case "queue_worker":
                if (details.recent_jobs !== undefined) {
                    html += `<div class="mb-2">
                        <strong>Queue Statistics:</strong>
                        <ul class="ml-4 mt-1 text-sm">
                            <li>Recent jobs (24h): ${details.recent_jobs}</li>
                            <li>Recent failed jobs: ${
                                details.recent_failed_jobs || 0
                            }</li>
                            <li>Total failed jobs: ${
                                details.total_failed_jobs || 0
                            }</li>
                            <li>Stalled jobs: ${details.stalled_jobs || 0}</li>
                        </ul>
                    </div>`;
                }
                break;

            case "database":
                // Handle new detailed database status
                if (details.scenario) {
                    html += this.getDatabaseStatusDetails(status, details);
                } else if (details.connection_name) {
                    html += `<div class="mb-2"><strong>Connection:</strong> ${details.connection_name}</div>`;
                }
                break;

            case "mail":
                if (details.driver) {
                    html += `<div class="mb-2"><strong>Mail driver:</strong> ${details.driver}</div>`;
                }
                break;

            case "google_drive":
                if (details.client_id) {
                    html += `<div class="mb-2"><strong>Client ID configured:</strong> Yes</div>`;
                }
                break;
        }

        if (details.error) {
            html += `<div class="mb-2 p-2 bg-red-50 border border-red-200 rounded">
                <strong>Error:</strong> ${details.error}
            </div>`;
        }

        return html;
    }

    /**
     * Get detailed database status information
     */
    getDatabaseStatusDetails(status, details) {
        const scenario = details.scenario;
        let html = '';

        switch (scenario) {
            case 'no_credentials':
                html += `
                    <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded">
                        <div class="flex items-start">
                            <span class="text-red-600 text-lg mr-2">❌</span>
                            <div>
                                <h4 class="text-sm font-medium text-red-800">No Database Credentials</h4>
                                <p class="mt-1 text-sm text-red-700">${details.description}</p>
                                <div class="mt-2">
                                    <p class="text-sm font-medium text-red-800">Missing fields:</p>
                                    <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                        ${details.metadata.missing_fields.map(field => `<li>${field}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                break;

            case 'partial_credentials':
                html += `
                    <div class="mb-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <div class="flex items-start">
                            <span class="text-yellow-600 text-lg mr-2">⚠️</span>
                            <div>
                                <h4 class="text-sm font-medium text-yellow-800">Partial Database Configuration</h4>
                                <p class="mt-1 text-sm text-yellow-700">${details.description}</p>
                                <div class="mt-2 grid grid-cols-1 gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-yellow-800">Missing fields:</p>
                                        <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                                            ${details.metadata.missing_fields.map(field => `<li>${field}</li>`).join('')}
                                        </ul>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-yellow-800">Configured fields:</p>
                                        <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                                            ${details.metadata.configured_fields.map(field => `<li>${field}</li>`).join('')}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                break;

            case 'connection_failed':
                html += `
                    <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded">
                        <div class="flex items-start">
                            <span class="text-red-600 text-lg mr-2">🚫</span>
                            <div>
                                <h4 class="text-sm font-medium text-red-800">Database Connection Failed</h4>
                                <p class="mt-1 text-sm text-red-700">${details.description}</p>
                                <div class="mt-2">
                                    <p class="text-sm font-medium text-red-800">Connection details:</p>
                                    <ul class="mt-1 text-sm text-red-700 space-y-1">
                                        <li><strong>Type:</strong> ${details.metadata.connection_type}</li>
                                        <li><strong>Host:</strong> ${details.metadata.host}</li>
                                        <li><strong>Database:</strong> ${details.metadata.database}</li>
                                        <li><strong>Username:</strong> ${details.metadata.username}</li>
                                    </ul>
                                    ${details.metadata.error_message ? `
                                        <div class="mt-2 p-2 bg-red-100 rounded text-xs text-red-800">
                                            <strong>Error:</strong> ${details.metadata.error_message}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                break;

            case 'connection_successful':
                html += `
                    <div class="mb-3 p-3 bg-green-50 border border-green-200 rounded">
                        <div class="flex items-start">
                            <span class="text-green-600 text-lg mr-2">✅</span>
                            <div>
                                <h4 class="text-sm font-medium text-green-800">Database Connection Successful</h4>
                                <p class="mt-1 text-sm text-green-700">${details.description}</p>
                                <div class="mt-2">
                                    <p class="text-sm font-medium text-green-800">Connection details:</p>
                                    <ul class="mt-1 text-sm text-green-700 space-y-1">
                                        <li><strong>Type:</strong> ${details.metadata.connection_type}</li>
                                        <li><strong>Host:</strong> ${details.metadata.host}</li>
                                        <li><strong>Database:</strong> ${details.metadata.database}</li>
                                        <li><strong>Username:</strong> ${details.metadata.username}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                break;

            default:
                html += `<div class="mb-2"><strong>Status:</strong> ${details.description || 'Database status information available'}</div>`;
        }

        return html;
    }

    /**
     * Get troubleshooting guidance based on step and status
     */
    getTroubleshootingGuidance(stepName, status, details) {
        let html =
            '<div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">';

        if (status === "cannot_verify") {
            html += "<strong>Cannot Verify - Manual Check Required:</strong>";
            html +=
                '<p class="text-sm mt-1 mb-2">The system cannot automatically verify this step. Please check manually:</p>';
        } else {
            html += "<strong>Troubleshooting:</strong>";
        }

        html += '<ul class="ml-4 mt-2 text-sm">';

        switch (stepName) {
            case "database":
                if (status === "cannot_verify") {
                    html +=
                        "<li>Check your database connection settings in .env file</li>";
                    html += "<li>Ensure your database server is running</li>";
                    html += "<li>Verify database credentials are correct</li>";
                    html +=
                        "<li><strong>Manual verification:</strong> Try running <code>php artisan migrate:status</code></li>";
                } else if (status === "incomplete") {
                    html +=
                        "<li>Run database migrations: <code>php artisan migrate</code></li>";
                    html += "<li>Check if all required tables exist</li>";
                }
                break;

            case "mail":
                if (status === "cannot_verify") {
                    html += "<li>Check mail configuration in .env file</li>";
                    html +=
                        "<li>For local development, consider using log driver</li>";
                    html +=
                        "<li>Verify SMTP credentials if using external mail service</li>";
                    html +=
                        "<li><strong>Manual verification:</strong> Try sending a test email or check logs</li>";
                }
                break;

            case "google_drive":
                if (status === "incomplete") {
                    html += "<li>Set GOOGLE_DRIVE_CLIENT_ID in .env file</li>";
                    html +=
                        "<li>Set GOOGLE_DRIVE_CLIENT_SECRET in .env file</li>";
                    html +=
                        "<li>Complete OAuth setup in Google Cloud Console</li>";
                }
                break;

            case "queue_worker":
                if (status === "cannot_verify") {
                    html +=
                        "<li>Start queue worker: <code>php artisan queue:work</code></li>";
                    html += "<li>Check if queue tables exist in database</li>";
                    html +=
                        "<li><strong>Manual verification:</strong> Use the test button above to verify functionality</li>";
                    html +=
                        "<li>Check queue status with: <code>php artisan queue:monitor</code></li>";
                } else if (status === "needs_attention") {
                    html +=
                        "<li>Check failed jobs: <code>php artisan queue:failed</code></li>";
                    html += "<li>Restart queue worker if needed</li>";
                    html += "<li>Review application logs for errors</li>";
                }
                break;

            case "admin_user":
                if (status === "incomplete") {
                    html +=
                        "<li>Create admin user: <code>php artisan make:admin</code></li>";
                    html += "<li>Or register through the web interface</li>";
                }
                break;

            case "migrations":
                if (status === "incomplete") {
                    html +=
                        "<li>Run migrations: <code>php artisan migrate</code></li>";
                    html += "<li>Check database connection first</li>";
                }
                break;
        }

        html += "</ul></div>";
        return html;
    }

    /**
     * Get human-readable time ago string
     */
    getTimeAgo(date) {
        const now = new Date();
        const diffMs = now - date;
        const diffSecs = Math.floor(diffMs / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);

        if (diffSecs < 60) {
            return "Just now";
        } else if (diffMins < 60) {
            return `${diffMins} minute${diffMins !== 1 ? "s" : ""} ago`;
        } else if (diffHours < 24) {
            return `${diffHours} hour${diffHours !== 1 ? "s" : ""} ago`;
        } else {
            return date.toLocaleString();
        }
    }

    /**
     * Toggle status details visibility
     */
    toggleStatusDetails(stepName) {
        const details = document.getElementById(`details-${stepName}`);
        if (details) {
            details.classList.toggle("show");
        }
    }

    /**
     * Cleanup when page is unloaded
     */
    cleanup() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
    }




}

// Global functions for backward compatibility
let setupStatusManager;

function toggleStatusDetails(stepName) {
    if (setupStatusManager) {
        setupStatusManager.toggleStatusDetails(stepName);
    }
}

// Explicitly attach to window object to ensure global availability
window.toggleStatusDetails = toggleStatusDetails;

// Export SetupStatusManager to global window object
window.SetupStatusManager = SetupStatusManager;

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    console.log("SetupStatusManager: DOM loaded, initializing...");
    window.setupStatusManager = new SetupStatusManager();
    console.log("SetupStatusManager: Instance created");

    // Cleanup on page unload
    window.addEventListener("beforeunload", () => {
        if (window.setupStatusManager) {
            window.setupStatusManager.cleanup();
        }
    });
});

// Export for testing
if (typeof module !== "undefined" && module.exports) {
    module.exports = SetupStatusManager;
}
