<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl text-warm-900 leading-tight">
            {{ __('File Manager') }}
        </h2>
    </x-slot>

    <div class="py-6" style="min-height: auto;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- File Management Dashboard -->
            <div class="bg-white shadow sm:rounded-lg">
                <!-- Use the shared file manager index component -->
                <x-file-manager.index 
                    user-type="employee" 
                    :username="auth()->user()->username"
                    :files="$files" 
                    :statistics="$statistics ?? []" 
                />
            </div>
        </div>
    </div>


</x-app-layout>
