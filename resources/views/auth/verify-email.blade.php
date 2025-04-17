<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('messages.verify_email_intro') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('messages.verify_email_sent') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('messages.verify_email_resend_button') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] dark:focus:ring-offset-gray-800">
                {{ __('messages.log_out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
