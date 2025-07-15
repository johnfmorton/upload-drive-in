<x-guest-layout>
    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-center mb-8">
                    @if(File::exists(public_path('images/app-icon.png')))
                        <img src="{{ asset('images/app-icon.png') }}?v={{ md5_file(public_path('images/app-icon.png')) }}" alt="{{ __('messages.nav_logo_alt', ['company_name' => config('app.company_name')]) }}" class="w-auto h-8 mx-auto mb-4">
                    @endif
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('messages.upload_files_for_employee', ['name' => $employee->name]) }}</h1>
                    <p class="text-gray-600 mb-6">{{ __('messages.email_validation_required_for_upload') }}</p>
                </div>

                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-800">{{ __('messages.authentication_required') }}</p>
                            <p class="text-sm text-blue-600">{{ __('messages.validate_email_to_upload_files') }}</p>
                        </div>
                    </div>
                </div>

                <form id="emailValidationForm" class="max-w-md mx-auto">
                    @csrf
                    <input type="hidden" name="intended_url" value="{{ request()->url() }}">
                    
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
                            class="w-full bg-[var(--brand-color)] text-white px-4 py-2 rounded-md hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                            {{ __('messages.nav_validate_email_button') }}
                        </button>
                    </div>
                </form>

                <div id="validationMessage" class="mt-4 text-center hidden">
                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-800 font-medium">{{ __('messages.validation_email_sent') }}</p>
                        <p class="text-green-600 text-sm mt-1">{{ __('messages.check_email_and_click_link') }}</p>
                    </div>
                </div>

                <div id="errorMessage" class="mt-4 text-center hidden">
                    <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-800">{{ __('messages.nav_validation_error') }}</p>
                    </div>
                </div>
            </div>
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