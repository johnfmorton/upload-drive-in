<x-setup-layout 
    :title="'Admin User'" 
    :current-step="3" 
    :total-steps="5" 
    :steps="['Welcome', 'Database', 'Admin User', 'Cloud Storage', 'Complete']">

    <div class="p-8" data-setup-step="admin">
        <!-- Progress Indicator -->
        <x-setup-progress-indicator 
            :current-step="$currentStep ?? 'admin'" 
            :progress="$progress ?? 60" />

        <!-- Success Message -->
        @if(session('success'))
            <x-setup-success-display 
                :message="session('success')" 
                :show-progress="true"
                :progress="$progress ?? 60" />
        @endif

        <!-- Error Display -->
        @if(session('setup_error'))
            <x-setup-error-display 
                :error="session('setup_error')" 
                title="Admin Account Creation Error" />
        @elseif($errors->has('admin_creation'))
            <x-setup-error-display 
                :error="['user_message' => $errors->first('admin_creation')]" 
                title="Admin Account Creation Error" />
        @endif

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-purple-100 mb-4">
                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Create Administrator Account</h2>
            <p class="text-gray-600 max-w-md mx-auto">
                Create your administrator account to manage the application. This account will have full access to all features and settings.
            </p>
        </div>

        <!-- Admin User Creation Form -->
        <form method="POST" action="{{ route('setup.admin.create') }}" id="admin-form">
            @csrf

            <div class="space-y-6">
                <!-- Administrator Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Administrator Name
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}"
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-300 @enderror"
                               placeholder="John Administrator"
                               required
                               autocomplete="name"
                               maxlength="255">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">
                        Enter the full name for the administrator account. This will be displayed in the admin dashboard.
                    </p>
                </div>

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Address
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}"
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-300 @enderror"
                               placeholder="admin@example.com"
                               required
                               autocomplete="email">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                        </div>
                    </div>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">
                        This email will be used to log into the admin dashboard and receive important notifications.
                    </p>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-300 @enderror"
                               required
                               autocomplete="new-password"
                               minlength="8">
                        <button type="button" 
                                id="toggle-password" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg id="eye-closed" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                            </svg>
                            <svg id="eye-open" class="h-5 w-5 text-gray-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    
                    <!-- Password Strength Indicator -->
                    <div class="mt-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Password strength:</span>
                            <span id="strength-text" class="font-medium text-gray-400">Enter password</span>
                        </div>
                        <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                            <div id="strength-bar" class="h-2 rounded-full transition-all duration-300 bg-gray-300" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Password Requirements -->
                    <div class="mt-3 text-sm text-gray-600">
                        <p class="mb-2">Password must contain:</p>
                        <ul class="space-y-1">
                            <li id="req-length" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                At least 8 characters
                            </li>
                            <li id="req-uppercase" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                One uppercase letter
                            </li>
                            <li id="req-lowercase" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                One lowercase letter
                            </li>
                            <li id="req-number" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                One number
                            </li>
                            <li id="req-special" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                One special character
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirm Password
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password_confirmation') border-red-300 @enderror"
                               required
                               autocomplete="new-password">
                        <div id="password-match-indicator" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none hidden">
                            <svg id="match-success" class="h-5 w-5 text-green-500 hidden" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <svg id="match-error" class="h-5 w-5 text-red-500 hidden" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    @error('password_confirmation')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p id="password-match-text" class="mt-2 text-sm text-gray-500">
                        Re-enter your password to confirm
                    </p>
                </div>
            </div>

            <!-- Help Panel -->
            <div class="mt-6">
                <x-setup-help-panel step="admin" />
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200 mt-8">
                <a href="{{ route('setup.database') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back
                </a>
                <button type="submit" 
                        id="submit-btn"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    Create Admin Account
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        @vite('resources/js/admin-user-creation.js')
    @endpush

</x-setup-layout>