<x-guest-layout>
    <div class="text-center mb-10">
        <div class="w-8 h-px bg-accent-500 mx-auto mb-8"></div>
        <h1 class="font-display text-3xl text-warm-900 mb-3 tracking-tight">{{ __('messages.auth_log_in') }}</h1>
        <p class="text-warm-500 text-sm">
            {!! __('messages.login-message') !!}
        </p>
    </div>

    @if (session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="max-w-sm mx-auto">
        @csrf

        <div class="mb-4">
            <label for="email" class="block text-xs font-medium text-warm-500 mb-2 tracking-wide uppercase">{{ __('messages.auth_email') }}</label>
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mb-4">
            <label for="password" class="block text-xs font-medium text-warm-500 mb-2 tracking-wide uppercase">{{ __('messages.auth_password') }}</label>
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mb-6">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-cream-300 text-accent-500 shadow-sm focus:ring-accent-500/30" name="remember">
                <span class="ms-2 text-sm text-warm-500">{{ __('messages.auth_remember_me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs text-accent-500 hover:text-accent-600 transition-colors" href="{{ route('password.request') }}">
                    {{ __('messages.auth_forgot_password') }}
                </a>
            @endif
        </div>

        <button type="submit" class="w-full py-3.5 px-6 bg-warm-900 text-cream-50 rounded-xl font-medium text-sm tracking-wide hover:bg-warm-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-warm-900 transition-all duration-200">
            {{ __('messages.auth_log_in') }}
        </button>
    </form>
</x-guest-layout>
