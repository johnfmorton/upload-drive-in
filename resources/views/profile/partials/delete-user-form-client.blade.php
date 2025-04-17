<section class="space-y-6" x-data="{ showEmailSentModal: false }">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('messages.delete_account_title') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('messages.delete_account_info') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('messages.delete_account_button') }}</x-danger-button>

    <!-- Initial Confirmation Modal -->
    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6" id="deletion-form">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('messages.delete_account_confirm_title') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('messages.delete_account_client_confirm_info') }}
            </p>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('messages.delete_account_cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3" type="submit">
                    {{ __('messages.delete_account_button') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>

    <!-- Email Sent Confirmation Modal -->
    @if (session('deletion-requested'))
    <x-modal name="deletion-email-sent" :show="true" focusable>
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('messages.delete_account_email_sent_title') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('messages.delete_account_email_sent_info') }}
            </p>

            <div class="mt-6 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('messages.delete_account_email_sent_understood') }}
                </x-primary-button>
            </div>
        </div>
    </x-modal>
    @endif

    @if($errors->userDeletion->any())
        <div class="mt-4 text-sm text-red-600">
            <ul>
                @foreach($errors->userDeletion->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</section>

<script>
document.getElementById('deletion-form').addEventListener('submit', function(e) {
    console.log('Form submission triggered');
});
</script>
