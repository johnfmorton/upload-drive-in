/**
 * Setup Wizard JavaScript Functionality
 * 
 * Handles dynamic form behavior, real-time validation, and AJAX functionality
 * for the Upload Drive-in setup wizard.
 */

class SetupWizard {
    constructor() {
        this.currentStep = null;
        this.progressBar = null;
        this.init();
    }

    /**
     * Initialize the setup wizard functionality
     */
    init() {
        this.currentStep = this.getCurrentStep();
        this.progressBar = document.querySelector('[data-progress-bar]');
        
        // Initialize step-specific functionality
        this.initializeStepFunctionality();
        
        // Initialize form submission handling
        this.initializeFormSubmission();
        
        // Initialize progress indicator updates
        this.initializeProgressIndicator();
        
        console.log('Setup Wizard initialized for step:', this.currentStep);
    }

    /**
     * Get the current setup step from the page
     */
    getCurrentStep() {
        const stepElement = document.querySelector('[data-setup-step]');
        return stepElement ? stepElement.dataset.setupStep : 'welcome';
    }

    /**
     * Initialize functionality specific to each step
     */
    initializeStepFunctionality() {
        switch (this.currentStep) {
            case 'database':
                this.initializeDatabaseStep();
                break;
            case 'admin':
                this.initializeAdminStep();
                break;
            case 'storage':
                this.initializeStorageStep();
                break;
            default:
                // No specific functionality needed for welcome/complete steps
                break;
        }
    }

    /**
     * Initialize database configuration step functionality
     */
    initializeDatabaseStep() {
        const sqliteRadio = document.getElementById('sqlite');
        const mysqlRadio = document.getElementById('mysql');
        const sqliteConfig = document.getElementById('sqlite-config');
        const mysqlConfig = document.getElementById('mysql-config');
        const testConnectionBtn = document.getElementById('test-connection');

        if (!sqliteRadio || !mysqlRadio) return;

        // Database type selection handler
        const toggleDatabaseConfig = () => {
            if (sqliteRadio.checked) {
                sqliteConfig?.classList.remove('hidden');
                mysqlConfig?.classList.add('hidden');
                this.updateFormValidation('sqlite');
            } else {
                sqliteConfig?.classList.add('hidden');
                mysqlConfig?.classList.remove('hidden');
                this.updateFormValidation('mysql');
            }
        };

        sqliteRadio.addEventListener('change', toggleDatabaseConfig);
        mysqlRadio.addEventListener('change', toggleDatabaseConfig);

        // Initialize on page load
        toggleDatabaseConfig();

        // MySQL connection testing
        if (testConnectionBtn) {
            testConnectionBtn.addEventListener('click', () => {
                this.testDatabaseConnection();
            });
        }

        // Real-time validation for MySQL fields
        this.initializeDatabaseValidation();
    }

