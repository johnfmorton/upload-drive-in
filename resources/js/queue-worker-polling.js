/**
 * Queue Worker Polling Service with Exponential Backoff
 * 
 * Provides optimized AJAX polling for queue worker status with:
 * - Exponential backoff for failed requests
 * - Adaptive polling intervals based on status
 * - Resource leak prevention with proper timeout handling
 * - Performance monitoring and metrics
 */

class QueueWorkerPollingService {
    constructor(options = {}) {
        // Polling configuration
        this.baseInterval = options.baseInterval || 1000; // 1 second base interval
        this.maxInterval = options.maxInterval || 30000;  // 30 seconds max interval
        this.backoffMultiplier = options.backoffMultiplier || 1.5;
        this.maxRetries = options.maxRetries || 5;
        
        // Adaptive intervals based on status
        this.intervalsByStatus = {
            'testing': 1000,      // 1 second for active tests
            'completed': 30000,   // 30 seconds for completed tests
            'failed': 5000,       // 5 seconds for failed tests
            'timeout': 10000,     // 10 seconds for timeouts
            'not_tested': 60000   // 1 minute for not tested
        };
        
        // Request timeout configuration
        this.requestTimeout = options.requestTimeout || 15000; // 15 seconds
        this.abortController = null;
        
        // State tracking
        this.currentInterval = this.baseInterval;
        this.retryCount = 0;
        this.isPolling = false;
        this.pollTimeoutId = null;
        this.lastPollTime = 0;
        this.consecutiveErrors = 0;
        
        // Performance metrics
        this.metrics = {
            totalRequests: 0,
            successfulRequests: 0,
            failedRequests: 0,
            averageResponseTime: 0,
            responseTimes: [],
            lastError: null,
            startTime: Date.now()
        };
        
        // Event callbacks
        this.onStatusUpdate = options.onStatusUpdate || (() => {});
        this.onError = options.onError || (() => {});
        this.onMetricsUpdate = options.onMetricsUpdate || (() => {});
        
        // Bind methods
        this.poll = this.poll.bind(this);
        this.handleResponse = this.handleResponse.bind(this);
        this.handleError = this.handleError.bind(this);
    }
    
    /**
     * Start polling for queue worker status
     * 
     * @param {string} jobId - The test job ID to poll for
     * @param {Object} options - Additional polling options
     */
    startPolling(jobId, options = {}) {
        if (this.isPolling) {
            console.log('Polling already in progress, stopping previous poll');
            this.stopPolling();
        }
        
        this.jobId = jobId;
        this.isPolling = true;
        this.retryCount = 0;
        this.consecutiveErrors = 0;
        this.currentInterval = this.baseInterval;
        
        // Override intervals if provided
        if (options.intervals) {
            this.intervalsByStatus = { ...this.intervalsByStatus, ...options.intervals };
        }
        
        console.log('Starting queue worker polling', {
            jobId: this.jobId,
            baseInterval: this.baseInterval,
            maxInterval: this.maxInterval
        });
        
        // Start immediate poll
        this.poll();
    }
    
    /**
     * Stop polling and clean up resources
     */
    stopPolling() {
        if (!this.isPolling) {
            return;
        }
        
        console.log('Stopping queue worker polling', {
            jobId: this.jobId,
            totalRequests: this.metrics.totalRequests,
            duration: Date.now() - this.metrics.startTime
        });
        
        this.isPolling = false;
        
        // Clear timeout
        if (this.pollTimeoutId) {
            clearTimeout(this.pollTimeoutId);
            this.pollTimeoutId = null;
        }
        
        // Abort any ongoing request
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
        
        // Reset state
        this.jobId = null;
        this.retryCount = 0;
        this.consecutiveErrors = 0;
        this.currentInterval = this.baseInterval;
    }
    
