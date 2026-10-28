{{-- Confirmation after clicking an email's unsubscribe link. The clicker usually isn't signed in, so this uses the public layout. --}}
<x-share-public-layout :title="__('messages.unsubscribe_success_heading')">
    <div class="max-w-md mx-auto bg-white border border-cream-200 rounded-3xl shadow-[0_2px_8px_rgba(28,25,23,0.04),0_12px_40px_rgba(28,25,23,0.06)] px-8 py-10 text-center">
        <h1 class="font-display text-3xl leading-tight">{{ __('messages.unsubscribe_success_heading') }}</h1>
        <p class="mt-3 text-warm-700">{{ $message }}</p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex items-center px-5 py-2.5 bg-warm-900 rounded-full font-semibold text-sm text-white hover:bg-warm-800 focus:outline-none focus:ring-2 focus:ring-warm-500 focus:ring-offset-2 transition">
            {{ __('messages.notification_go_home') }}
        </a>
    </div>
</x-share-public-layout>
