<?php /* resources/views/public-employee/upload-page.blade.php */ ?>
@extends('layouts.guest')

@section('content')
    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-4">{{ __('messages.drop_files_for', ['name' => $username]) }}*</h1>

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

            <form method="POST" action="{{ route('public.employee.upload', ['username' => $username]) }}" enctype="multipart/form-data">
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

                <div class="mb-4">
                    <label for="message" class="block text-sm font-medium text-gray-700">{{ __('messages.optional_message') }}</label>
                    <textarea id="message" name="message" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-[var(--brand-color)] focus:ring focus:ring-[var(--brand-color)]/50" placeholder="{{ __('messages.optional_message_placeholder') }}">{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="w-full bg-[var(--brand-color)] text-white px-4 py-2 rounded-md hover:brightness-90 transition font-medium">
                    {{ __('messages.upload_files') }}
                </button>
            </form>
        </div>
    </div>
@endsection
