<x-guest-layout>
    <div class="text-gray-900">
                    <div class="text-center mb-8">
                        @if(File::exists(public_path('images/app-icon.png')))
                            <img src="{{ asset('images/app-icon.png') }}?v={{ md5_file(public_path('images/app-icon.png')) }}" alt="{{ __('messages.nav_logo_alt', ['company_name' => config('app.company_name')]) }}" class="w-auto h-8 mx-auto mb-4">
                        @endif
                        <h1 class="text-2xl font-bold text-gray-900 mb-4 text-balance">{{ __('messages.email_validation_title', ['company_name' => config('app.company_name')]) }}</h1>
                        <p class="text-gray-600">{{ __('messages.email_validation_subtitle') }}</p>
                    </div>

                    <form id="emailValidationForm" class="max-w-md mx-auto">
                        @csrf
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.nav_email_label') }}</label>
                            <input type="email" id="email" name="email" required value="{{ old('email') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--brand-color)] focus:border-[var(--brand-color)]"
                                placeholder="{{ __('messages.nav_email_placeholder') }}">
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="text-center">
                            <button type="submit"
                                class="bg-[var(--brand-color)] text-white px-4 py-2 rounded-md hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                                {{ __('messages.nav_validate_email_button') }}
                            </button>
                        </div>
                    </form>

                    <div id="validationMessage" class="mt-4 text-center hidden">
                        <p class="text-gray-600">{{ __('messages.nav_validation_success', ['company_name' => config('app.company_name')]) }}</p>
                    </div>

                    <div id="errorMessage" class="mt-4 text-center hidden">
                        <p class="text-red-600">{{ __('messages.nav_validation_error') }}</p>
                    </div>
    </div>
    @push('scripts')
    <script>
        document.getElementById('emailValidationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const validationMessage = document.getElementById('validationMessage');
            const errorMessage = document.getElementById('errorMessage');
            const submitButton = this.querySelector('button[type="submit"]');

            // Disable the submit button
            submitButton.disabled = true;
            submitButton.innerHTML = '{{ __('messages.nav_validate_email_sending') }}';

            fetch('{{ route('validate-email') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    validationMessage.classList.remove('hidden');
                    errorMessage.classList.add('hidden');
                    this.reset();

                    // Keep the message visible for 10 seconds
                    setTimeout(() => {
                        validationMessage.classList.add('hidden');
                    }, 10000);
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                validationMessage.classList.add('hidden');
                errorMessage.classList.remove('hidden');
            })
            .finally(() => {
                // Re-enable the submit button
                submitButton.disabled = false;
                submitButton.innerHTML = '{{ __('messages.nav_validate_email_button') }}';
            });
        });
    </script>
    @endpush
</x-guest-layout>
