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

            <form method="POST" action="{{ route('public.employee.upload', ['username' => $username]) }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label for="files" class="block text-sm font-medium text-gray-700">{{ __('messages.choose_files') }}*</label>
                    <input id="files" name="files[]" type="file" multiple class="mt-1 block w-full" required>
                </div>

                <div class="mb-4">
                    <label for="message" class="block text-sm font-medium text-gray-700">{{ __('messages.optional_message') }}</label>
                    <textarea id="message" name="message" rows="3" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="{{ __('messages.optional_message_placeholder') }}"></textarea>
                </div>

                <button type="submit" class="bg-[var(--brand-color)] text-white px-4 py-2 rounded hover:brightness-90 transition">
                    {{ __('messages.upload') }}*
                </button>
            </form>
        </div>
    </div>
@endsection
