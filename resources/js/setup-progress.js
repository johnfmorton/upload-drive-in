/**
 * Setup Progress Tracking and Visual Feedback
 * 
 * This module handles enhanced progress tracking, step transitions,
 * and visual feedback for the setup wizard.
 */

class SetupProgressTracker {
    constructor() {
        this.currentStep = null;
        this.progress = 0;
        this.stepStartTime = null;
        this.setupStartTime = null;
        this.completedSteps = [];
        
        this.init();
    }
    
    init() {
        // Get initial setup state from DOM
        this.loadSetupState();
        
        // Set up event listeners
        this.setupEventListeners();
        
        // Start progress tracking
        this.startProgressTracking();
    }
    
    loadSetupState() {
        const setupElement = document.querySelector('[data-setup-step]');
        if (setupElement) {
            this.currentStep = setupElement.dataset.setupStep;
        }
        
        const progressElement = document.querySelector('[data-setup-progress]');
        if (progressElement) {
            this.progress = parseInt(progressElement.dataset.setupProgress) || 0;
        }
        
        // Load from localStorage if available
        const savedState = localStorage.getItem('setup_progress_state');
        if (savedState) {
            try {
                const state = JSON.parse(savedState);
                this.setupStartTime = state.setupStartTime;
                this.completedSteps = state.completedSteps || [];
            } catch (e) {
                console.warn('Failed to load setup state from localStorage:', e);
            }
        }
        
        // Set setup start time if not already set
        if (!this.setupStartTime) {
            this.setupStartTime = Date.now();
            this.saveState();
        }
        
        // Set step start time
        this.stepStartTime = Date.now();
    }
    
