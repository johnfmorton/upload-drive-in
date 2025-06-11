<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.client_dashboard_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1>{{ __('messages.client_dashboard_title') }}</h1>

                    @include('components.upload-stats')

                    <div class="mt-4">
                        @include('client.partials.recent-uploads')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
