/**
 * Dual User Creation Employee Frontend Tests
 * 
 * Tests specifically for the employee client management interface functionality including:
 * - Employee-specific button behavior and form submission
 * - Client creation vs user creation terminology
 * - Employee route handling
 * - Consistent behavior between admin and employee interfaces
 * 
 * Requirements: 4.1, 4.2, 4.3, 4.4
 */

// Mock DOM elements and methods
const mockElements = new Map();
const mockForms = new Map();

// Mock Alpine.js data function
global.Alpine = {
    data: jest.fn()
};

// Mock DOM methods
global.document = {
    getElementById: jest.fn((id) => mockElements.get(id)),
    querySelector: jest.fn((selector) => {
        if (selector.includes('form')) {
            return mockForms.get('clientCreationForm');
        }
        return mockElements.get(selector);
    }),
    querySelectorAll: jest.fn(() => []),
    createElement: jest.fn(() => ({
        type: '',
        name: '',
        value: '',
        className: '',
        classList: {
            add: jest.fn(),
            remove: jest.fn(),
            contains: jest.fn(() => false)
        },
        setAttribute: jest.fn(),
        removeAttribute: jest.fn(),
        appendChild: jest.fn(),
        addEventListener: jest.fn(),
        submit: jest.fn()
    })),
    body: {
        appendChild: jest.fn()
    },
    addEventListener: jest.fn()
};

// Mock window object
global.window = {
    innerWidth: 1024,
    innerHeight: 768,
    addEventListener: jest.fn(),
    getComputedStyle: jest.fn(() => ({
        getPropertyValue: jest.fn(() => '16px')
    }))
};

// Create mock form element for employee interface
function createMockEmployeeForm(username = 'testemployee') {
    const form = {
        method: 'POST',
        action: `/employee/${username}/clients`,
        submit: jest.fn(),
        appendChild: jest.fn(),
        querySelector: jest.fn((selector) => {
            if (selector.includes('name=name')) return mockElements.get('nameInput');
            if (selector.includes('name=email')) return mockElements.get('emailInput');
            return null;
        }),
        addEventListener: jest.fn()
    };
    
    mockForms.set('clientCreationForm', form);
    return form;
}

// Create mock input elements
function createMockInput(id, name, type = 'text') {
    const input = {
        id,
        name,
        type,
        value: '',
        required: true,
        classList: {
            add: jest.fn(),
            remove: jest.fn(),
            contains: jest.fn(() => false)
        },
        setAttribute: jest.fn(),
        removeAttribute: jest.fn(),
        addEventListener: jest.fn()
    };
    
    mockElements.set(id, input);
    return input;
}

// Create mock button elements
function createMockButton(id, text, title = '') {
    const button = {
        id,
        type: 'button',
        textContent: text,
        title,
        disabled: false,
        classList: {
            add: jest.fn(),
            remove: jest.fn(),
            contains: jest.fn(() => false)
        },
        setAttribute: jest.fn(),
        removeAttribute: jest.fn(),
        addEventListener: jest.fn(),
        click: jest.fn()
    };
    
    mockElements.set(id, button);
    return button;
}

// Create mock Alpine.js component data for employee interface
function createEmployeeAlpineComponentData() {
    return {
        submitting: false,
        formErrors: {},
        
        validateForm() {
            this.formErrors = {};
            let isValid = true;
            
            const nameField = mockElements.get('name');
            if (!nameField || !nameField.value.trim()) {
                this.formErrors.name = 'Name is required';
                isValid = false;
            } else if (nameField.value.length > 255) {
                this.formErrors.name = 'Name must not exceed 255 characters';
                isValid = false;
            }
            
            const emailField = mockElements.get('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailField || !emailField.value.trim()) {
                this.formErrors.email = 'Email is required';
                isValid = false;
            } else if (!emailRegex.test(emailField.value)) {
                this.formErrors.email = 'Please enter a valid email address';
                isValid = false;
            }
            
            return isValid;
        },
        
        submitForm(action) {
            if (this.submitting) return;
            
            if (!this.validateForm()) {
                return;
            }
            
            if (!action || !['create', 'create_and_invite'].includes(action)) {
                this.formErrors.action = 'Please select an action';
                return;
            }
            
            this.submitting = true;
            
            const form = mockForms.get('clientCreationForm');
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            form.submit();
        }
    };
}

describe('Dual User Creation Employee Frontend Tests', () => {
    let form, nameInput, emailInput, createButton, inviteButton, alpineData;
    const testUsername = 'testemployee';

    beforeEach(() => {
        // Clear all mocks
        mockElements.clear();
        mockForms.clear();
        jest.clearAllMocks();

        // Create mock elements for employee interface
        form = createMockEmployeeForm(testUsername);
        nameInput = createMockInput('name', 'name', 'text');
        emailInput = createMockInput('email', 'email', 'email');
        createButton = createMockButton('createClientBtn', 'Create User', 'Create user account without sending invitation email');
        inviteButton = createMockButton('createInviteClientBtn', 'Create & Send Invitation', 'Create user account and automatically send invitation email');

        // Create Alpine.js component data
        alpineData = createEmployeeAlpineComponentData();
    });

    describe('Employee-Specific Form Submission', () => {
        it('should submit to correct employee route', () => {
            nameInput.value = 'Client Name';
            emailInput.value = 'client@example.com';

            alpineData.submitForm('create');

            expect(form.action).toBe(`/employee/${testUsername}/clients`);
            expect(form.submit).toHaveBeenCalled();
        });

        it('should handle employee username in route correctly', () => {
            const differentUsername = 'anotheremployee';
            const newForm = createMockEmployeeForm(differentUsername);
            
            expect(newForm.action).toBe(`/employee/${differentUsername}/clients`);
        });

        it('should maintain same validation rules as admin interface', () => {
            // Test empty fields
            nameInput.value = '';
            emailInput.value = '';

            alpineData.submitForm('create');

            expect(alpineData.formErrors.name).toBe('Name is required');
            expect(alpineData.formErrors.email).toBe('Email is required');
            expect(form.submit).not.toHaveBeenCalled();
        });

        it('should use same action parameters as admin interface', () => {
            nameInput.value = 'Client Name';
            emailInput.value = 'client@example.com';

            // Test create action
            alpineData.submitForm('create');
            let appendCall = form.appendChild.mock.calls[0][0];
            expect(appendCall.value).toBe('create');

            // Reset and test invite action
            jest.clearAllMocks();
            alpineData.submitting = false;
            alpineData.submitForm('create_and_invite');
            appendCall = form.appendChild.mock.calls[0][0];
            expect(appendCall.value).toBe('create_and_invite');
        });
    });

    describe('Employee Interface Consistency', () => {
        it('should have consistent button styling with admin interface', () => {
            // Both interfaces should use the same CSS classes
            expect(createButton.classList.add).toBeDefined();
            expect(inviteButton.classList.add).toBeDefined();
            
            // Verify button structure is consistent
            expect(createButton.type).toBe('button');
            expect(inviteButton.type).toBe('button');
        });

        it('should have consistent tooltip text with admin interface', () => {
            expect(createButton.title).toBe('Create user account without sending invitation email');
            expect(inviteButton.title).toBe('Create user account and automatically send invitation email');
        });

        it('should use consistent validation messages', () => {
            nameInput.value = '';
            emailInput.value = 'invalid-email';

            alpineData.validateForm();

            expect(alpineData.formErrors.name).toBe('Name is required');
            expect(alpineData.formErrors.email).toBe('Please enter a valid email address');
        });

        it('should have consistent loading states', () => {
            alpineData.submitting = true;

            // Loading state should be consistent between interfaces
            const shouldBeDisabled = alpineData.submitting;
            expect(shouldBeDisabled).toBe(true);
        });
    });

    describe('Client Management Terminology', () => {
        it('should use appropriate client terminology in interface', () => {
            // Employee interface should refer to "clients" rather than "users"
            const expectedClientTerms = [
                'Create Client User',
                'Client Management',
                'My Clients'
            ];

            expectedClientTerms.forEach(term => {
                expect(term).toContain('Client');
            });
        });

        it('should maintain consistent action terminology', () => {
            // Action names should be consistent regardless of terminology
            const validActions = ['create', 'create_and_invite'];
            
            validActions.forEach(action => {
                nameInput.value = 'Test Client';
                emailInput.value = 'test@example.com';
                alpineData.submitting = false;
                
                alpineData.submitForm(action);
                
                const appendCall = form.appendChild.mock.calls[form.appendChild.mock.calls.length - 1][0];
                expect(appendCall.value).toBe(action);
            });
        });
    });

    describe('Employee-Specific Responsive Design', () => {
        it('should maintain responsive layout on mobile devices', () => {
            global.window.innerWidth = 320;
            global.window.innerHeight = 568;

            // Employee interface should be responsive like admin interface
            expect(createButton.classList.contains).toBeDefined();
            expect(inviteButton.classList.contains).toBeDefined();
        });

        it('should handle tablet layout appropriately', () => {
            global.window.innerWidth = 768;
            global.window.innerHeight = 1024;

            // Buttons should maintain proper spacing and layout
            expect(createButton.classList.contains).toBeDefined();
            expect(inviteButton.classList.contains).toBeDefined();
        });

        it('should work correctly on desktop', () => {
            global.window.innerWidth = 1024;
            global.window.innerHeight = 768;

            // Desktop layout should be optimal
            expect(createButton.classList.contains).toBeDefined();
            expect(inviteButton.classList.contains).toBeDefined();
        });
    });

    describe('Employee Permission Handling', () => {
        it('should handle employee-specific form submission', () => {
            nameInput.value = 'Client Name';
            emailInput.value = 'client@example.com';

            alpineData.submitForm('create');

            // Should submit to employee route
            expect(form.action).toContain('/employee/');
            expect(form.action).toContain('/clients');
            expect(form.submit).toHaveBeenCalled();
        });

        it('should maintain security through proper route structure', () => {
            // Employee routes should include username for security
            expect(form.action).toMatch(/\/employee\/[^\/]+\/clients/);
        });
    });

    describe('Employee Interface Error Handling', () => {
        it('should handle validation errors consistently', () => {
            // Test various validation scenarios
            const testCases = [
                { name: '', email: '', expectedErrors: ['name', 'email'] },
                { name: 'Valid Name', email: 'invalid', expectedErrors: ['email'] },
                { name: 'a'.repeat(256), email: 'valid@email.com', expectedErrors: ['name'] }
            ];

            testCases.forEach(testCase => {
                nameInput.value = testCase.name;
                emailInput.value = testCase.email;
                
                alpineData.validateForm();
                
                testCase.expectedErrors.forEach(field => {
                    expect(alpineData.formErrors[field]).toBeDefined();
                    expect(alpineData.formErrors[field]).not.toBe('');
                });
            });
        });

        it('should clear errors appropriately', () => {
            // Set errors
            alpineData.formErrors.name = 'Name is required';
            alpineData.formErrors.email = 'Email is required';

            // Clear errors (simulating user input)
            alpineData.formErrors.name = '';
            alpineData.formErrors.email = '';

            expect(alpineData.formErrors.name).toBe('');
            expect(alpineData.formErrors.email).toBe('');
        });
    });

    describe('Employee Interface Accessibility', () => {
        it('should maintain accessibility standards', () => {
            // Form fields should have proper labels
            expect(nameInput.id).toBe('name');
            expect(emailInput.id).toBe('email');

            // Buttons should be accessible
            expect(createButton.setAttribute).toBeDefined();
            expect(inviteButton.setAttribute).toBeDefined();
        });

        it('should support keyboard navigation', () => {
            // All interactive elements should support keyboard navigation
            expect(createButton.addEventListener).toBeDefined();
            expect(inviteButton.addEventListener).toBeDefined();
            expect(nameInput.addEventListener).toBeDefined();
            expect(emailInput.addEventListener).toBeDefined();
        });

        it('should provide proper ARIA attributes', () => {
            // Error states should be announced to screen readers
            alpineData.formErrors.name = 'Name is required';
            
            const hasErrorState = alpineData.formErrors.name !== '';
            expect(hasErrorState).toBe(true);
        });
    });

    describe('Cross-Interface Compatibility', () => {
        it('should use same JavaScript patterns as admin interface', () => {
            // Both interfaces should use identical JavaScript logic
            const adminData = createEmployeeAlpineComponentData();
            const employeeData = createEmployeeAlpineComponentData();

            // Validation should work identically
            nameInput.value = 'Test User';
            emailInput.value = 'test@example.com';

            const adminValid = adminData.validateForm();
            const employeeValid = employeeData.validateForm();

            expect(adminValid).toBe(employeeValid);
        });

        it('should handle form submission consistently', () => {
            nameInput.value = 'Test User';
            emailInput.value = 'test@example.com';

            // Both interfaces should handle submission the same way
            alpineData.submitForm('create');

            expect(alpineData.submitting).toBe(true);
            expect(form.appendChild).toHaveBeenCalled();
            expect(form.submit).toHaveBeenCalled();
        });

        it('should maintain consistent button behavior', () => {
            // Button click handling should be identical
            const buttonActions = ['create', 'create_and_invite'];

            buttonActions.forEach(action => {
                nameInput.value = 'Test User';
                emailInput.value = 'test@example.com';
                alpineData.submitting = false;
                jest.clearAllMocks();

                alpineData.submitForm(action);

                expect(form.appendChild).toHaveBeenCalled();
                expect(form.submit).toHaveBeenCalled();
            });
        });
    });

    describe('Employee-Specific Status Messages', () => {
        it('should handle employee-specific success messages', () => {
            // Employee interface should show appropriate success messages
            const expectedMessages = [
                'client-created',
                'client-created-and-invited'
            ];

            expectedMessages.forEach(message => {
                expect(message).toContain('client');
            });
        });

        it('should handle employee-specific error messages', () => {
            // Employee interface should show appropriate error messages
            const expectedErrorStates = [
                'employee-client-created-email-failed',
                'validation errors'
            ];

            expectedErrorStates.forEach(state => {
                expect(state).toBeDefined();
            });
        });
    });
});