    setupEventListeners() {
        // Listen for step completion events
        document.addEventListener('setup:step-completed', (event) => {
            this.handleStepCompletion(event.detail);
        });
        
        // Listen for step transition events
        document.addEventListener('setup:step-transition', (event) => {
            this.handleStepTransition(event.detail);
        });
        
        // Listen for form submissions to track step progress
        document.addEventListener('submit', (event) => {
            if (event.target.closest('[data-setup-step]')) {
                this.handleFormSubmission(event);
            }
        });
        
        // Listen for page visibility changes to pause/resume tracking
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseTracking();
            } else {
                this.resumeTracking();
            }
        });
    }
    
    startProgressTracking() {
        // Update progress indicators
        this.updateProgressIndicators();
        
        // Start step timer
        this.startStepTimer();
        
        // Animate progress bars
        this.animateProgressBars();
    }
    
    handleStepCompletion(stepData) {
        const { step, nextStep, progress, details } = stepData;
        
        // Mark step as completed
        if (!this.completedSteps.includes(step)) {
            this.completedSteps.push(step);
        }
        
        // Update progress
        this.progress = progress || this.progress;
        
        // Show step completion feedback
        this.showStepCompletionFeedback(step, details);
        
        // Save state
        this.saveState();
        
        // Trigger step transition if next step is provided
        if (nextStep) {
            setTimeout(() => {
                this.triggerStepTransition(step, nextStep);
            }, 2000);
        }
    }
    
    handleStepTransition(transitionData) {
        const { fromStep, toStep, message } = transitionData;
        
        // Show transition animation
        this.showStepTransition(fromStep, toStep, message);
        
        // Update current step
        this.currentStep = toStep;
        this.stepStartTime = Date.now();
        
        // Save state
        this.saveState();
    }
    
    handleFormSubmission(event) {
        const form = event.target;
        const stepElement = form.closest('[data-setup-step]');
        
        if (stepElement) {
            // Show loading state
            this.showFormLoadingState(form);
            
            // Track form submission time
            this.trackFormSubmission(stepElement.dataset.setupStep);
        }
    }
    
    showStepCompletionFeedback(step, details = {}) {
        // Create and show completion notification
        const notification = this.createCompletionNotification(step, details);
        document.body.appendChild(notification);
        
        // Animate in
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });
        
        // Auto-remove after delay
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
    
    showStepTransition(fromStep, toStep, message) {
        // Create transition overlay
        const overlay = this.createTransitionOverlay(fromStep, toStep, message);
        document.body.appendChild(overlay);
        
        // Animate in
        requestAnimationFrame(() => {
            overlay.classList.add('show');
        });
        
        // Auto-remove after animation
        setTimeout(() => {
            overlay.classList.remove('show');
            setTimeout(() => {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 300);
        }, 2000);
    }
    
    createCompletionNotification(step, details) {
        const notification = document.createElement('div');
        notification.className = 'setup-completion-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="notification-text">
                    <div class="notification-title">${this.getStepTitle(step)} Complete!</div>
                    <div class="notification-message">${details.message || 'Step completed successfully.'}</div>
                </div>
            </div>
            <div class="notification-progress">
                <div class="progress-bar" style="width: ${this.progress}%"></div>
            </div>
        `;
        
        return notification;
    }
    
    createTransitionOverlay(fromStep, toStep, message) {
        const overlay = document.createElement('div');
        overlay.className = 'setup-transition-overlay';
        overlay.innerHTML = `
            <div class="transition-content">
                <div class="transition-icon">
                    <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <div class="transition-text">
                    <div class="transition-title">Moving to Next Step</div>
                    <div class="transition-message">${message || 'Proceeding to the next setup step...'}</div>
                </div>
                <div class="transition-steps">
                    <span class="step-badge completed">${this.getStepTitle(fromStep)}</span>
                    <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="step-badge current">${this.getStepTitle(toStep)}</span>
                </div>
            </div>
        `;
        
        return overlay;
    }
    
    updateProgressIndicators() {
        // Update all progress bars
        const progressBars = document.querySelectorAll('.setup-progress-bar');
        progressBars.forEach(bar => {
            bar.style.width = `${this.progress}%`;
        });
        
        // Update progress text
        const progressTexts = document.querySelectorAll('.setup-progress-text');
        progressTexts.forEach(text => {
            text.textContent = `${this.progress}%`;
        });
        
        // Update step indicators
        this.updateStepIndicators();
    }
    
    updateStepIndicators() {
        const stepIndicators = document.querySelectorAll('.setup-step-indicator');
        stepIndicators.forEach(indicator => {
            const step = indicator.dataset.step;
            
            if (this.completedSteps.includes(step)) {
                indicator.classList.add('completed');
                indicator.classList.remove('current', 'upcoming');
            } else if (step === this.currentStep) {
                indicator.classList.add('current');
                indicator.classList.remove('completed', 'upcoming');
            } else {
                indicator.classList.add('upcoming');
                indicator.classList.remove('completed', 'current');
            }
        });
    }
    
    animateProgressBars() {
        const progressBars = document.querySelectorAll('.setup-progress-bar');
        progressBars.forEach(bar => {
            bar.style.transition = 'width 1s ease-out';
            bar.style.width = `${this.progress}%`;
        });
    }
    
    startStepTimer() {
        // Update step timer display if present
        const timerElement = document.querySelector('.setup-step-timer');
        if (timerElement) {
            this.updateStepTimer(timerElement);
            
            // Update every second
            this.stepTimerInterval = setInterval(() => {
                this.updateStepTimer(timerElement);
            }, 1000);
        }
    }
    
    updateStepTimer(element) {
        if (!this.stepStartTime) return;
        
        const elapsed = Date.now() - this.stepStartTime;
        const seconds = Math.floor(elapsed / 1000);
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        
        const timeString = minutes > 0 
            ? `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
            : `${seconds}s`;
            
        element.textContent = timeString;
    }
    
    showFormLoadingState(form) {
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('loading');
            
            // Add loading spinner if not present
            if (!submitButton.querySelector('.loading-spinner')) {
                const spinner = document.createElement('span');
                spinner.className = 'loading-spinner';
                spinner.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                `;
                submitButton.insertBefore(spinner, submitButton.firstChild);
            }
        }
    }
    
    trackFormSubmission(step) {
        // Track analytics or send to server
        console.log(`Setup step form submitted: ${step}`);
        
        // You could send this to an analytics service or server endpoint
        // fetch('/api/setup/track', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify({ step, timestamp: Date.now() })
        // });
    }
    
    getStepTitle(step) {
        const titles = {
            'assets': 'Assets',
            'welcome': 'Welcome',
            'database': 'Database',
            'admin': 'Admin User',
            'storage': 'Cloud Storage',
            'complete': 'Complete'
        };
        
        return titles[step] || step.charAt(0).toUpperCase() + step.slice(1);
    }
    
    saveState() {
        const state = {
            currentStep: this.currentStep,
            progress: this.progress,
            setupStartTime: this.setupStartTime,
            completedSteps: this.completedSteps,
            lastUpdated: Date.now()
        };
        
        try {
            localStorage.setItem('setup_progress_state', JSON.stringify(state));
        } catch (e) {
            console.warn('Failed to save setup state to localStorage:', e);
        }
    }
    
    pauseTracking() {
        if (this.stepTimerInterval) {
            clearInterval(this.stepTimerInterval);
        }
    }
    
    resumeTracking() {
        this.startStepTimer();
    }
    
    triggerStepTransition(fromStep, toStep) {
        const event = new CustomEvent('setup:step-transition', {
            detail: { fromStep, toStep, message: `Moving from ${this.getStepTitle(fromStep)} to ${this.getStepTitle(toStep)}` }
        });
        document.dispatchEvent(event);
    }
    
    // Public API methods
    completeStep(step, details = {}) {
        const event = new CustomEvent('setup:step-completed', {
            detail: { step, details, progress: this.progress }
        });
        document.dispatchEvent(event);
    }
    
    updateProgress(newProgress) {
        this.progress = newProgress;
        this.updateProgressIndicators();
        this.saveState();
    }
    
    getElapsedTime() {
        if (!this.setupStartTime) return 0;
        return Date.now() - this.setupStartTime;
    }
    
    getStepElapsedTime() {
        if (!this.stepStartTime) return 0;
        return Date.now() - this.stepStartTime;
    }
}

// CSS for notifications and transitions
const setupProgressStyles = `
<style>
.setup-completion-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border: 1px solid #d1fae5;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    padding: 16px;
    max-width: 400px;
    z-index: 1000;
    transform: translateX(100%);
    transition: transform 0.3s ease-out;
}

.setup-completion-notification.show {
    transform: translateX(0);
}

.notification-content {
    display: flex;
    align-items: flex-start;
    margin-bottom: 8px;
}

.notification-icon {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    background: #dcfce7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
}

.notification-text {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #065f46;
    margin-bottom: 4px;
}

.notification-message {
    font-size: 14px;
    color: #047857;
}

.notification-progress {
    height: 4px;
    background: #d1fae5;
    border-radius: 2px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: #10b981;
    transition: width 0.5s ease-out;
}

.setup-transition-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1001;
    opacity: 0;
    transition: opacity 0.3s ease-out;
}

.setup-transition-overlay.show {
    opacity: 1;
}

.transition-content {
    background: white;
    border-radius: 12px;
    padding: 32px;
    text-align: center;
    max-width: 400px;
    margin: 16px;
}

.transition-icon {
    margin-bottom: 16px;
}

.transition-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.transition-message {
    color: #6b7280;
    margin-bottom: 24px;
}

.transition-steps {
    display: flex;
    align-items: center;
    justify-content: center;
}

.step-badge {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 500;
}

.step-badge.completed {
    background: #dcfce7;
    color: #065f46;
}

.step-badge.current {
    background: #dbeafe;
    color: #1e40af;
}

.loading-spinner {
    display: inline-flex;
    align-items: center;
}
</style>
`;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Add styles to head
    document.head.insertAdjacentHTML('beforeend', setupProgressStyles);
    
    // Initialize progress tracker
    window.setupProgressTracker = new SetupProgressTracker();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SetupProgressTracker;
}