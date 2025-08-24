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
        this.testHistory = this.loadTestHistory();
        
        this.initializeElements();
        this.bindEvents();
        this.loadQueueHealth();
        this.displayTestHistory();
    }

    initializeElements() {
        // Main buttons
        this.testQueueBtn = document.getElementById('test-queue-btn');
        this.testQueueBtnText = document.getElementById('test-queue-btn-text');
        this.refreshQueueHealthBtn = document.getElementById('refresh-queue-health-btn');
        
        // Queue health overview
        this.queueStatus = document.getElementById('queue-status');
        this.recentJobsCount = document.getElementById('recent-jobs-count');
        this.recentJobsDescription = document.getElementById('recent-jobs-description');
        this.failedJobsCount = document.getElementById('failed-jobs-count');
        
        // Test results sections
        this.testResultsSection = document.getElementById('test-results-section');
        this.currentTestProgress = document.getElementById('current-test-progress');
        this.testProgressMessage = document.getElementById('test-progress-message');
        this.testElapsedTime = document.getElementById('test-elapsed-time');
        this.testResultsDisplay = document.getElementById('test-results-display');
        
        // Historical results
        this.historicalResultsSection = document.getElementById('historical-results-section');
        this.historicalResultsList = document.getElementById('historical-results-list');
        this.clearTestHistoryBtn = document.getElementById('clear-test-history-btn');
        
        // Failed jobs details
        this.failedJobsDetailsSection = document.getElementById('failed-jobs-details-section');
        this.failedJobsList = document.getElementById('failed-jobs-list');
    }

    bindEvents() {
        if (this.testQueueBtn) {
            this.testQueueBtn.addEventListener('click', () => this.startQueueTest());
        }
        
        if (this.refreshQueueHealthBtn) {
            this.refreshQueueHealthBtn.addEventListener('click', () => this.loadQueueHealth());
        }
        
        if (this.clearTestHistoryBtn) {
            this.clearTestHistoryBtn.addEventListener('click', () => this.clearTestHistory());
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
        this.addToTestHistory(result);
        
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
        this.addToTestHistory(result);
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
        this.addToTestHistory(result);
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
        this.addToTestHistory(result);
        
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

    async loadQueueHealth() {
        try {
            const response = await fetch('/admin/queue/health', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            if (data.success && data.data && data.data.metrics) {
                this.updateQueueHealthDisplay(data.data.metrics);
            }
            
        } catch (error) {
            console.error('Failed to load queue health:', error);
            this.updateQueueHealthDisplay({
                overall_status: 'error',
                job_statistics: {
                    pending_jobs: 0,
                    failed_jobs_total: 0
                }
            });
        }
    }

    updateQueueHealthDisplay(metrics) {
        if (this.queueStatus) {
            let statusText = 'Unknown';
            let statusClass = 'text-gray-900';
            
            // Use overall_status from the API response
            const status = metrics.overall_status || metrics.status;
            
            switch (status) {
                case 'healthy':
                    statusText = 'Healthy';
                    statusClass = 'text-green-600';
                    break;
                case 'warning':
                    statusText = 'Warning';
                    statusClass = 'text-yellow-600';
                    break;
                case 'critical':
                case 'error':
                    statusText = 'Error';
                    statusClass = 'text-red-600';
                    break;
                case 'idle':
                    statusText = 'Idle';
                    statusClass = 'text-blue-600';
                    break;
            }
            
            this.queueStatus.textContent = statusText;
            this.queueStatus.className = `text-2xl font-bold ${statusClass}`;
        }
        
        if (this.recentJobsCount) {
            // Show recent test jobs (more meaningful than pending jobs for admin dashboard)
            const recentTestJobs = metrics.test_job_statistics?.test_jobs_1h || 0;
            const pendingJobs = metrics.job_statistics?.pending_jobs || 0;
            
            // Show recent test jobs if any, otherwise show pending jobs
            if (recentTestJobs > 0) {
                this.recentJobsCount.textContent = recentTestJobs;
                if (this.recentJobsDescription) {
                    this.recentJobsDescription.textContent = 'Test jobs (1h)';
                }
            } else if (pendingJobs > 0) {
                this.recentJobsCount.textContent = pendingJobs;
                if (this.recentJobsDescription) {
                    this.recentJobsDescription.textContent = 'Pending jobs';
                }
            } else {
                this.recentJobsCount.textContent = '0';
                if (this.recentJobsDescription) {
                    this.recentJobsDescription.textContent = 'No recent activity';
                }
            }
        }
        
        if (this.failedJobsCount) {
            // Get failed jobs count from job_statistics
            const failedJobs = metrics.job_statistics?.failed_jobs_total || 0;
            this.failedJobsCount.textContent = failedJobs;
            
            // Show/hide failed jobs details section
            if (failedJobs > 0 && metrics.recent_failed_jobs && metrics.recent_failed_jobs.length > 0) {
                this.displayFailedJobsDetails(metrics.recent_failed_jobs);
            } else {
                this.hideFailedJobsDetails();
            }
        }
    }

    // Test History Management
    loadTestHistory() {
        try {
            const stored = localStorage.getItem('admin_queue_test_history');
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Failed to load test history:', error);
            return [];
        }
    }

    addToTestHistory(result) {
        this.testHistory.unshift(result);
        
        // Keep only last 10 results
        if (this.testHistory.length > 10) {
            this.testHistory = this.testHistory.slice(0, 10);
        }
        
        this.saveTestHistory();
        this.displayTestHistory();
    }

    saveTestHistory() {
        try {
            localStorage.setItem('admin_queue_test_history', JSON.stringify(this.testHistory));
        } catch (error) {
            console.error('Failed to save test history:', error);
        }
    }

    displayTestHistory() {
        if (!this.historicalResultsList || this.testHistory.length === 0) {
            if (this.historicalResultsSection) {
                this.historicalResultsSection.classList.add('hidden');
            }
            return;
        }
        
        // Show historical results section
        if (this.historicalResultsSection) {
            this.historicalResultsSection.classList.remove('hidden');
        }
        
        // Clear existing history
        this.historicalResultsList.innerHTML = '';
        
        // Add historical results (limit to 5 for display)
        this.testHistory.slice(0, 5).forEach(result => {
            const element = this.createHistoricalResultElement(result);
            this.historicalResultsList.appendChild(element);
        });
    }

    createHistoricalResultElement(result) {
        const div = document.createElement('div');
        
        let statusColor;
        switch (result.status) {
            case 'success':
                statusColor = 'text-green-600';
                break;
            case 'failed':
            case 'error':
                statusColor = 'text-red-600';
                break;
            case 'timeout':
                statusColor = 'text-yellow-600';
                break;
            default:
                statusColor = 'text-gray-600';
        }
        
        const timestamp = new Date(result.timestamp).toLocaleString();
        
        div.className = 'flex items-center justify-between py-2 px-3 bg-gray-50 rounded-md';
        div.innerHTML = `
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-900 truncate">
                    ${result.message}
                </div>
                <div class="text-xs text-gray-500">
                    ${timestamp}
                </div>
            </div>
            <div class="flex-shrink-0 ml-3">
                <span class="text-sm font-medium ${statusColor} capitalize">
                    ${result.status}
                </span>
            </div>
        `;
        
        return div;
    }

    clearTestHistory() {
        if (confirm('Are you sure you want to clear the test history?')) {
            this.testHistory = [];
            this.saveTestHistory();
            this.displayTestHistory();
        }
    }

    // Failed Jobs Details Management
    displayFailedJobsDetails(failedJobs) {
        if (!this.failedJobsDetailsSection || !this.failedJobsList) return;
        
        // Show the section
        this.failedJobsDetailsSection.classList.remove('hidden');
        
        // Clear existing content
        this.failedJobsList.innerHTML = '';
        
        // Add each failed job
        failedJobs.forEach(job => {
            const jobElement = this.createFailedJobElement(job);
            this.failedJobsList.appendChild(jobElement);
        });
    }

    hideFailedJobsDetails() {
        if (this.failedJobsDetailsSection) {
            this.failedJobsDetailsSection.classList.add('hidden');
        }
    }

    createFailedJobElement(job) {
        const div = document.createElement('div');
        div.className = 'bg-white border border-red-200 rounded-md p-3';
        
        const jobClass = job.job_class.replace('App\\Jobs\\', ''); // Shorten class name
        const failedAt = new Date(job.failed_at).toLocaleString();
        
        div.innerHTML = `
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900">
                        ${jobClass}
                    </div>
                    <div class="text-sm text-red-600 mt-1">
                        ${job.error_message}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Failed: ${failedAt} • Queue: ${job.queue} • ID: ${job.id}
                    </div>
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