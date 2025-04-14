<section class="space-y-6" x-data="{ showEmailSentModal: false }">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <!-- Initial Confirmation Modal -->
    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6" id="deletion-form">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. A confirmation email will be sent to verify this action.') }}
            </p>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3" type="submit">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>

    <!-- Email Sent Confirmation Modal -->
    @if (session('deletion-requested'))
    <x-modal name="deletion-email-sent" :show="true" focusable>
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Check Your Email') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('A confirmation email has been sent to your email address. Please check your inbox and follow the link to complete the account deletion process.') }}
            </p>

            <div class="mt-6 flex justify-end">
                <x-primary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Understood') }}
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