    /**
     * Perform a single poll request
     */
    async poll() {
        if (!this.isPolling || !this.jobId) {
            return;
        }
        
        const startTime = performance.now();
        this.lastPollTime = Date.now();
        this.metrics.totalRequests++;
        
        try {
            // Create new abort controller for this request
            this.abortController = new AbortController();
            
            // Set up request timeout
            const timeoutId = setTimeout(() => {
                if (this.abortController) {
                    this.abortController.abort();
                }
            }, this.requestTimeout);
            
            const response = await fetch(`/setup/queue-worker/status/${this.jobId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                signal: this.abortController.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            const responseTime = performance.now() - startTime;
            
            this.handleResponse(data, responseTime);
            
        } catch (error) {
            const responseTime = performance.now() - startTime;
            this.handleError(error, responseTime);
        } finally {
            this.abortController = null;
        }
    }
    
    /**
     * Handle successful poll response
     * 
     * @param {Object} data - Response data
     * @param {number} responseTime - Response time in milliseconds
     */
    handleResponse(data, responseTime) {
        this.metrics.successfulRequests++;
        this.updateResponseTimeMetrics(responseTime);
        this.consecutiveErrors = 0;
        this.retryCount = 0;
        
        console.log('Poll response received', {
            jobId: this.jobId,
            status: data.status,
            responseTime: Math.round(responseTime),
            consecutiveErrors: this.consecutiveErrors
        });
        
        // Call status update callback
        this.onStatusUpdate(data);
        
        // Determine if we should continue polling
        const shouldContinue = this.shouldContinuePolling(data);
        
        if (shouldContinue && this.isPolling) {
            // Calculate next poll interval based on status
            const nextInterval = this.calculateNextInterval(data.status);
            this.scheduleNextPoll(nextInterval);
        } else {
            console.log('Stopping polling based on status', {
                status: data.status,
                shouldContinue,
                isPolling: this.isPolling
            });
            this.stopPolling();
        }
        
        // Update metrics callback
        this.onMetricsUpdate(this.getMetrics());
    }
    
    /**
     * Handle poll error with exponential backoff
     * 
     * @param {Error} error - The error that occurred
     * @param {number} responseTime - Response time in milliseconds
     */
    handleError(error, responseTime) {
        this.metrics.failedRequests++;
        this.metrics.lastError = {
            message: error.message,
            timestamp: new Date().toISOString(),
            responseTime: Math.round(responseTime)
        };
        
        this.consecutiveErrors++;
        this.retryCount++;
        
        console.error('Poll request failed', {
            jobId: this.jobId,
            error: error.message,
            retryCount: this.retryCount,
            consecutiveErrors: this.consecutiveErrors,
            responseTime: Math.round(responseTime)
        });
        
        // Call error callback
        this.onError(error, this.retryCount);
        
        // Check if we should retry
        if (this.retryCount < this.maxRetries && this.isPolling) {
            // Calculate backoff interval
            const backoffInterval = this.calculateBackoffInterval();
            
            console.log('Scheduling retry with backoff', {
                retryCount: this.retryCount,
                backoffInterval,
                maxRetries: this.maxRetries
            });
            
            this.scheduleNextPoll(backoffInterval);
        } else {
            console.error('Max retries reached or polling stopped', {
                retryCount: this.retryCount,
                maxRetries: this.maxRetries,
                isPolling: this.isPolling
            });
            
            this.stopPolling();
        }
        
        // Update metrics callback
        this.onMetricsUpdate(this.getMetrics());
    }
    
    /**
     * Calculate the next polling interval based on status
     * 
     * @param {string} status - Current queue worker status
     * @returns {number} Next polling interval in milliseconds
     */
    calculateNextInterval(status) {
        // Use status-specific interval if available
        if (this.intervalsByStatus[status]) {
            return this.intervalsByStatus[status];
        }
        
        // Default to base interval
        return this.baseInterval;
    }
    
    /**
     * Calculate exponential backoff interval for retries
     * 
     * @returns {number} Backoff interval in milliseconds
     */
    calculateBackoffInterval() {
        const backoffInterval = Math.min(
            this.baseInterval * Math.pow(this.backoffMultiplier, this.retryCount - 1),
            this.maxInterval
        );
        
        // Add jitter to prevent thundering herd
        const jitter = Math.random() * 0.1 * backoffInterval;
        
        return Math.round(backoffInterval + jitter);
    }
    
    /**
     * Schedule the next poll
     * 
     * @param {number} interval - Interval in milliseconds
     */
    scheduleNextPoll(interval) {
        if (!this.isPolling) {
            return;
        }
        
        this.currentInterval = interval;
        
        this.pollTimeoutId = setTimeout(() => {
            if (this.isPolling) {
                this.poll();
            }
        }, interval);
        
        console.log('Next poll scheduled', {
            interval,
            jobId: this.jobId,
            retryCount: this.retryCount
        });
    }
    
    /**
     * Determine if polling should continue based on status
     * 
     * @param {Object} data - Response data
     * @returns {boolean} Whether to continue polling
     */
    shouldContinuePolling(data) {
        const terminalStatuses = ['completed', 'failed', 'timeout', 'error'];
        return !terminalStatuses.includes(data.status);
    }
    
    /**
     * Update response time metrics
     * 
     * @param {number} responseTime - Response time in milliseconds
     */
    updateResponseTimeMetrics(responseTime) {
        this.metrics.responseTimes.push(responseTime);
        
        // Keep only last 100 response times for average calculation
        if (this.metrics.responseTimes.length > 100) {
            this.metrics.responseTimes.shift();
        }
        
        // Calculate average response time
        this.metrics.averageResponseTime = this.metrics.responseTimes.reduce(
            (sum, time) => sum + time, 0
        ) / this.metrics.responseTimes.length;
    }
    
    /**
     * Get current polling metrics
     * 
     * @returns {Object} Current metrics
     */
    getMetrics() {
        const now = Date.now();
        const duration = now - this.metrics.startTime;
        
        return {
            ...this.metrics,
            duration,
            requestsPerSecond: this.metrics.totalRequests / (duration / 1000),
            successRate: this.metrics.totalRequests > 0 
                ? (this.metrics.successfulRequests / this.metrics.totalRequests) * 100 
                : 0,
            currentInterval: this.currentInterval,
            isPolling: this.isPolling,
            consecutiveErrors: this.consecutiveErrors,
            retryCount: this.retryCount
        };
    }
    
    /**
     * Get CSRF token from meta tag
     * 
     * @returns {string} CSRF token
     */
    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            console.warn('CSRF token not found');
        }
        return token || '';
    }
    
    /**
     * Reset metrics
     */
    resetMetrics() {
        this.metrics = {
            totalRequests: 0,
            successfulRequests: 0,
            failedRequests: 0,
            averageResponseTime: 0,
            responseTimes: [],
            lastError: null,
            startTime: Date.now()
        };
    }
    
    /**
     * Get current polling status
     * 
     * @returns {Object} Polling status information
     */
    getStatus() {
        return {
            isPolling: this.isPolling,
            jobId: this.jobId,
            currentInterval: this.currentInterval,
            retryCount: this.retryCount,
            consecutiveErrors: this.consecutiveErrors,
            lastPollTime: this.lastPollTime,
            metrics: this.getMetrics()
        };
    }
}

export default QueueWorkerPollingService;