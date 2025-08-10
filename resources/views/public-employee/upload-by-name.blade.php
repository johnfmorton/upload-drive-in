<?php /* resources/views/public-employee/upload-by-name.blade.php */ ?>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.upload_files_for_employee', ['name' => $employee->name]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-gray-900">{{ __('messages.upload_files_for_employee', ['name' => $employee->name]) }}</h1>
                        <p class="text-gray-600">{{ __('messages.upload_files_description') }}</p>
                    </div>

                    @if(!$hasGoogleDriveConnected)
                        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-yellow-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-yellow-800">{{ __('messages.employee_drive_not_connected') }}</p>
                                    <p class="text-sm text-yellow-600">{{ __('messages.files_will_go_to_admin_drive') }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-green-800">{{ __('messages.ready_to_receive_files') }}</p>
                                    <p class="text-sm text-green-600">{{ __('messages.files_will_go_to_employee_drive') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form id="messageForm" class="space-y-6">
                        @csrf {{-- Important for CSRF protection --}}

                        {{-- Dropzone Container --}}
                        <div id="file-upload-dropzone"
                             class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-[var(--brand-color)] transition-colors duration-200"
                             data-upload-url="{{ route('upload.employee.chunk', ['name' => $name]) }}">
                            <div class="dz-message" data-dz-message>
                                <span class="block text-lg font-medium text-gray-700">Drop files here or click to upload.</span>
                                <span class="block text-sm text-gray-500">(Large files will be uploaded in chunks)</span>
                            </div>
                            {{-- Dropzone will automatically add file previews here --}}
                        </div>

                        {{-- Hidden input to store successful upload IDs --}}
                        <input type="hidden" name="file_upload_ids" id="file_upload_ids" value="[]">

                        {{-- Area to display upload errors --}}
                        <div id="upload-errors" class="hidden mt-4"></div>

                        {{-- Message Textarea --}}
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message (Optional)</label>
                            <textarea id="message" name="message" rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--brand-color)] focus:border-[var(--brand-color)]"
                                      placeholder="Enter an optional message to associate with the uploaded files..."></textarea>
                        </div>

                         {{-- Submit Button --}}
                         <div class="text-center">
                             <button type="submit"
                                     class="bg-[var(--brand-color)] text-white px-6 py-2 rounded-md hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] transition-colors duration-200">
                                 Upload and Send Message
                             </button>
                         </div>
                    </form>
                </div>
            </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Success Modal --}}
    <div id="success-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden" aria-hidden="true">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Upload Complete!</h3>
                    <p class="text-sm text-gray-600">Your files have been uploaded successfully to {{ $employee->name }}.</p>
                    <div class="mt-6">
                        <button type="button" onclick="window.location.reload()"
                                class="w-full bg-[var(--brand-color)] text-white px-4 py-2 rounded hover:brightness-90 transition">
                            Upload More Files
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Employee-specific upload functionality
        // The main app.js will handle the Dropzone initialization
        // We just need to customize the endpoints for employee uploads
        
        // Override the associate message and batch complete URLs for employee uploads
        window.employeeUploadConfig = {
            associateMessageUrl: '{{ route('upload.employee.associate-message', ['name' => $name]) }}',
            batchCompleteUrl: '{{ route('upload.employee.batch-complete', ['name' => $name]) }}',
            employeeName: '{{ $employee->name }}'
        };

        // Listen for modal events (triggered by main app.js)
        window.addEventListener('open-modal', function(e) {
            const modalName = e.detail;
            if (modalName === 'upload-success' || modalName === 'association-success') {
                document.getElementById('success-modal').classList.remove('hidden');
            }
        });
    </script>
    @endpush
</x-app-layout>