<x-guest-layout>
    <div>
        <div class="mb-4 text-center">
            <h2 class="text-2xl font-bold text-gray-900">
                {{ __('messages.2fa_verify_title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600 text-balance">
                {{ __('messages.2fa_verify_instruction') }}
            </p>
            <p class="mt-1 text-sm text-gray-500 text-balance">
                {{ __('messages.2fa_verify_recovery_info') }}
            </p>
        </div>

        @if (session('warning'))
            <div class="mb-4 text-sm text-yellow-600">
                {{ session('warning') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.2fa.verify.store') }}">
            @csrf

            <div>
                <x-input-label for="code" :value="__('messages.2fa_verify_code_label')" />
                <x-text-input id="code"
                             type="text"
                             name="code"
                             class="mt-1 block w-full"
                             required
                             autofocus
                             autocomplete="off" />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div class="mt-6">
                <x-primary-button class="w-full justify-center bg-[var(--brand-color)]">
                    {{ __('messages.2fa_verify_button') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
