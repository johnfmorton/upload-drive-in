/**
 * Admin Dashboard Queue Testing Functionality
 * 
 * Handles queue worker testing interface including:
 * - Test job dispatch and monitoring
 * - Real-time progress tracking
 * - Queue health metrics display
 * - Historical test results
 */

class AdminQueueTesting {
    constructor() {
        this.currentTestJobId = null;
        this.testStartTime = null;
        this.pollingInterval = null;
        this.elapsedTimeInterval = null;
        
        this.initializeElements();
        this.bindEvents();
    }

    initializeElements() {
        // Main buttons
        this.testQueueBtn = document.getElementById('test-queue-btn');
        this.testQueueBtnText = document.getElementById('test-queue-btn-text');
        
        // Test results sections
        this.testResultsSection = document.getElementById('test-results-section');
        this.currentTestProgress = document.getElementById('current-test-progress');
        this.testProgressMessage = document.getElementById('test-progress-message');
        this.testElapsedTime = document.getElementById('test-elapsed-time');
        this.testResultsDisplay = document.getElementById('test-results-display');
    }

    bindEvents() {
        if (this.testQueueBtn) {
            this.testQueueBtn.addEventListener('click', () => this.startQueueTest());
        }
    }

    async startQueueTest() {
        if (this.currentTestJobId) {
            console.warn('Test already in progress');
            return;
        }

        try {
            this.setTestInProgress(true);
            this.testStartTime = Date.now();
            
            // Start elapsed time counter
            this.startElapsedTimeCounter();
            
            // Dispatch test job
            const response = await this.dispatchTestJob();
            
            if (response.success && response.data) {
                this.currentTestJobId = response.data.test_job_id;
                this.updateProgressMessage('Test job dispatched, waiting for processing...');
                
                // Start polling for results
                this.startPolling();
            } else {
                throw new Error(response.message || response.error?.message || 'Failed to dispatch test job');
            }
            
        } catch (error) {
            console.error('Queue test failed:', error);
            this.handleTestError(error.message);
        }
    }

    async dispatchTestJob() {
        const response = await fetch('/admin/queue/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                delay: 0 // No delay for admin tests
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    startPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }

        this.pollingInterval = setInterval(async () => {
            try {
                await this.checkTestJobStatus();
            } catch (error) {
                console.error('Polling error:', error);
                this.handleTestError('Failed to check test status');
            }
        }, 1000); // Poll every second

        // Set timeout for test (30 seconds)
        setTimeout(() => {
            if (this.currentTestJobId) {
                this.handleTestTimeout();
            }
        }, 30000);
    }

