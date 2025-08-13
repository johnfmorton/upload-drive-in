<x-setup-layout 
    :title="'Setup Complete'" 
    :current-step="5" 
    :total-steps="5" 
    :steps="['Welcome', 'Database', 'Admin User', 'Cloud Storage', 'Complete']">

    <div class="p-8" data-setup-step="complete">
        <!-- Progress Indicator -->
        <x-setup-progress-indicator 
            :current-step="$currentStep ?? 'complete'" 
            :progress="100" />

        <!-- Success Display -->
        <x-setup-success-display 
            :message="'Setup completed successfully! Your Upload Drive-in installation is now ready to use.'"
            title="🎉 Setup Complete!"
            :show-progress="true"
            :progress="100"
            :details="[
                'Database configured and migrations completed',
                'Administrator account created successfully',
                'Cloud storage provider configured',
                'Application security settings applied',
                'All system requirements verified'
            ]"
            :next-steps="[
                'Access the admin dashboard to complete your profile',
                'Connect your Google Drive account for file storage',
                'Create employee accounts to help manage uploads',
                'Test the upload process with a sample file'
            ]" />

        <!-- Success Header -->
        <div class="text-center mb-8">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-3">Setup Complete!</h2>
            <p class="text-lg text-gray-600 max-w-lg mx-auto">
                Congratulations! {{ config('app.name', 'Upload Drive-In') }} has been successfully configured and is ready to use.
            </p>
        </div>

        <!-- Setup Summary -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuration Summary</h3>
            <div class="bg-gray-50 rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Database Configuration -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Database</dt>
                        <dd class="mt-1 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-900">
                                {{ $setupSummary['database_type'] ?? 'SQLite' }}
                                @if(isset($setupSummary['database_name']))
                                    ({{ $setupSummary['database_name'] }})
                                @endif
                            </span>
                        </dd>
                    </div>

                    <!-- Admin User -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Administrator</dt>
                        <dd class="mt-1 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-900">{{ $setupSummary['admin_email'] ?? 'Created' }}</span>
                        </dd>
                    </div>

                    <!-- Cloud Storage -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Cloud Storage</dt>
                        <dd class="mt-1 flex items-center">
                            @if($setupSummary['cloud_storage_configured'] ?? false)
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm text-gray-900">{{ $setupSummary['cloud_storage_provider'] ?? 'Google Drive' }}</span>
                            @else
                                <svg class="w-4 h-4 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm text-gray-900">Not configured (local storage)</span>
                            @endif
                        </dd>
                    </div>

                    <!-- Application URL -->
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Application URL</dt>
                        <dd class="mt-1 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-900">{{ url('/') }}</span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Next Steps</h3>
            <div class="space-y-4">
                <!-- Admin Dashboard -->
                <div class="flex items-start p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100">
                            <span class="text-sm font-medium text-blue-600">1</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-blue-900">Access Your Admin Dashboard</h4>
                        <p class="mt-1 text-sm text-blue-700">
                            Log in with your administrator credentials to manage users, configure settings, and monitor file uploads.
                        </p>
                        <div class="mt-2">
                            <a href="{{ route('login') }}" 
                               class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500">
                                Go to Admin Login
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Cloud Storage Setup (if skipped) -->
                @if(!($setupSummary['cloud_storage_configured'] ?? false))
                    <div class="flex items-start p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-yellow-100">
                                <span class="text-sm font-medium text-yellow-600">2</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-yellow-900">Configure Cloud Storage</h4>
                            <p class="mt-1 text-sm text-yellow-700">
                                You skipped cloud storage setup. Files will be stored locally until you configure a cloud provider from the admin dashboard.
                            </p>
                            <div class="mt-2">
                                <span class="inline-flex items-center text-sm font-medium text-yellow-600">
                                    Configure later in Admin → Cloud Storage
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Test File Upload -->
                    <div class="flex items-start p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-100">
                                <span class="text-sm font-medium text-green-600">2</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-green-900">Test File Upload</h4>
                            <p class="mt-1 text-sm text-green-700">
                                Your cloud storage is configured. Test the upload functionality to ensure everything is working correctly.
                            </p>
                            <div class="mt-2">
                                <a href="{{ url('/') }}" 
                                   class="inline-flex items-center text-sm font-medium text-green-600 hover:text-green-500">
                                    Visit Upload Page
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Documentation -->
                <div class="flex items-start p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-100">
                            <span class="text-sm font-medium text-gray-600">{{ ($setupSummary['cloud_storage_configured'] ?? false) ? '3' : '3' }}</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-medium text-gray-900">Review Documentation</h4>
                        <p class="mt-1 text-sm text-gray-700">
                            Learn about advanced features, user management, and customization options in the documentation.
                        </p>
                        <div class="mt-2">
                            <span class="inline-flex items-center text-sm font-medium text-gray-600">
                                Available in Admin Dashboard → Help
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Important Security Notes</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Keep your administrator credentials secure and don't share them</li>
                            <li>Regularly update your password from the admin dashboard</li>
                            <li>Monitor file uploads and user activity through the admin panel</li>
                            <li>Consider enabling two-factor authentication for additional security</li>
                            @if(!($setupSummary['cloud_storage_configured'] ?? false))
                                <li>Files are currently stored locally - configure cloud storage for better reliability</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Panel -->
        <div class="mb-8">
            <x-setup-help-panel step="complete" />
        </div>

        <!-- Final Action -->
        <div class="text-center pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-600 mb-4">
                Setup is complete! You can now start using {{ config('app.name', 'Upload Drive-In') }}.
            </p>
            <a href="{{ route('login') }}" 
               class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                Go to Admin Dashboard
            </a>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add some celebration animation
            const successIcon = document.querySelector('.text-green-600');
            if (successIcon) {
                successIcon.style.animation = 'pulse 2s infinite';
            }

            // Auto-redirect after 30 seconds if user doesn't click
            let redirectTimer = setTimeout(function() {
                if (confirm('Would you like to go to the admin dashboard now?')) {
                    window.location.href = '{{ route("login") }}';
                }
            }, 30000);

            // Clear timer if user clicks the button
            document.querySelector('a[href="{{ route("login") }}"]').addEventListener('click', function() {
                clearTimeout(redirectTimer);
            });
        });
    </script>
    @endpush

</x-setup-layout>