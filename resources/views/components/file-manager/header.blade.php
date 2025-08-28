@props([
    'userType' => 'admin',
    'statistics' => []
])

@php
    // Define titles and descriptions based on user type with localization support
    $titles = [
        'admin' => __('messages.uploaded_files_title'),
        'employee' => __('messages.uploaded_files_title')
    ];
    
    $descriptions = [
        'admin' => __('messages.file_management_description'),
        'employee' => __('messages.file_management_description')
    ];
    
    // Get the appropriate title and description
    $title = $titles[$userType] ?? $titles['admin'];
    $description = $descriptions[$userType] ?? $descriptions['admin'];
    
    // Ensure statistics is an array with default values
    $stats = array_merge([
        'total' => 0,
        'pending' => 0,
        'total_size' => 0
    ], $statistics);
@endphp

<!-- Header Section -->
<div class="px-4 py-5 sm:px-6 border-b border-gray-200">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                {{ $title }}
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                {{ $description }}
            </p>
        </div>

        <!-- Statistics -->
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center space-x-2">
                <span class="text-gray-500">{{ __('messages.total_files') }}:</span>
                <span class="font-medium" x-text="statistics.total || {{ $stats['total'] }}"></span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-gray-500">{{ __('messages.pending_uploads') }}:</span>
                <span class="font-medium text-yellow-600" x-text="statistics.pending || {{ $stats['pending'] }}"></span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-gray-500">{{ __('messages.total_size') }}:</span>
                <span class="font-medium" x-text="formatBytes(statistics.total_size || {{ $stats['total_size'] }})"></span>
            </div>
        </div>
    </div>
</div>