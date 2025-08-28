<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.file_management_title') }}
        </h2>
    </x-slot>

    <div class="py-6" style="min-height: auto;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- File Management Dashboard using shared components -->
            <x-file-manager.index 
                userType="admin"
                :files="$files"
                :statistics="$statistics ?? []" />
        </div>
    </div>
</x-app-layout>