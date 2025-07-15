<?php /* resources/views/public-employee/upload-by-name.blade.php */ ?>
@extends('layouts.guest')

@section('content')
    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-2">{{ __('messages.upload_files_for_employee', ['name' => $employee->name]) }}</h1>
                <p class="text-gray-600 mb-6">{{ __('messages.upload_files_description') }}</p>

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

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('upload.employee.submit', ['name' => $name]) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">{{ __('messages.your_email') }}*</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50" required>
                        <p class="mt-1 text-sm text-gray-500">{{ __('messages.email_for_organization') }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="files" class="block text-sm font-medium text-gray-700">{{ __('messages.choose_files') }}*</label>
                        <input id="files" name="files[]" type="file" multiple class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50" required>
                        <p class="mt-1 text-sm text-gray-500">{{ __('messages.max_file_size_10mb') }}</p>
                    </div>

                    <div class="mb-6">
                        <label for="message" class="block text-sm font-medium text-gray-700">{{ __('messages.optional_message') }}</label>
                        <textarea id="message" name="message" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50" placeholder="{{ __('messages.optional_message_placeholder') }}">{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="w-full bg-[var(--brand-color)] text-white px-4 py-2 rounded-md hover:brightness-90 transition font-medium">
                        {{ __('messages.upload_files') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection