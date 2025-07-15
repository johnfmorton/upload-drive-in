<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h1 class="text-xl font-bold mb-4">{{ __('messages.unsubscribe_success_heading') }}</h1>
            <p class="mb-6">{{ $message }}</p>
            <a href="{{ route('home') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                {{ __('messages.notification_go_home') }}
            </a>
        </div>
    </div>
</x-app-layout>
