<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('messages.profile_information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('messages.profile_update_info') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('messages.profile_name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('messages.profile_email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('messages.profile_email_unverified') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] dark:focus:ring-offset-gray-800">
                            {{ __('messages.profile_email_verify_resend') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('messages.profile_email_verify_sent') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Add Checkbox for Notification Preference -->
        <div class="block mt-4">
            <label for="receive_upload_notifications" class="inline-flex items-center">
                <input id="receive_upload_notifications" type="checkbox" class="rounded border-gray-300 text-[var(--brand-color)] shadow-sm focus:ring-[var(--brand-color)]" name="receive_upload_notifications" value="1" {{ old('receive_upload_notifications', $user->receive_upload_notifications) ? 'checked' : '' }}>
                <span class="ms-2 text-sm text-gray-600">{{ __('messages.profile_receive_notifications_label') }}</span>
            </label>
        </div>
        <!-- End Checkbox -->

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('messages.profile_save') }}</x-primary-button>

            @if (session('status'))
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 4000)"
                    class="text-sm font-medium {{ session('status') === 'profile-updated' || str_contains(session('status'), 'unsubscribe') ? 'text-green-600' : 'text-gray-600' }}"
                >{{ session('status') }}</p>
            @endif
        </div>
    </form>
</section>
