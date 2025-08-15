/**
 * Admin User Creation Enhancement Script
 * 
 * Provides real-time validation, password strength checking, and email availability
 * checking for the admin user creation form during setup.
 * 
 * Version: 1.1 - Fixed duplicate validation messages
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('admin-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    const nameInput = document.getElementById('name');
    const submitBtn = document.getElementById('submit-btn');
    
    if (!form) return;

    // Password strength configuration
    const passwordRequirements = {
        length: { min: 8, element: document.getElementById('req-length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('req-number') },
        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') }
    };

    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const passwordMatchIndicator = document.getElementById('password-match-indicator');
    const matchSuccess = document.getElementById('match-success');
    const matchError = document.getElementById('match-error');
    const passwordMatchText = document.getElementById('password-match-text');

    // Email validation state
    let emailValidationRequest = null; // Track ongoing request
    let isEmailAvailable = false;
    let isPasswordValid = false;
    let isPasswordMatching = false;
    let isNameValid = false;

    /**
     * Initialize form enhancements
     */
    function initializeForm() {
        // Add real-time validation listeners
        if (emailInput) {
            emailInput.addEventListener('input', debounce(validateEmail, 500));
            emailInput.addEventListener('blur', validateEmail);
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', validatePassword);
            passwordInput.addEventListener('blur', validatePassword);
        }

        if (passwordConfirmInput) {
            passwordConfirmInput.addEventListener('input', validatePasswordMatch);
            passwordConfirmInput.addEventListener('blur', validatePasswordMatch);
        }

        if (nameInput) {
            nameInput.addEventListener('input', validateName);
            nameInput.addEventListener('blur', validateName);
        }

        // Password visibility toggle
        const togglePassword = document.getElementById('toggle-password');
        if (togglePassword) {
            togglePassword.addEventListener('click', togglePasswordVisibility);
        }

        // Form submission validation
        form.addEventListener('submit', handleFormSubmit);

        // Initial validation state
        updateSubmitButton();
    }

    /**
     * Validate email availability and format
     */
    async function validateEmail() {
        const email = emailInput.value.trim();
        
        // Cancel any ongoing request
        if (emailValidationRequest) {
            emailValidationRequest.abort();
            emailValidationRequest = null;
        }
        
        // Clear previous validation
        clearFieldValidation(emailInput);
        
        if (!email) {
            showFieldError(emailInput, 'Email address is required');
            isEmailAvailable = false;
            updateSubmitButton();
            return;
        }

        // Basic format validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldError(emailInput, 'Please enter a valid email address');
            isEmailAvailable = false;
            updateSubmitButton();
            return;
        }

        // Show loading state
        showFieldLoading(emailInput, 'Checking email availability...');

        try {
            // Create AbortController for this request
            const controller = new AbortController();
            emailValidationRequest = controller;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                console.error('CSRF token not found. Please refresh the page.');
                showFieldError(emailInput, 'Session expired. Please refresh the page.');
                isEmailAvailable = false;
                updateSubmitButton();
                return;
            }
            
            const response = await fetch('/setup/ajax/validate-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ email }),
                signal: controller.signal
            });

            const data = await response.json();

            // Clear the request reference
            emailValidationRequest = null;

            if (data.available) {
                showFieldSuccess(emailInput, 'Email address is available');
                isEmailAvailable = true;
            } else {
                showFieldError(emailInput, data.message || 'This email address is already in use');
                isEmailAvailable = false;
            }
        } catch (error) {
            // Clear the request reference
            emailValidationRequest = null;
            
            // Don't show error if request was aborted
            if (error.name === 'AbortError') {
                return;
            }
            
            console.error('Email validation error:', error);
            showFieldWarning(emailInput, 'Unable to verify email availability. Please try again.');
            isEmailAvailable = false;
        }

        updateSubmitButton();
    }

    /**
     * Validate password strength and requirements
     */
    function validatePassword() {
        const password = passwordInput.value;
        let score = 0;
        let metRequirements = 0;
        const totalRequirements = Object.keys(passwordRequirements).length;

        // Clear previous validation
        clearFieldValidation(passwordInput);

        if (!password) {
            resetPasswordStrength();
            isPasswordValid = false;
            updateSubmitButton();
            return;
        }

        // Check each requirement
        Object.entries(passwordRequirements).forEach(([key, requirement]) => {
            const element = requirement.element;
            if (!element) return;

            let meets = false;
            
            if (key === 'length') {
                meets = password.length >= requirement.min;
            } else if (requirement.regex) {
                meets = requirement.regex.test(password);
            }

            if (meets) {
                element.classList.remove('text-gray-400');
                element.classList.add('text-green-600');
                element.querySelector('svg').classList.remove('text-gray-400');
                element.querySelector('svg').classList.add('text-green-600');
                score += 20;
                metRequirements++;
            } else {
                element.classList.remove('text-green-600');
                element.classList.add('text-gray-400');
                element.querySelector('svg').classList.remove('text-green-600');
                element.querySelector('svg').classList.add('text-gray-400');
            }
        });

        // Update strength bar and text
        updatePasswordStrength(score, metRequirements, totalRequirements);

        // Validate password meets minimum requirements
        isPasswordValid = metRequirements >= 4; // At least 4 out of 5 requirements

        if (password.length < 8) {
            showFieldError(passwordInput, 'Password must be at least 8 characters long');
            isPasswordValid = false;
        } else if (metRequirements < 3) {
            showFieldWarning(passwordInput, 'Password should meet more security requirements');
            isPasswordValid = false;
        } else if (isPasswordValid) {
            showFieldSuccess(passwordInput, 'Password meets security requirements');
        }

        // Re-validate password match if confirmation is filled
        if (passwordConfirmInput.value) {
            validatePasswordMatch();
        }

        updateSubmitButton();
    }

    /**
     * Validate password confirmation match
     */
    function validatePasswordMatch() {
        const password = passwordInput.value;
        const confirmation = passwordConfirmInput.value;

        // Clear previous validation
        clearFieldValidation(passwordConfirmInput);
        
        if (!confirmation) {
            hidePasswordMatchIndicator();
            isPasswordMatching = false;
            updateSubmitButton();
            return;
        }

        if (password === confirmation) {
            showPasswordMatchSuccess();
            showFieldSuccess(passwordConfirmInput, 'Passwords match');
            isPasswordMatching = true;
        } else {
            showPasswordMatchError();
            showFieldError(passwordConfirmInput, 'Passwords do not match');
            isPasswordMatching = false;
        }

        updateSubmitButton();
    }

    /**
     * Validate name field
     */
    function validateName() {
        const name = nameInput.value.trim();
        
        // Clear previous validation
        clearFieldValidation(nameInput);

        if (!name) {
            showFieldError(nameInput, 'Administrator name is required');
            isNameValid = false;
        } else if (name.length < 2) {
            showFieldError(nameInput, 'Name must be at least 2 characters long');
            isNameValid = false;
        } else if (name.length > 255) {
            showFieldError(nameInput, 'Name must not exceed 255 characters');
            isNameValid = false;
        } else if (!/^[a-zA-Z\s\-\'\.]+$/.test(name)) {
            showFieldError(nameInput, 'Name can only contain letters, spaces, hyphens, apostrophes, and periods');
            isNameValid = false;
        } else {
            showFieldSuccess(nameInput, 'Valid administrator name');
            isNameValid = true;
        }

        updateSubmitButton();
    }

    /**
     * Update password strength indicator
     */
    function updatePasswordStrength(score, metRequirements, totalRequirements) {
        if (!strengthBar || !strengthText) return;

        let strengthLevel = 'Very Weak';
        let strengthColor = 'bg-red-500';
        let textColor = 'text-red-600';

        if (score >= 80) {
            strengthLevel = 'Very Strong';
            strengthColor = 'bg-green-500';
            textColor = 'text-green-600';
        } else if (score >= 60) {
            strengthLevel = 'Strong';
            strengthColor = 'bg-green-400';
            textColor = 'text-green-600';
        } else if (score >= 40) {
            strengthLevel = 'Medium';
            strengthColor = 'bg-yellow-500';
            textColor = 'text-yellow-600';
        } else if (score >= 20) {
            strengthLevel = 'Weak';
            strengthColor = 'bg-orange-500';
            textColor = 'text-orange-600';
        }

        strengthBar.style.width = `${Math.min(score, 100)}%`;
        strengthBar.className = `h-2 rounded-full transition-all duration-300 ${strengthColor}`;
        strengthText.textContent = strengthLevel;
        strengthText.className = `font-medium ${textColor}`;
    }

    /**
     * Reset password strength indicator
     */
    function resetPasswordStrength() {
        if (strengthBar) {
            strengthBar.style.width = '0%';
            strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-gray-300';
        }
        if (strengthText) {
            strengthText.textContent = 'Enter password';
            strengthText.className = 'font-medium text-gray-400';
        }

        // Reset all requirement indicators
        Object.values(passwordRequirements).forEach(requirement => {
            const element = requirement.element;
            if (element) {
                element.classList.remove('text-green-600');
                element.classList.add('text-gray-400');
                element.querySelector('svg')?.classList.remove('text-green-600');
                element.querySelector('svg')?.classList.add('text-gray-400');
            }
        });
    }

    /**
     * Show password match success indicator
     */
    function showPasswordMatchSuccess() {
        if (passwordMatchIndicator && matchSuccess && matchError) {
            passwordMatchIndicator.classList.remove('hidden');
            matchSuccess.classList.remove('hidden');
            matchError.classList.add('hidden');
        }
        if (passwordMatchText) {
            passwordMatchText.textContent = 'Passwords match';
            passwordMatchText.className = 'mt-2 text-sm text-green-600';
        }
    }

    /**
     * Show password match error indicator
     */
    function showPasswordMatchError() {
        if (passwordMatchIndicator && matchSuccess && matchError) {
            passwordMatchIndicator.classList.remove('hidden');
            matchSuccess.classList.add('hidden');
            matchError.classList.remove('hidden');
        }
        if (passwordMatchText) {
            passwordMatchText.textContent = 'Passwords do not match';
            passwordMatchText.className = 'mt-2 text-sm text-red-600';
        }
    }

    /**
     * Hide password match indicator
     */
    function hidePasswordMatchIndicator() {
        if (passwordMatchIndicator) {
            passwordMatchIndicator.classList.add('hidden');
        }
        if (passwordMatchText) {
            passwordMatchText.textContent = 'Re-enter your password to confirm';
            passwordMatchText.className = 'mt-2 text-sm text-gray-500';
        }
    }

    /**
     * Toggle password visibility
     */
    function togglePasswordVisibility() {
        const eyeClosed = document.getElementById('eye-closed');
        const eyeOpen = document.getElementById('eye-open');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeClosed.classList.add('hidden');
            eyeOpen.classList.remove('hidden');
        } else {
            passwordInput.type = 'password';
            eyeClosed.classList.remove('hidden');
            eyeOpen.classList.add('hidden');
        }
    }

    /**
     * Show field error state
     */
    function showFieldError(input, message) {
        input.classList.remove('border-gray-300', 'border-green-300', 'border-yellow-300');
        input.classList.add('border-red-300');
        
        const feedback = getOrCreateFeedback(input);
        feedback.textContent = message;
        feedback.className = 'mt-2 text-sm text-red-600';
    }

    /**
     * Show field success state
     */
    function showFieldSuccess(input, message) {
        input.classList.remove('border-gray-300', 'border-red-300', 'border-yellow-300');
        input.classList.add('border-green-300');
        
        const feedback = getOrCreateFeedback(input);
        feedback.textContent = message;
        feedback.className = 'mt-2 text-sm text-green-600';
    }

    /**
     * Show field warning state
     */
    function showFieldWarning(input, message) {
        input.classList.remove('border-gray-300', 'border-red-300', 'border-green-300');
        input.classList.add('border-yellow-300');
        
        const feedback = getOrCreateFeedback(input);
        feedback.textContent = message;
        feedback.className = 'mt-2 text-sm text-yellow-600';
    }

    /**
     * Show field loading state
     */
    function showFieldLoading(input, message) {
        input.classList.remove('border-red-300', 'border-green-300', 'border-yellow-300');
        input.classList.add('border-gray-300');
        
        const feedback = getOrCreateFeedback(input);
        feedback.innerHTML = `
            <div class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                ${message}
            </div>
        `;
        feedback.className = 'mt-2 text-sm text-gray-500';
    }

    /**
     * Clear field validation state
     */
    function clearFieldValidation(input) {
        input.classList.remove('border-red-300', 'border-green-300', 'border-yellow-300');
        input.classList.add('border-gray-300');
        
        // Remove all existing feedback elements
        removeFeedback(input);
    }

    /**
     * Remove all feedback elements for an input
     */
    function removeFeedback(input) {
        const existingFeedbacks = input.parentNode.querySelectorAll('.field-feedback');
        existingFeedbacks.forEach(feedback => feedback.remove());
    }

    /**
     * Get or create feedback element for input
     */
    function getOrCreateFeedback(input) {
        // First, remove any existing field-feedback elements to prevent duplication
        removeFeedback(input);
        
        // Create a new feedback element
        const feedback = document.createElement('p');
        feedback.className = 'field-feedback mt-2 text-sm text-gray-500';
        input.parentNode.appendChild(feedback);
        
        return feedback;
    }

    /**
     * Update submit button state
     */
    function updateSubmitButton() {
        if (!submitBtn) return;

        const isFormValid = isNameValid && isEmailAvailable && isPasswordValid && isPasswordMatching;
        
        if (isFormValid) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.add('hover:bg-blue-700');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.remove('hover:bg-blue-700');
        }
    }

    /**
     * Handle form submission
     */
    function handleFormSubmit(event) {
        // Check CSRF token before submission
        const csrfToken = document.querySelector('input[name="_token"]')?.value || 
                         document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (!csrfToken) {
            console.error('CSRF token not found');
            event.preventDefault();
            
            // Show user-friendly error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md';
            errorDiv.innerHTML = `
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Session Expired</h3>
                        <p class="mt-2 text-sm text-yellow-700">
                            Your session has expired for security reasons. Please refresh the page to continue.
                        </p>
                        <div class="mt-4">
                            <button type="button" onclick="window.location.reload()" 
                                    class="bg-yellow-100 px-3 py-2 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-200">
                                Refresh Page
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing error message
            const existingError = form.querySelector('.csrf-error');
            if (existingError) {
                existingError.remove();
            }
            
            errorDiv.classList.add('csrf-error');
            form.insertBefore(errorDiv, form.firstChild);
            
            // Scroll to top of form
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        
        console.log('Form submission - CSRF token present:', csrfToken ? 'Yes' : 'No');

        // Perform final validation
        validateName();
        validateEmail();
        validatePassword();
        validatePasswordMatch();

        const isFormValid = isNameValid && isEmailAvailable && isPasswordValid && isPasswordMatching;
        
        if (!isFormValid) {
            event.preventDefault();
            
            // Show general error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'mb-4 p-4 bg-red-50 border border-red-200 rounded-md';
            errorDiv.innerHTML = `
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            ${!isNameValid ? '<li>Administrator name is invalid</li>' : ''}
                            ${!isEmailAvailable ? '<li>Email address is invalid or unavailable</li>' : ''}
                            ${!isPasswordValid ? '<li>Password does not meet security requirements</li>' : ''}
                            ${!isPasswordMatching ? '<li>Password confirmation does not match</li>' : ''}
                        </ul>
                    </div>
                </div>
            `;
            
            // Remove existing error message
            const existingError = form.querySelector('.form-validation-error');
            if (existingError) {
                existingError.remove();
            }
            
            errorDiv.classList.add('form-validation-error');
            form.insertBefore(errorDiv, form.firstChild);
            
            // Scroll to top of form
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /**
     * Debounce function to limit API calls
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize the form enhancements
    initializeForm();
});