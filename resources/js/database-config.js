/**
 * Enhanced Database Configuration JavaScript
 * 
 * Provides real-time validation, connection testing, and improved UX
 * for the database setup form.
 */

class DatabaseConfigManager {
    constructor() {
        this.initializeEventListeners();
        this.initializeFormState();
    }

    initializeEventListeners() {
        // Database type selection
        document.querySelectorAll('.database-type-radio').forEach(radio => {
            radio.addEventListener('change', this.handleDatabaseTypeChange.bind(this));
        });

        // MySQL field validation
        document.querySelectorAll('.mysql-field').forEach(field => {
            field.addEventListener('input', this.handleMySQLFieldChange.bind(this));
            field.addEventListener('blur', this.validateMySQLField.bind(this));
        });

        // Connection test buttons
        const testMySQLButton = document.getElementById('test-mysql-connection');
        if (testMySQLButton) {
            testMySQLButton.addEventListener('click', this.testMySQLConnection.bind(this));
        }

        const testSQLiteButton = document.getElementById('test-sqlite-connection');
        if (testSQLiteButton) {
            testSQLiteButton.addEventListener('click', this.testSQLiteConnection.bind(this));
        }

        // Password visibility toggle
        const togglePassword = document.getElementById('toggle-password');
        if (togglePassword) {
            togglePassword.addEventListener('click', this.togglePasswordVisibility.bind(this));
        }

        // SQLite custom path toggle
        const sqliteCustomPath = document.getElementById('sqlite-custom-path');
        if (sqliteCustomPath) {
            sqliteCustomPath.addEventListener('change', this.toggleSQLiteCustomPath.bind(this));
        }

        // Hosting help toggle
        const showHostingHelp = document.getElementById('show-hosting-help');
        if (showHostingHelp) {
            showHostingHelp.addEventListener('click', this.showHostingInstructions.bind(this));
        }

        // Form submission validation
        const form = document.getElementById('database-form');
        if (form) {
            form.addEventListener('submit', this.handleFormSubmission.bind(this));
        }
    }

    initializeFormState() {
        // Check initial database type and show appropriate config
        const selectedType = document.querySelector('.database-type-radio:checked');
        if (selectedType) {
            this.showDatabaseConfig(selectedType.value);
        }

        // Enable/disable MySQL test button based on field completion
        this.updateMySQLTestButtonState();
    }

    handleDatabaseTypeChange(event) {
        const selectedType = event.target.value;
        this.showDatabaseConfig(selectedType);
        
        // Clear any previous connection test results
        this.clearConnectionStatus();
    }

    showDatabaseConfig(type) {
        const sqliteConfig = document.getElementById('sqlite-config');
        const mysqlConfig = document.getElementById('mysql-config');

        if (type === 'sqlite') {
            sqliteConfig?.classList.remove('hidden');
            mysqlConfig?.classList.add('hidden');
        } else if (type === 'mysql') {
            sqliteConfig?.classList.add('hidden');
            mysqlConfig?.classList.remove('hidden');
            this.updateMySQLTestButtonState();
        }
    }

    handleMySQLFieldChange(event) {
        // Real-time validation feedback
        this.validateMySQLField(event);
        
        // Update test button state
        this.updateMySQLTestButtonState();
        
        // Clear previous connection status if fields change
        this.clearConnectionStatus();
    }

    validateMySQLField(event) {
        const field = event.target;
        const value = field.value.trim();
        
        // Remove existing validation classes
        field.classList.remove('border-red-300', 'border-green-300');
        
        // Remove existing validation messages
        const existingMessage = field.parentNode.querySelector('.field-validation-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        let isValid = true;
        let message = '';

        switch (field.id) {
            case 'mysql_host':
                if (!value) {
                    isValid = false;
                    message = 'Host is required';
                } else if (value.length > 255) {
                    isValid = false;
                    message = 'Host must not exceed 255 characters';
                }
                break;

            case 'mysql_port':
                const port = parseInt(value);
                if (!value) {
                    isValid = false;
                    message = 'Port is required';
                } else if (isNaN(port) || port < 1 || port > 65535) {
                    isValid = false;
                    message = 'Port must be between 1 and 65535';
                }
                break;

            case 'mysql_database':
                if (!value) {
                    isValid = false;
                    message = 'Database name is required';
                } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                    isValid = false;
                    message = 'Only letters, numbers, and underscores allowed';
                } else if (value.length > 64) {
                    isValid = false;
                    message = 'Database name must not exceed 64 characters';
                }
                break;

            case 'mysql_username':
                if (!value) {
                    isValid = false;
                    message = 'Username is required';
                } else if (value.length > 32) {
                    isValid = false;
                    message = 'Username must not exceed 32 characters';
                }
                break;

            case 'mysql_password':
                if (value.length > 255) {
                    isValid = false;
                    message = 'Password must not exceed 255 characters';
                }
                break;
        }

        // Apply validation styling
        if (event.type === 'blur' && value) {
            if (isValid) {
                field.classList.add('border-green-300');
            } else {
                field.classList.add('border-red-300');
                this.showFieldValidationMessage(field, message);
            }
        }
    }

