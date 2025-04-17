<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {!! __('messages.login-message') !!}
    </div>

    @if (session('error'))
        <div class="mb-4 font-medium text-sm text-red-600">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('messages.auth_email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('messages.auth_password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)] dark:focus:ring-[var(--brand-color)] dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('messages.auth_remember_me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('messages.auth_forgot_password') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('messages.auth_log_in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
