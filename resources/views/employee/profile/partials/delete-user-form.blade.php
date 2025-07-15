@php
    $showPasswordField = auth()->user()->canLoginWithPassword();
@endphp

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('messages.delete_account_title') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('messages.delete_account_warning') }}
        </p>
    </header>

    @if (session('deletion-requested'))
        <div class="bg-green-50 border border-green-200 rounded p-4 mb-4">
            <p class="text-green-700">{{ __('messages.delete_account_email_sent') }}</p>
        </div>
    @endif

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('messages.delete_account_button') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('employee.profile.destroy', ['username' => auth()->user()->username]) }}" class="p-6" id="deletion-form">
            @csrf
            @method('delete')

            <!-- Hidden username field for accessibility and password managers -->
            <input type="hidden" name="username" value="{{ auth()->user()->email }}" autocomplete="username">

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('messages.delete_account_confirm_title') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('messages.delete_account_client_confirm_info') }}
            </p>

            @if ($showPasswordField)
                <div class="mt-6">
                    <x-input-label for="employee_delete_password" value="{{ __('messages.password') }}" class="sr-only" />

                    <x-text-input
                        id="employee_delete_password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="{{ __('messages.password') }}"
                        autocomplete="current-password"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('messages.cancel') }}
                </x-secondary-button>

                <x-danger-button class="ml-3">
                    {{ __('messages.delete_account_confirm_button') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
