<x-share-public-layout :title="__('messages.shares_public_password_title')">
    <div class="max-w-md mx-auto bg-white border border-cream-200 rounded-3xl shadow-[0_2px_8px_rgba(28,25,23,0.04),0_12px_40px_rgba(28,25,23,0.06)] px-8 py-10">
        <h1 class="font-display text-3xl leading-tight">{{ __('messages.shares_public_password_title') }}</h1>
        <p class="mt-2 text-sm text-gray-500">{{ __('messages.shares_public_password_hint') }}</p>

        <form method="POST" action="{{ route('shares.public.unlock', ['token' => $token]) }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="password" :value="__('messages.shares_field_password')" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autofocus autocomplete="off" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <x-primary-button class="w-full justify-center">{{ __('messages.shares_public_unlock') }}</x-primary-button>
        </form>
    </div>
</x-share-public-layout>
