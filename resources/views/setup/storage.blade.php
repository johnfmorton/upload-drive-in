<x-setup-layout 
    :title="'Cloud Storage'" 
    :current-step="4" 
    :total-steps="5" 
    :steps="['Welcome', 'Database', 'Admin User', 'Cloud Storage', 'Complete']">

    <div class="p-8" data-setup-step="storage">
        <!-- Progress Indicator -->
        <x-setup-progress-indicator 
            :current-step="$currentStep ?? 'storage'" 
            :progress="$progress ?? 80" />

        <!-- Success Message -->
        @if(session('success'))
            <x-setup-success-display 
                :message="session('success')" 
                :show-progress="true"
                :progress="$progress ?? 80" />
        @endif

        <!-- Error Display -->
        @if(session('setup_error'))
            <x-setup-error-display 
                :error="session('setup_error')" 
                title="Cloud Storage Configuration Error" />
        @elseif($errors->has('storage_setup'))
            <x-setup-error-display 
                :error="['user_message' => $errors->first('storage_setup')]" 
                title="Cloud Storage Configuration Error" />
        @endif

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 mb-4">
                <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Cloud Storage Configuration</h2>
            <p class="text-gray-600 max-w-md mx-auto">
                Connect your cloud storage provider to automatically store uploaded files. Currently supporting Google Drive with more providers coming soon.
            </p>
        </div>

        <!-- Cloud Storage Configuration Form -->
        <form method="POST" action="{{ route('setup.storage.configure') }}" id="storage-form">
            @csrf

            <!-- Provider Selection -->
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 mb-4">Storage Provider</label>
                <div class="grid grid-cols-1 gap-4">
                    <!-- Google Drive Option -->
                    <div class="relative">
                        <input type="radio" 
                               id="google-drive" 
                               name="provider" 
                               value="google-drive" 
                               class="sr-only peer"
                               {{ old('provider', 'google-drive') === 'google-drive' ? 'checked' : '' }}>
                        <label for="google-drive" 
                               class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-900">Google Drive</span>
                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Available</span>
                                    </div>
                                    <p class="text-xs text-gray-600">
                                        Store files directly in your Google Drive account with automatic folder organization.
                                    </p>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Coming Soon Providers -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 opacity-50">
                        <div class="flex items-center p-4 border-2 border-gray-200 rounded-lg bg-gray-50">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8" viewBox="0 0 24 24">
                                        <path fill="#0078D4" d="M0 0h11.377v11.372H0zm12.623 0H24v11.372H12.623zM0 12.623h11.377V24H0zm12.623 0H24V24H12.623z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-500">Microsoft OneDrive</span>
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">Coming Soon</span>
                                    </div>
                                    <p class="text-xs text-gray-500">Microsoft cloud storage integration</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center p-4 border-2 border-gray-200 rounded-lg bg-gray-50">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8" viewBox="0 0 24 24">
                                        <path fill="#0061FF" d="M7.71 6.366c.176-.467.637-.782 1.155-.782h6.27c.518 0 .979.315 1.155.782l2.84 7.546c.176.467-.09.97-.59.97H5.46c-.5 0-.766-.503-.59-.97l2.84-7.546z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-500">Dropbox</span>
                                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">Coming Soon</span>
                                    </div>
                                    <p class="text-xs text-gray-500">Dropbox cloud storage integration</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @error('provider')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Google Drive Configuration -->
            <div id="google-drive-config" class="mb-8">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-blue-900 mb-4">Google Drive Setup</h3>
                    
                    <!-- Setup Instructions -->
                    <div class="mb-6 p-4 bg-white border border-blue-200 rounded-lg">
                        <h4 class="text-sm font-medium text-blue-900 mb-3">Before you begin:</h4>
                        <ol class="text-sm text-blue-800 space-y-2 list-decimal list-inside">
                            <li>Go to the <a href="https://console.cloud.google.com/" target="_blank" class="underline hover:text-blue-600">Google Cloud Console</a></li>
                            <li>Create a new project or select an existing one</li>
                            <li>Enable the Google Drive API for your project</li>
                            <li>Create OAuth 2.0 credentials (Web application type)</li>
                            <li>Add this redirect URI: <code class="bg-blue-100 px-2 py-1 rounded text-xs">{{ url('/admin/cloud-storage/google-drive/callback') }}</code></li>
                            <li>Copy your Client ID and Client Secret below</li>
                        </ol>
                        <div class="mt-3 text-xs text-blue-700">
                            <a href="https://developers.google.com/drive/api/quickstart/php" target="_blank" class="inline-flex items-center underline hover:text-blue-600">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                View detailed setup guide
                            </a>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Client ID -->
                        <div>
                            <label for="google_client_id" class="block text-sm font-medium text-blue-900">
                                Google Drive Client ID
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="google_client_id" 
                                   name="google_client_id" 
                                   value="{{ old('google_client_id', $googleConfig['client_id'] ?? '') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('google_client_id') border-red-300 @enderror"
                                   placeholder="123456789-abcdefghijklmnop.apps.googleusercontent.com"
                                   required>
                            @error('google_client_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-blue-700">
                                Your Google OAuth 2.0 Client ID from the Google Cloud Console
                            </p>
                        </div>

                        <!-- Client Secret -->
                        <div>
                            <label for="google_client_secret" class="block text-sm font-medium text-blue-900">
                                Google Drive Client Secret
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative">
                                <input type="password" 
                                       id="google_client_secret" 
                                       name="google_client_secret" 
                                       value="{{ old('google_client_secret', $googleConfig['client_secret'] ?? '') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('google_client_secret') border-red-300 @enderror"
                                       placeholder="GOCSPX-abcdefghijklmnopqrstuvwxyz"
                                       required>
                                <button type="button" 
                                        id="toggle-secret" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg id="secret-eye-closed" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                    </svg>
                                    <svg id="secret-eye-open" class="h-5 w-5 text-gray-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                            @error('google_client_secret')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-blue-700">
                                Your Google OAuth 2.0 Client Secret from the Google Cloud Console
                            </p>
                        </div>

                        <!-- Test Connection Button -->
                        <div>
                            <button type="button" 
                                    id="test-google-connection" 
                                    class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Test Google Drive Connection
                            </button>
                            <div id="google-connection-status" class="mt-2 hidden"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Panel -->
            <div class="mb-6">
                <x-setup-help-panel step="storage" />
            </div>

            <!-- Skip Option -->
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Skip Cloud Storage Setup?</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>You can skip this step and configure cloud storage later from the admin dashboard. Files will be stored locally until you set up a cloud provider.</p>
                            <div class="mt-3">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" 
                                           id="skip_storage" 
                                           name="skip_storage" 
                                           value="1"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                           {{ old('skip_storage') ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-yellow-800">Skip cloud storage setup for now</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="{{ route('setup.admin') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Configure Storage
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>

</x-setup-layout>