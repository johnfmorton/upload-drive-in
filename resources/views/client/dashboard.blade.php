<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl text-warm-900 leading-tight">
            {{ __('messages.client_dashboard_title') }}
        </h2>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('components.upload-stats')

            @include('client.partials.recent-uploads')
        </div>
    </div>
</x-app-layout>
