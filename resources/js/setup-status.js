/**
 * Setup Status Management JavaScript
 *
 * Handles AJAX status refresh functionality for the setup instructions page.
 * Provides real-time status updates, error handling, and retry logic.
 */

// Import Shoelace components for toast notifications
import '@shoelace-style/shoelace/dist/components/alert/alert.js';
import '@shoelace-style/shoelace/dist/components/icon/icon.js';
import '@shoelace-style/shoelace/dist/components/button/button.js';

class SetupStatusManager {
    constructor(options = {}) {
        this.statusSteps = [
            "database",
            "mail",
            "google_drive",
            "migrations",
            "admin_user",
            "queue_worker",
        ];
        this.refreshInProgress = false;
        this.retryAttempts = 0;
        this.maxRetryAttempts = 3;
        this.retryDelay = 2000; // 2 seconds
        this.autoRefreshInterval = null;
        this.autoRefreshEnabled = false;
        this.autoInit = options.autoInit !== false; // Default to true unless explicitly disabled

        // Bind methods to maintain context
        this.refreshAllStatuses = this.refreshAllStatuses.bind(this);
        this.refreshSingleStep = this.refreshSingleStep.bind(this);
        this.handleRefreshError = this.handleRefreshError.bind(this);
        this.retryRefresh = this.retryRefresh.bind(this);

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
        // Main refresh button
        const refreshButton = document.getElementById("refresh-status-btn");
        if (refreshButton) {
            refreshButton.addEventListener("click", this.refreshAllStatuses);
        }



        // Individual step refresh buttons (if they exist)
        this.statusSteps.forEach((step) => {
            const stepRefreshBtn = document.getElementById(
                `refresh-${step}-btn`
            );
            if (stepRefreshBtn) {
                stepRefreshBtn.addEventListener("click", () => {
                    this.refreshSingleStep(step);
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

        // Queue worker test button
        const testQueueWorkerBtn = document.getElementById(
            "test-queue-worker-btn"
        );
        if (testQueueWorkerBtn) {
            testQueueWorkerBtn.addEventListener("click", () =>
                this.testQueueWorker()
            );
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
     * Refresh all step statuses via AJAX
     */
    async refreshAllStatuses() {
        if (this.refreshInProgress) {
            console.log("Refresh already in progress, skipping...");
            return;
        }

        try {
            this.setLoadingState(true);
            this.clearErrorMessages();

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

            // Update all step statuses
            this.updateAllStepStatuses(response.data.statuses);
            this.updateLastChecked();
            this.resetRetryAttempts();

            // Show success feedback
            this.showSuccessMessage("Status refreshed successfully");
        } catch (error) {
            console.error("Error refreshing all statuses:", error);
            this.handleRefreshError(error, "all");
        } finally {
            this.setLoadingState(false);
        }
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
            this.updateStatusIndicator(
                stepName,
                response.data.status.status,
                response.data.status.message,
                response.data.status.details || response.data.status.message
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
                this.updateStatusIndicator(
                    step,
                    stepData.status,
                    stepData.message,
                    stepData.details || stepData.message
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
            this.updateStatusDetails(stepName, status, details);
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
            incomplete: "❌",
            error: "🚫",
            "cannot-verify": "❓",
            needs_attention: "⚠️",
            checking: "🔄",
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
        const button = document.getElementById("refresh-status-btn");
        const buttonText = document.getElementById("refresh-btn-text");
        const spinner = document.getElementById("refresh-spinner");

        if (button && buttonText && spinner) {
            button.disabled = isLoading;
            buttonText.textContent = isLoading ? "Checking..." : "Check Status";

            if (isLoading) {
                spinner.classList.remove("hidden");
            } else {
                spinner.classList.add("hidden");
            }
        }



        // Set all steps to checking state if loading
        if (isLoading) {
            this.statusSteps.forEach((step) => {
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
     * Show toast notification using Shoelace alert component
     */
    showMessage(message, type = "info", showRetryButton = false) {
        // Clear any existing toast messages
        this.clearMessages();

        // Get or create toast container
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        // Map our types to Shoelace variants
        const variantMap = {
            'success': 'success',
            'error': 'danger',
            'warning': 'warning',
            'info': 'primary'
        };

        // Create Shoelace alert element
        const alert = document.createElement('sl-alert');
        alert.setAttribute('variant', variantMap[type] || 'primary');
        alert.setAttribute('closable', 'true');
        alert.className = 'status-message';
        
        // Add content
        let alertContent = `
            <sl-icon slot="icon" name="${this.getIconName(type)}"></sl-icon>
            ${message}
        `;

        // Add retry button if requested
        if (showRetryButton) {
            alertContent += `
                <sl-button slot="action" variant="text" size="small" class="retry-refresh-btn">
                    Retry Now
                </sl-button>
            `;
        }

        alert.innerHTML = alertContent;

        // Add to container
        toastContainer.appendChild(alert);

        // Show the alert with animation
        alert.toast();

        // Auto-dismiss success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.hide();
                }
            }, 5000);
        }

        // Handle retry button click if present
        if (showRetryButton) {
            const retryBtn = alert.querySelector('.retry-refresh-btn');
            if (retryBtn) {
                retryBtn.addEventListener('click', () => {
                    alert.hide();
                    this.retryRefresh();
                });
            }
        }
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
        const toastContainer = document.getElementById('toast-container');
        if (toastContainer) {
            // Hide all existing alerts
            toastContainer.querySelectorAll('sl-alert').forEach(alert => {
                alert.hide();
            });
        }
    }

    /**
     * Clear error messages specifically
     */
    clearErrorMessages() {
        const toastContainer = document.getElementById('toast-container');
        if (toastContainer) {
            // Hide error alerts (danger variant)
            toastContainer.querySelectorAll('sl-alert[variant="danger"]').forEach(alert => {
                alert.hide();
            });
        }
    }

    /**
     * Test queue worker functionality
     */
    async testQueueWorker() {
        const testBtn = document.getElementById("test-queue-worker-btn");
        const testBtnText = document.getElementById(
            "test-queue-worker-btn-text"
        );
        const testResults = document.getElementById("queue-test-results");
        const testStatus = document.getElementById("queue-test-status");

        if (!testBtn || !testBtnText || !testResults || !testStatus) {
            console.error("Queue test elements not found");
            return;
        }

        try {
            // Set loading state
            testBtn.disabled = true;
            testBtnText.textContent = "Testing...";
            testResults.classList.remove("hidden");
            testStatus.innerHTML = `
                <div class="flex items-center">
                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-blue-700">Dispatching test job...</span>
                </div>
            `;

            // Dispatch test job
            const response = await this.makeAjaxRequest("/setup/queue/test", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.getCSRFToken(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({
                    delay: 0,
                }),
            });

            if (response.success && response.test_job_id) {
                // Poll for results
                await this.pollQueueTestResult(
                    response.test_job_id,
                    testStatus
                );
            } else {
                throw new Error(
                    response.message || "Failed to dispatch test job"
                );
            }
        } catch (error) {
            console.error("Queue test failed:", error);
            testStatus.innerHTML = `
                <div class="flex items-center">
                    <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-red-700">Test failed: ${error.message}</span>
                </div>
            `;
        } finally {
            // Reset button state
            testBtn.disabled = false;
            testBtnText.textContent = "Test Queue Worker";
        }
    }

    /**
     * Poll for queue test result
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
        if (typeof details === "object") {
            if (details.checked_at) {
                const checkedAt = new Date(details.checked_at);
                const timeAgo = this.getTimeAgo(checkedAt);
                detailsHtml += `<div class="mb-2"><strong>Last checked:</strong> ${timeAgo}</div>`;
            }

            // Add status-specific details
            detailsHtml += this.getStatusSpecificDetails(
                stepName,
                status,
                details
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
                    details
                );
            }
        } else if (typeof details === "string") {
            detailsHtml = `<div>${details}</div>`;
        }

        detailsText.innerHTML =
            detailsHtml || "No additional details available.";
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
                if (details.connection_name) {
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

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    console.log("SetupStatusManager: DOM loaded, initializing...");
    setupStatusManager = new SetupStatusManager();
    console.log("SetupStatusManager: Instance created");

    // Cleanup on page unload
    window.addEventListener("beforeunload", () => {
        if (setupStatusManager) {
            setupStatusManager.cleanup();
        }
    });
});

// Export for testing
if (typeof module !== "undefined" && module.exports) {
    module.exports = SetupStatusManager;
}