    /**
     * Initialize admin user creation step functionality
     */
    initializeAdminStep() {
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('password_confirmation');
        const emailInput = document.getElementById('email');
        const togglePasswordBtn = document.getElementById('toggle-password');

        if (!passwordInput || !passwordConfirmInput || !emailInput) return;

        // Password visibility toggle
        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', () => {
                this.togglePasswordVisibility(passwordInput, togglePasswordBtn);
            });
        }

        // Password strength checking
        passwordInput.addEventListener('input', () => {
            this.checkPasswordStrength(passwordInput.value);
            this.validatePasswordMatch();
        });

        // Password confirmation checking
        passwordConfirmInput.addEventListener('input', () => {
            this.validatePasswordMatch();
        });

        // Real-time email validation
        emailInput.addEventListener('blur', () => {
            this.validateEmailAvailability(emailInput.value);
        });

        // Form validation
        this.initializeAdminFormValidation();
    }

    /**
     * Initialize cloud storage configuration step functionality
     */
    initializeStorageStep() {
        const toggleSecretBtn = document.getElementById('toggle-secret');
        const secretInput = document.getElementById('google_client_secret');
        const testConnectionBtn = document.getElementById('test-google-connection');
        const skipCheckbox = document.getElementById('skip_storage');
        const googleConfig = document.getElementById('google-drive-config');

        // Client secret visibility toggle
        if (toggleSecretBtn && secretInput) {
            toggleSecretBtn.addEventListener('click', () => {
                this.togglePasswordVisibility(secretInput, toggleSecretBtn);
            });
        }

        // Skip storage checkbox handler
        if (skipCheckbox && googleConfig) {
            skipCheckbox.addEventListener('change', () => {
                this.toggleStorageRequirements(skipCheckbox.checked, googleConfig);
            });
        }

        // Google Drive connection testing
        if (testConnectionBtn) {
            testConnectionBtn.addEventListener('click', () => {
                this.testGoogleDriveConnection();
            });
        }

        // Real-time credential validation
        this.initializeStorageValidation();
    }

    /**
     * Initialize form submission handling with loading states
     */
    initializeFormSubmission() {
        const forms = document.querySelectorAll('form[id$="-form"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                this.handleFormSubmission(form, e);
            });
        });
    }

    /**
     * Initialize progress indicator updates
     */
    initializeProgressIndicator() {
        // Update progress bar animation
        if (this.progressBar) {
            const targetWidth = this.progressBar.style.width;
            this.animateProgressBar(targetWidth);
        }

        // Update step indicators
        this.updateStepIndicators();
    }

    /**
     * Handle database connection testing
     */
    async testDatabaseConnection() {
        const button = document.getElementById('test-connection');
        const statusDiv = document.getElementById('connection-status');
        
        if (!button || !statusDiv) return;

        const originalText = button.innerHTML;
        
        try {
            // Show loading state
            this.setButtonLoading(button, 'Testing...');
            
            // Collect form data
            const formData = new FormData();
            formData.append('_token', this.getCsrfToken());
            formData.append('host', document.getElementById('mysql_host')?.value || '');
            formData.append('port', document.getElementById('mysql_port')?.value || '');
            formData.append('database', document.getElementById('mysql_database')?.value || '');
            formData.append('username', document.getElementById('mysql_username')?.value || '');
            formData.append('password', document.getElementById('mysql_password')?.value || '');

            // Make AJAX request
            const response = await fetch('/setup/ajax/test-database', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            
            // Show result
            statusDiv.classList.remove('hidden');
            statusDiv.innerHTML = this.formatConnectionResult(data.success, data.message);

        } catch (error) {
            console.error('Database connection test failed:', error);
            statusDiv.classList.remove('hidden');
            statusDiv.innerHTML = this.formatConnectionResult(false, 'Connection test failed. Please try again.');
        } finally {
            // Restore button state
            this.restoreButtonState(button, originalText);
        }
    }

    /**
     * Handle Google Drive connection testing
     */
    async testGoogleDriveConnection() {
        const button = document.getElementById('test-google-connection');
        const statusDiv = document.getElementById('google-connection-status');
        
        if (!button || !statusDiv) return;

        const originalText = button.innerHTML;
        
        try {
            // Show loading state
            this.setButtonLoading(button, 'Testing...');
            
            // Collect form data
            const formData = new FormData();
            formData.append('_token', this.getCsrfToken());
            formData.append('client_id', document.getElementById('google_client_id')?.value || '');
            formData.append('client_secret', document.getElementById('google_client_secret')?.value || '');

            // Make AJAX request
            const response = await fetch('/setup/ajax/test-storage', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            
            // Show result
            statusDiv.classList.remove('hidden');
            statusDiv.innerHTML = this.formatConnectionResult(data.success, data.message);

        } catch (error) {
            console.error('Google Drive connection test failed:', error);
            statusDiv.classList.remove('hidden');
            statusDiv.innerHTML = this.formatConnectionResult(false, 'Connection test failed. Please try again.');
        } finally {
            // Restore button state
            this.restoreButtonState(button, originalText);
        }
    }

    /**
     * Validate email availability
     */
    async validateEmailAvailability(email) {
        if (!email || !this.isValidEmail(email)) return;

        try {
            const formData = new FormData();
            formData.append('_token', this.getCsrfToken());
            formData.append('email', email);

            const response = await fetch('/setup/ajax/validate-email', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            this.showEmailValidationResult(data.available, data.message);

        } catch (error) {
            console.error('Email validation failed:', error);
        }
    }

    /**
     * Check password strength and update indicators
     */
    checkPasswordStrength(password) {
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        
        if (!strengthBar || !strengthText) return;

        const score = this.calculatePasswordScore(password);
        
        // Update progress bar
        strengthBar.style.width = score + '%';
        
        // Update text and colors
        if (score === 0) {
            strengthText.textContent = 'Enter password';
            strengthText.className = 'font-medium text-gray-400';
            strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-gray-300';
        } else if (score < 50) {
            strengthText.textContent = 'Weak';
            strengthText.className = 'font-medium text-red-600';
            strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-red-500';
        } else if (score < 75) {
            strengthText.textContent = 'Fair';
            strengthText.className = 'font-medium text-yellow-600';
            strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-yellow-500';
        } else if (score < 100) {
            strengthText.textContent = 'Good';
            strengthText.className = 'font-medium text-blue-600';
            strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-blue-500';
        } else {
            strengthText.textContent = 'Strong';
            strengthText.className = 'font-medium text-green-600';
            strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-green-500';
        }

        // Update requirement indicators
        this.updatePasswordRequirements(password);
    }

    /**
     * Calculate password strength score
     */
    calculatePasswordScore(password) {
        let score = 0;
        
        if (password.length >= 8) score += 25;
        if (/[A-Z]/.test(password)) score += 25;
        if (/[a-z]/.test(password)) score += 25;
        if (/[0-9]/.test(password)) score += 25;
        
        return score;
    }

    /**
     * Update password requirement indicators
     */
    updatePasswordRequirements(password) {
        const requirements = [
            { id: 'req-length', test: password.length >= 8 },
            { id: 'req-uppercase', test: /[A-Z]/.test(password) },
            { id: 'req-lowercase', test: /[a-z]/.test(password) },
            { id: 'req-number', test: /[0-9]/.test(password) }
        ];

        requirements.forEach(req => {
            const element = document.getElementById(req.id);
            if (!element) return;

            if (req.test) {
                element.classList.remove('text-gray-600');
                element.classList.add('text-green-600');
                element.querySelector('svg')?.classList.remove('text-gray-400');
                element.querySelector('svg')?.classList.add('text-green-500');
            } else {
                element.classList.remove('text-green-600');
                element.classList.add('text-gray-600');
                element.querySelector('svg')?.classList.remove('text-green-500');
                element.querySelector('svg')?.classList.add('text-gray-400');
            }
        });
    }

    /**
     * Validate password confirmation match
     */
    validatePasswordMatch() {
        const password = document.getElementById('password')?.value || '';
        const confirmation = document.getElementById('password_confirmation')?.value || '';
        const indicator = document.getElementById('password-match-indicator');
        const successIcon = document.getElementById('match-success');
        const errorIcon = document.getElementById('match-error');
        const matchText = document.getElementById('password-match-text');

        if (!indicator || !successIcon || !errorIcon || !matchText) return;

        if (confirmation.length === 0) {
            indicator.classList.add('hidden');
            matchText.textContent = 'Re-enter your password to confirm';
            matchText.className = 'mt-2 text-sm text-gray-500';
            return;
        }

        indicator.classList.remove('hidden');

        if (password === confirmation) {
            successIcon.classList.remove('hidden');
            errorIcon.classList.add('hidden');
            matchText.textContent = 'Passwords match';
            matchText.className = 'mt-2 text-sm text-green-600';
        } else {
            successIcon.classList.add('hidden');
            errorIcon.classList.remove('hidden');
            matchText.textContent = 'Passwords do not match';
            matchText.className = 'mt-2 text-sm text-red-600';
        }
    }

    /**
     * Toggle password visibility
     */
    togglePasswordVisibility(input, button) {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        
        const eyeClosed = button.querySelector('[id$="eye-closed"], [id$="-eye-closed"]');
        const eyeOpen = button.querySelector('[id$="eye-open"], [id$="-eye-open"]');
        
        if (type === 'text') {
            eyeClosed?.classList.add('hidden');
            eyeOpen?.classList.remove('hidden');
        } else {
            eyeClosed?.classList.remove('hidden');
            eyeOpen?.classList.add('hidden');
        }
    }

    /**
     * Toggle storage requirements based on skip checkbox
     */
    toggleStorageRequirements(skip, configElement) {
        if (skip) {
            configElement.style.opacity = '0.5';
            configElement.style.pointerEvents = 'none';
            document.getElementById('google_client_id').required = false;
            document.getElementById('google_client_secret').required = false;
        } else {
            configElement.style.opacity = '1';
            configElement.style.pointerEvents = 'auto';
            document.getElementById('google_client_id').required = true;
            document.getElementById('google_client_secret').required = true;
        }
    }

    /**
     * Handle form submission with loading states
     */
    handleFormSubmission(form, event) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (!submitButton) return;

        // Show loading state
        const originalText = submitButton.innerHTML;
        this.setButtonLoading(submitButton, 'Processing...');

        // Disable form elements
        const formElements = form.querySelectorAll('input, select, textarea, button');
        formElements.forEach(element => {
            element.disabled = true;
        });

        // Re-enable form if there's an error (handled by page reload on success)
        setTimeout(() => {
            formElements.forEach(element => {
                element.disabled = false;
            });
            this.restoreButtonState(submitButton, originalText);
        }, 10000); // 10 second timeout
    }

    /**
     * Initialize database field validation
     */
    initializeDatabaseValidation() {
        const fields = ['mysql_host', 'mysql_port', 'mysql_database', 'mysql_username'];
        
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('blur', () => {
                    this.validateDatabaseField(fieldId, field.value);
                });
            }
        });
    }

    /**
     * Initialize admin form validation
     */
    initializeAdminFormValidation() {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const submitBtn = document.getElementById('submit-btn');

        if (!emailInput || !passwordInput || !confirmInput || !submitBtn) return;

        const validateForm = () => {
            const email = emailInput.value;
            const password = passwordInput.value;
            const confirmation = confirmInput.value;
            const score = this.calculatePasswordScore(password);

            const isValid = this.isValidEmail(email) && 
                           score === 100 && 
                           password === confirmation && 
                           confirmation.length > 0;

            submitBtn.disabled = !isValid;
        };

        emailInput.addEventListener('input', validateForm);
        passwordInput.addEventListener('input', validateForm);
        confirmInput.addEventListener('input', validateForm);

        // Initial validation
        validateForm();
    }

    /**
     * Initialize storage validation
     */
    initializeStorageValidation() {
        const clientIdField = document.getElementById('google_client_id');
        const clientSecretField = document.getElementById('google_client_secret');

        if (clientIdField) {
            clientIdField.addEventListener('blur', () => {
                this.validateGoogleClientId(clientIdField.value);
            });
        }

        if (clientSecretField) {
            clientSecretField.addEventListener('blur', () => {
                this.validateGoogleClientSecret(clientSecretField.value);
            });
        }
    }

    /**
     * Validate database field
     */
    validateDatabaseField(fieldId, value) {
        // Basic validation - can be extended
        const field = document.getElementById(fieldId);
        if (!field) return;

        let isValid = true;
        let message = '';

        switch (fieldId) {
            case 'mysql_host':
                isValid = value.length > 0;
                message = isValid ? '' : 'Host is required';
                break;
            case 'mysql_port':
                isValid = /^\d+$/.test(value) && parseInt(value) > 0 && parseInt(value) <= 65535;
                message = isValid ? '' : 'Port must be a valid number between 1 and 65535';
                break;
            case 'mysql_database':
                isValid = /^[a-zA-Z0-9_]+$/.test(value);
                message = isValid ? '' : 'Database name can only contain letters, numbers, and underscores';
                break;
            case 'mysql_username':
                isValid = value.length > 0;
                message = isValid ? '' : 'Username is required';
                break;
        }

        this.showFieldValidation(field, isValid, message);
    }

    /**
     * Validate Google Client ID format
     */
    validateGoogleClientId(clientId) {
        const field = document.getElementById('google_client_id');
        if (!field) return;

        const isValid = /^[0-9]+-[a-zA-Z0-9]+\.apps\.googleusercontent\.com$/.test(clientId);
        const message = isValid ? '' : 'Client ID should end with .apps.googleusercontent.com';
        
        this.showFieldValidation(field, isValid, message);
    }

    /**
     * Validate Google Client Secret format
     */
    validateGoogleClientSecret(clientSecret) {
        const field = document.getElementById('google_client_secret');
        if (!field) return;

        const isValid = /^GOCSPX-[a-zA-Z0-9_-]+$/.test(clientSecret);
        const message = isValid ? '' : 'Client Secret should start with GOCSPX-';
        
        this.showFieldValidation(field, isValid, message);
    }

    /**
     * Show field validation result
     */
    showFieldValidation(field, isValid, message) {
        // Remove existing validation classes
        field.classList.remove('border-red-300', 'border-green-300');
        
        // Remove existing validation message
        const existingMessage = field.parentNode.querySelector('.validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        if (message) {
            // Add validation classes
            field.classList.add(isValid ? 'border-green-300' : 'border-red-300');
            
            // Add validation message
            const messageElement = document.createElement('p');
            messageElement.className = `mt-1 text-sm validation-message ${isValid ? 'text-green-600' : 'text-red-600'}`;
            messageElement.textContent = message;
            field.parentNode.appendChild(messageElement);
        }
    }

    /**
     * Show email validation result
     */
    showEmailValidationResult(available, message) {
        const emailField = document.getElementById('email');
        if (!emailField) return;

        this.showFieldValidation(emailField, available, message);
    }

    /**
     * Update form validation based on database type
     */
    updateFormValidation(databaseType) {
        const mysqlFields = ['mysql_host', 'mysql_port', 'mysql_database', 'mysql_username'];
        
        mysqlFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.required = databaseType === 'mysql';
            }
        });
    }

    /**
     * Animate progress bar
     */
    animateProgressBar(targetWidth) {
        if (!this.progressBar) return;

        // Smooth animation to target width
        this.progressBar.style.transition = 'width 0.5s ease-out';
        setTimeout(() => {
            this.progressBar.style.width = targetWidth;
        }, 100);
    }

    /**
     * Update step indicators
     */
    updateStepIndicators() {
        const stepElements = document.querySelectorAll('[data-step-indicator]');
        
        stepElements.forEach(element => {
            const step = element.dataset.stepIndicator;
            const isCompleted = this.isStepCompleted(step);
            const isCurrent = step === this.currentStep;
            
            if (isCompleted) {
                element.classList.add('completed');
            }
            if (isCurrent) {
                element.classList.add('current');
            }
        });
    }

    /**
     * Check if a step is completed
     */
    isStepCompleted(step) {
        // This would typically check against server state
        // For now, we'll use a simple check based on current step
        const stepOrder = ['welcome', 'database', 'admin', 'storage', 'complete'];
        const currentIndex = stepOrder.indexOf(this.currentStep);
        const stepIndex = stepOrder.indexOf(step);
        
        return stepIndex < currentIndex;
    }

    /**
     * Set button loading state
     */
    setButtonLoading(button, text) {
        button.disabled = true;
        button.innerHTML = `
            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            ${text}
        `;
    }

    /**
     * Restore button state
     */
    restoreButtonState(button, originalText) {
        button.disabled = false;
        button.innerHTML = originalText;
    }

    /**
     * Format connection test result
     */
    formatConnectionResult(success, message) {
        const iconClass = success ? 'text-green-600' : 'text-red-600';
        const icon = success ? 
            `<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>` :
            `<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>`;

        return `
            <div class="${iconClass}">
                <div class="flex items-center">
                    ${icon}
                    ${success ? 'Connection successful!' : 'Connection failed'}
                </div>
                ${message ? `<p class="text-sm mt-1">${message}</p>` : ''}
            </div>
        `;
    }

    /**
     * Get CSRF token
     */
    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    /**
     * Validate email format
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
}

// Initialize setup wizard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new SetupWizard();
});