    showFieldValidationMessage(field, message) {
        const messageElement = document.createElement('p');
        messageElement.className = 'mt-1 text-xs text-red-600 field-validation-message';
        messageElement.textContent = message;
        field.parentNode.appendChild(messageElement);
    }

    updateMySQLTestButtonState() {
        const testButton = document.getElementById('test-mysql-connection');
        if (!testButton) return;

        const requiredFields = ['mysql_host', 'mysql_port', 'mysql_database', 'mysql_username'];
        const allFieldsFilled = requiredFields.every(fieldId => {
            const field = document.getElementById(fieldId);
            return field && field.value.trim() !== '';
        });

        testButton.disabled = !allFieldsFilled;
        
        if (allFieldsFilled) {
            testButton.classList.remove('opacity-50', 'cursor-not-allowed');
            testButton.classList.add('hover:bg-gray-50');
        } else {
            testButton.classList.add('opacity-50', 'cursor-not-allowed');
            testButton.classList.remove('hover:bg-gray-50');
        }
    }

    async testMySQLConnection() {
        const testButton = document.getElementById('test-mysql-connection');
        const statusDiv = document.getElementById('mysql-connection-status');
        const progressDiv = document.getElementById('connection-progress');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const loadingSpinner = document.getElementById('mysql-loading');
        const buttonText = document.getElementById('test-button-text');

        // Disable button and show loading state
        testButton.disabled = true;
        loadingSpinner?.classList.remove('hidden');
        buttonText.textContent = 'Testing...';
        
        // Show progress indicator
        progressDiv?.classList.remove('hidden');
        statusDiv?.classList.add('hidden');

        // Animate progress bar
        this.animateProgress(progressBar, progressText, [
            { progress: 20, text: 'Connecting to MySQL server...' },
            { progress: 50, text: 'Authenticating user credentials...' },
            { progress: 80, text: 'Testing database access...' },
            { progress: 100, text: 'Finalizing connection test...' }
        ]);

        try {
            const formData = {
                database_type: 'mysql',
                host: document.getElementById('mysql_host').value,
                port: document.getElementById('mysql_port').value,
                database: document.getElementById('mysql_database').value,
                username: document.getElementById('mysql_username').value,
                password: document.getElementById('mysql_password').value
            };

            const response = await fetch('/setup/ajax/test-database', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                                   document.querySelector('input[name="_token"]')?.value
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            // Hide progress, show results
            setTimeout(() => {
                progressDiv?.classList.add('hidden');
                statusDiv?.classList.remove('hidden');
                this.displayConnectionResult(result, response.ok);
            }, 1000);

        } catch (error) {
            console.error('Connection test failed:', error);
            
            setTimeout(() => {
                progressDiv?.classList.add('hidden');
                statusDiv?.classList.remove('hidden');
                this.displayConnectionResult({
                    success: false,
                    message: 'Connection test failed due to network error',
                    troubleshooting: ['Check your internet connection', 'Verify the server is accessible']
                }, false);
            }, 1000);
        } finally {
            // Reset button state
            testButton.disabled = false;
            loadingSpinner?.classList.add('hidden');
            buttonText.textContent = 'Test Connection';
            this.updateMySQLTestButtonState();
        }
    }

    async testSQLiteConnection() {
        const testButton = document.getElementById('test-sqlite-connection');
        const statusDiv = document.getElementById('sqlite-status-result');
        const loadingSpinner = document.getElementById('sqlite-loading');

        // Show loading state
        testButton.disabled = true;
        loadingSpinner?.classList.remove('hidden');
        statusDiv?.classList.remove('hidden');
        statusDiv.innerHTML = '<p class="text-xs text-gray-600">Checking SQLite configuration...</p>';

        try {
            const sqlitePath = document.getElementById('sqlite_path')?.value || '';
            
            const response = await fetch('/setup/ajax/test-database', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                                   document.querySelector('input[name="_token"]')?.value
                },
                body: JSON.stringify({
                    database_type: 'sqlite',
                    database: 'test_database',
                    sqlite_path: sqlitePath
                })
            });

            const result = await response.json();
            this.displaySQLiteResult(result, response.ok);

        } catch (error) {
            console.error('SQLite test failed:', error);
            this.displaySQLiteResult({
                success: false,
                message: 'SQLite test failed due to network error'
            }, false);
        } finally {
            testButton.disabled = false;
            loadingSpinner?.classList.add('hidden');
        }
    }

    animateProgress(progressBar, progressText, steps) {
        let currentStep = 0;
        
        const animate = () => {
            if (currentStep < steps.length) {
                const step = steps[currentStep];
                progressBar.style.width = `${step.progress}%`;
                progressText.textContent = step.text;
                currentStep++;
                setTimeout(animate, 500);
            }
        };
        
        animate();
    }

    displayConnectionResult(result, isSuccess) {
        const statusDiv = document.getElementById('mysql-connection-status');
        if (!statusDiv) return;

        let html = '';
        
        if (result.success) {
            html = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Connection Successful!</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>${result.message}</p>
                            ${result.details ? this.formatConnectionDetails(result.details) : ''}
                        </div>
                    </div>
                </div>
            `;
        } else {
            html = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Connection Failed</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>${result.message}</p>
                            ${result.troubleshooting ? this.formatTroubleshootingSteps(result.troubleshooting) : ''}
                            ${result.hosting_instructions ? this.formatHostingInstructions(result.hosting_instructions) : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        statusDiv.innerHTML = html;
    }

    displaySQLiteResult(result, isSuccess) {
        const statusDiv = document.getElementById('sqlite-status-result');
        if (!statusDiv) return;

        let html = '';
        
        if (result.success) {
            html = `
                <div class="flex items-center text-green-700">
                    <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-xs">${result.message}</span>
                </div>
            `;
        } else {
            html = `
                <div class="flex items-start text-red-700">
                    <svg class="w-4 h-4 mr-2 mt-0.5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-xs">${result.message}</span>
                </div>
            `;
        }

        statusDiv.innerHTML = html;
    }

    formatConnectionDetails(details) {
        if (!details || typeof details !== 'object') return '';
        
        let html = '<div class="mt-2 space-y-1">';
        for (const [key, value] of Object.entries(details)) {
            html += `<div class="text-xs">• ${value}</div>`;
        }
        html += '</div>';
        return html;
    }

    formatTroubleshootingSteps(steps) {
        if (!Array.isArray(steps) || steps.length === 0) return '';
        
        let html = '<div class="mt-3"><h4 class="text-xs font-medium text-red-800">Troubleshooting Steps:</h4><ul class="mt-1 text-xs space-y-1">';
        steps.slice(0, 5).forEach(step => {
            html += `<li>• ${step}</li>`;
        });
        html += '</ul></div>';
        return html;
    }

    formatHostingInstructions(instructions) {
        if (!instructions || typeof instructions !== 'object') return '';
        
        let html = '<div class="mt-3"><button type="button" class="text-xs text-red-600 hover:text-red-800 underline" onclick="this.nextElementSibling.classList.toggle(\'hidden\')">Show hosting provider instructions</button>';
        html += '<div class="hidden mt-2 space-y-2">';
        
        for (const [provider, info] of Object.entries(instructions)) {
            if (info.title && info.steps) {
                html += `<div class="text-xs"><strong>${info.title}:</strong><ul class="mt-1 ml-3 space-y-0.5">`;
                info.steps.slice(0, 3).forEach(step => {
                    html += `<li>• ${step}</li>`;
                });
                html += '</ul></div>';
            }
        }
        
        html += '</div></div>';
        return html;
    }

    togglePasswordVisibility() {
        const passwordField = document.getElementById('mysql_password');
        const showIcon = document.getElementById('password-show-icon');
        const hideIcon = document.getElementById('password-hide-icon');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            showIcon?.classList.add('hidden');
            hideIcon?.classList.remove('hidden');
        } else {
            passwordField.type = 'password';
            showIcon?.classList.remove('hidden');
            hideIcon?.classList.add('hidden');
        }
    }

    toggleSQLiteCustomPath() {
        const checkbox = document.getElementById('sqlite-custom-path');
        const pathInput = document.getElementById('sqlite-path-input');

        if (checkbox.checked) {
            pathInput?.classList.remove('hidden');
        } else {
            pathInput?.classList.add('hidden');
            const input = document.getElementById('sqlite_path');
            if (input) input.value = '';
        }
    }

    showHostingInstructions() {
        // This could open a modal or expand a section with detailed hosting instructions
        alert('Hosting provider instructions would be shown here. This could be implemented as a modal or expandable section.');
    }

    clearConnectionStatus() {
        const mysqlStatus = document.getElementById('mysql-connection-status');
        const sqliteStatus = document.getElementById('sqlite-status-result');
        const progressDiv = document.getElementById('connection-progress');

        mysqlStatus?.classList.add('hidden');
        sqliteStatus?.classList.add('hidden');
        progressDiv?.classList.add('hidden');
    }

    handleFormSubmission(event) {
        // Additional client-side validation before form submission
        const selectedType = document.querySelector('.database-type-radio:checked')?.value;
        
        if (selectedType === 'mysql') {
            const requiredFields = ['mysql_host', 'mysql_port', 'mysql_database', 'mysql_username'];
            const missingFields = requiredFields.filter(fieldId => {
                const field = document.getElementById(fieldId);
                return !field || !field.value.trim();
            });

            if (missingFields.length > 0) {
                event.preventDefault();
                alert('Please fill in all required MySQL fields before submitting.');
                return false;
            }
        }

        return true;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new DatabaseConfigManager();
});