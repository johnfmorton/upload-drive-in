<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cloud Storage') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Google Drive Integration</h3>
                        <p class="text-gray-600 mb-6">
                            Connect your Google Drive account to automatically store uploaded files in the cloud.
                        </p>

                        @if (auth()->user()->hasGoogleDriveConnected())
                            <!-- Connected State -->
                            <div class="border rounded-lg p-6 bg-green-50 border-green-200">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center text-green-700">
                                        <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-lg font-medium">Connected to Google Drive</span>
                                    </div>
                                    <form method="POST"
                                        action="{{ route('employee.google-drive.disconnect', ['username' => auth()->user()->username]) }}"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                            Disconnect
                                        </button>
                                    </form>
                                </div>

                                <!-- Upload Destination Settings -->
                                <div class="border-t border-green-200 pt-6">
                                    <h4 class="text-md font-medium mb-4 text-green-800">Upload Destination</h4>
                                    <p class="text-sm text-green-700 mb-4">
                                        Choose where files uploaded to you will be stored in your Google Drive.
                                    </p>

                                    @include('employee.google-drive.google-drive-root-folder')
                                </div>
                            </div>
                        @else
                            <!-- Disconnected State -->
                            <div class="border rounded-lg p-6 bg-yellow-50 border-yellow-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center text-yellow-700">
                                        <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <span class="text-lg font-medium">Google Drive Not Connected</span>
                                            <p class="text-sm text-yellow-600 mt-1">
                                                Files uploaded to you will be stored in the admin's Google Drive until
                                                you connect your own account.
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ route('employee.google-drive.connect', ['username' => auth()->user()->username]) }}"
                                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 inline-block">
                                        Connect Google Drive
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Information Section -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h4 class="text-md font-medium mb-3 text-gray-800">How It Works</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                When clients upload files and select you as the recipient, files will be stored in your
                                Google Drive
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                Files are organized in folders by client email address for easy management
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                You can choose a specific folder as your upload destination using the folder picker
                                above
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                Your Google Drive credentials are securely stored and only used for file uploads
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
