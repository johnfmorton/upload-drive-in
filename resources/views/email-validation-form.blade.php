<x-guest-layout>
    <div class="bg-gray-100 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4 text-balance">Upload files to {{ config('app.company_name') }}</h1>
                        <p class="text-gray-600">Begin by validating your email address.</p>
                    </div>

                    <form id="emailValidationForm" class="max-w-md mx-auto">
                        @csrf
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" id="email" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="your@email.com">
                        </div>

                        <div class="text-center">
                            <button type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('messages.validate-email-button') }}
                            </button>
                        </div>
                    </form>

                    <div id="validationMessage" class="mt-4 text-center hidden">
                        <p class="text-gray-600">You will receive an email with a link to validate your email address. Clicking the link we send you will allow you to upload files to {{ config('app.company_name') }}.</p>
                    </div>

                    <div id="errorMessage" class="mt-4 text-center hidden">
                        <p class="text-red-600">There was an error processing your request. Please try again.</p>
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
            submitButton.innerHTML = 'Sending...';

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
                submitButton.innerHTML = '{{ __('messages.validate-email-button') }}';
            });
        });
    </script>
    @endpush
</x-guest-layout>