    async checkTestJobStatus() {
        if (!this.currentTestJobId) return;

        const response = await fetch(`/admin/queue/test/status?test_job_id=${this.currentTestJobId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        if (data.success && data.data && data.data.status) {
            const status = data.data.status;
            
            switch (status.status) {
                case 'completed':
                    this.handleTestSuccess(status);
                    break;
                case 'failed':
                    this.handleTestFailure(status);
                    break;
                case 'timeout':
                    this.handleTestTimeout();
                    break;
                case 'processing':
                    this.updateProgressMessage('Test job is being processed...');
                    break;
                case 'pending':
                    this.updateProgressMessage('Test job is queued, waiting for worker...');
                    break;
            }
        }
    }

    handleTestSuccess(status) {
        this.stopTest();
        
        const processingTime = status.processing_time || 0;
        const totalTime = Date.now() - this.testStartTime;
        
        const result = {
            status: 'success',
            message: `Queue worker is functioning properly! Job completed in ${processingTime.toFixed(2)}s`,
            details: {
                processing_time: processingTime,
                total_time: (totalTime / 1000).toFixed(2),
                completed_at: status.completed_at || new Date().toISOString(),
                job_id: this.currentTestJobId
            },
            timestamp: Date.now()
        };
        
        this.displayTestResult(result);
        
        // Show success notification
        this.showSuccessNotification(`Queue worker completed test in ${processingTime.toFixed(2)}s`);
    }

    handleTestFailure(status) {
        this.stopTest();
        
        const result = {
            status: 'failed',
            message: 'Queue test failed: ' + (status.error_message || 'Unknown error'),
            details: {
                error: status.error_message,
                failed_at: status.failed_at || new Date().toISOString(),
                job_id: this.currentTestJobId
            },
            timestamp: Date.now()
        };
        
        this.displayTestResult(result);
    }

    handleTestTimeout() {
        this.stopTest();
        
        const result = {
            status: 'timeout',
            message: 'Queue test timed out after 30 seconds. The queue worker may not be running.',
            details: {
                timeout_duration: 30,
                job_id: this.currentTestJobId,
                timed_out_at: new Date().toISOString()
            },
            timestamp: Date.now()
        };
        
        this.displayTestResult(result);
    }

    handleTestError(message) {
        this.stopTest();
        
        const result = {
            status: 'error',
            message: 'Test error: ' + message,
            details: {
                error: message,
                error_at: new Date().toISOString()
            },
            timestamp: Date.now()
        };
        
        this.displayTestResult(result);
        
        // Show detailed error notification
        this.showDetailedError(new Error(message), 'Queue test execution');
    }

    stopTest() {
        // Clear intervals
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        
        if (this.elapsedTimeInterval) {
            clearInterval(this.elapsedTimeInterval);
            this.elapsedTimeInterval = null;
        }
        
        // Reset state
        this.currentTestJobId = null;
        this.testStartTime = null;
        
        // Update UI
        this.setTestInProgress(false);
        this.hideCurrentTestProgress();
    }

    setTestInProgress(inProgress) {
        if (this.testQueueBtn) {
            this.setLoadingStateWithAnimation(inProgress);
            
            if (inProgress) {
                this.showCurrentTestProgress();
            }
        }
    }

    showCurrentTestProgress() {
        if (this.currentTestProgress) {
            this.currentTestProgress.classList.remove('hidden');
        }
        if (this.testResultsSection) {
            this.testResultsSection.classList.remove('hidden');
        }
    }

    hideCurrentTestProgress() {
        if (this.currentTestProgress) {
            this.currentTestProgress.classList.add('hidden');
        }
    }

    updateProgressMessage(message) {
        if (this.testProgressMessage) {
            this.updateProgressWithAnimation(message);
        }
    }

    startElapsedTimeCounter() {
        if (this.elapsedTimeInterval) {
            clearInterval(this.elapsedTimeInterval);
        }
        
        this.elapsedTimeInterval = setInterval(() => {
            if (this.testStartTime && this.testElapsedTime) {
                const elapsed = ((Date.now() - this.testStartTime) / 1000).toFixed(1);
                this.testElapsedTime.textContent = `(${elapsed}s)`;
            }
        }, 100);
    }

    displayTestResult(result) {
        if (!this.testResultsDisplay) return;
        
        const resultElement = this.createTestResultElement(result);
        
        // Add initial animation classes
        resultElement.style.opacity = '0';
        resultElement.style.transform = 'translateY(-10px)';
        resultElement.style.transition = 'all 0.3s ease-in-out';
        
        this.testResultsDisplay.insertBefore(resultElement, this.testResultsDisplay.firstChild);
        
        // Trigger animation
        setTimeout(() => {
            resultElement.style.opacity = '1';
            resultElement.style.transform = 'translateY(0)';
        }, 10);
        
        // Show results section with animation
        if (this.testResultsSection) {
            this.testResultsSection.classList.remove('hidden');
        }
        
        // Add success/failure specific animations
        this.addResultAnimation(resultElement, result.status);
        
        // Limit to 5 recent results
        const results = this.testResultsDisplay.children;
        while (results.length > 5) {
            const lastResult = results[results.length - 1];
            this.animateResultRemoval(lastResult);
        }
    }

    createTestResultElement(result) {
        const div = document.createElement('div');
        
        let bgColor, textColor, icon, pulseClass = '';
        switch (result.status) {
            case 'success':
                bgColor = 'bg-green-50 border-green-200';
                textColor = 'text-green-900';
                pulseClass = 'animate-pulse-success';
                icon = `<svg class="h-5 w-5 text-green-600 animate-bounce-once" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;
                break;
            case 'failed':
            case 'error':
                bgColor = 'bg-red-50 border-red-200';
                textColor = 'text-red-900';
                pulseClass = 'animate-pulse-error';
                icon = `<svg class="h-5 w-5 text-red-600 animate-shake" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;
                break;
            case 'timeout':
                bgColor = 'bg-yellow-50 border-yellow-200';
                textColor = 'text-yellow-900';
                pulseClass = 'animate-pulse-warning';
                icon = `<svg class="h-5 w-5 text-yellow-600 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;
                break;
        }
        
        const timestamp = new Date(result.timestamp).toLocaleString();
        
        div.className = `border rounded-lg p-4 ${bgColor} ${pulseClass} transition-all duration-300 hover:shadow-md`;
        div.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${icon}
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium ${textColor}">
                        ${result.message}
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                        ${timestamp}
                        ${result.details?.processing_time ? ` • Processing: ${result.details.processing_time}s` : ''}
                        ${result.details?.total_time ? ` • Total: ${result.details.total_time}s` : ''}
                    </div>
                    ${this.createResultDetailsSection(result)}
                </div>
            </div>
        `;
        
        return div;
    }













    // Animation and Visual Enhancement Methods
    addResultAnimation(element, status) {
        if (!element || !element.classList) return;
        
        // Add status-specific animation classes
        switch (status) {
            case 'success':
                element.classList.add('animate-success-glow');
                setTimeout(() => element.classList.remove('animate-success-glow'), 2000);
                break;
            case 'failed':
            case 'error':
                element.classList.add('animate-error-shake');
                setTimeout(() => element.classList.remove('animate-error-shake'), 1000);
                break;
            case 'timeout':
                element.classList.add('animate-warning-pulse');
                setTimeout(() => element.classList.remove('animate-warning-pulse'), 3000);
                break;
        }
    }

    animateResultRemoval(element) {
        if (!element) return;
        
        element.style.transition = 'all 0.3s ease-out';
        element.style.opacity = '0';
        element.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, 300);
    }

    createResultDetailsSection(result) {
        if (!result.details) return '';
        
        const details = [];
        
        if (result.details.job_id) {
            details.push(`Job ID: ${result.details.job_id}`);
        }
        
        if (result.details.error) {
            details.push(`Error: ${result.details.error}`);
        }
        
        if (result.details.timeout_duration) {
            details.push(`Timeout: ${result.details.timeout_duration}s`);
        }
        
        if (details.length === 0) return '';
        
        return `
            <div class="mt-2 text-xs text-gray-500 border-t border-gray-200 pt-2">
                ${details.join(' • ')}
            </div>
        `;
    }

    // Enhanced Progress Tracking
    updateProgressWithAnimation(message) {
        if (!this.testProgressMessage) return;
        
        // Fade out current message
        this.testProgressMessage.style.opacity = '0.5';
        
        setTimeout(() => {
            this.testProgressMessage.textContent = message;
            this.testProgressMessage.style.opacity = '1';
        }, 150);
    }

    // Enhanced Loading States
    setLoadingStateWithAnimation(isLoading) {
        if (!this.testQueueBtn || !this.testQueueBtnText) return;
        
        if (isLoading) {
            this.testQueueBtn.disabled = true;
            this.testQueueBtn.classList.add('opacity-75', 'cursor-not-allowed');
            this.testQueueBtnText.textContent = 'Testing...';
            
            // Add loading spinner animation
            const spinner = this.testQueueBtn.querySelector('svg');
            if (spinner) {
                spinner.classList.add('animate-spin');
            }
        } else {
            this.testQueueBtn.disabled = false;
            this.testQueueBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            this.testQueueBtnText.textContent = 'Test Queue Worker';
            
            // Remove loading spinner animation
            const spinner = this.testQueueBtn.querySelector('svg');
            if (spinner) {
                spinner.classList.remove('animate-spin');
            }
        }
    }

    // Enhanced Error Display
    showDetailedError(error, context = '') {
        const errorContainer = document.createElement('div');
        errorContainer.className = 'fixed top-4 right-4 max-w-md bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg z-50 animate-slide-in-right';
        
        errorContainer.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium">
                        Queue Test Error
                    </div>
                    <div class="text-sm mt-1">
                        ${error.message || 'An unexpected error occurred'}
                        ${context ? `<br><small>Context: ${context}</small>` : ''}
                    </div>
                </div>
                <div class="ml-3">
                    <button class="text-red-600 hover:text-red-800" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(errorContainer);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorContainer.parentNode) {
                errorContainer.style.opacity = '0';
                errorContainer.style.transform = 'translateX(100%)';
                setTimeout(() => errorContainer.remove(), 300);
            }
        }, 5000);
    }

    // Enhanced Success Display
    showSuccessNotification(message) {
        const successContainer = document.createElement('div');
        successContainer.className = 'fixed top-4 right-4 max-w-md bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50 animate-slide-in-right';
        
        successContainer.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-600 animate-bounce-once" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium">
                        Queue Test Successful
                    </div>
                    <div class="text-sm mt-1">
                        ${message}
                    </div>
                </div>
                <div class="ml-3">
                    <button class="text-green-600 hover:text-green-800" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(successContainer);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (successContainer.parentNode) {
                successContainer.style.opacity = '0';
                successContainer.style.transform = 'translateX(100%)';
                setTimeout(() => successContainer.remove(), 300);
            }
        }, 3000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize on admin dashboard page
    if (document.getElementById('test-queue-btn')) {
        new AdminQueueTesting();
    }
});

// Export for testing (Node.js environment)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminQueueTesting;
}