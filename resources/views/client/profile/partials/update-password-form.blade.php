<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('messages.password_update_title') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('messages.password_update_info') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <!-- Hidden username field for accessibility and password managers -->
        <input type="hidden" name="username" value="{{ auth()->user()->email }}" autocomplete="username">

        <div>
            <x-input-label for="update_password_current_password" :value="__('messages.password_current')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            <p class="mt-2 text-sm text-gray-600">
                {{ __('Don\'t know your current password?') }} 
                <button type="button" onclick="sendPasswordReset()" class="text-blue-600 hover:text-blue-800 underline bg-transparent border-none cursor-pointer">
                    {{ __('Click here to send a password reset link to your email address.') }}
                </button>
            </p>
            <div id="password-reset-status" class="mt-2 text-sm" style="display: none;"></div>
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('messages.password_new')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('messages.password_confirm')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('messages.profile_save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('messages.password_updated') }}</p>
            @endif
        </div>
    </form>
</section>