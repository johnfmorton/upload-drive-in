<x-guest-layout>
    <div class="text-warm-900">
        {{-- Brand mark --}}
        <div class="text-center mb-12">
            @if(File::exists(public_path('images/app-icon.png')))
                <img src="{{ asset('images/app-icon.png') }}?v={{ md5_file(public_path('images/app-icon.png')) }}" alt="{{ __('messages.nav_logo_alt', ['company_name' => config('app.company_name')]) }}" class="w-auto h-10 mx-auto mb-8">
            @endif

            {{-- Decorative brass line --}}
            <div class="w-8 h-px bg-accent-500 mx-auto mb-8"></div>

            <h1 class="font-display text-4xl sm:text-5xl text-warm-900 mb-4 tracking-tight leading-[1.1]">
                {{ __('messages.email_validation_title') }}<br>
                {{ config('app.company_name') }}
            </h1>
            <p class="text-warm-500 text-base max-w-sm mx-auto leading-relaxed">{{ __('messages.email_validation_subtitle') }}</p>
        </div>

        {{-- Trust indicators with refined styling --}}
        <div class="flex items-center justify-center gap-8 mb-12">
            <div class="flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full bg-cream-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <span class="text-[10px] tracking-[0.15em] uppercase text-warm-400 font-medium">Encrypted</span>
            </div>
            <div class="flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full bg-cream-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <span class="text-[10px] tracking-[0.15em] uppercase text-warm-400 font-medium">Secure</span>
            </div>
            <div class="flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full bg-cream-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                </div>
                <span class="text-[10px] tracking-[0.15em] uppercase text-warm-400 font-medium">No size limit</span>
            </div>
        </div>

        {{-- Email form --}}
        <form id="emailValidationForm" class="max-w-sm mx-auto">
            @csrf
            <div class="mb-4">
                <label for="email" class="block text-xs font-medium text-warm-500 mb-2 tracking-wide uppercase">{{ __('messages.nav_email_label') }}</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}"
                    class="w-full px-4 py-3.5 bg-cream-50 border border-cream-300 rounded-xl text-warm-900 placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-accent-500/20 focus:border-accent-500 transition-all duration-200 text-sm"
                    placeholder="{{ __('messages.nav_email_placeholder') }}">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                class="w-full py-3.5 px-6 bg-warm-900 text-cream-50 rounded-xl font-medium text-sm tracking-wide hover:bg-warm-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-warm-900 transition-all duration-200">
                {{ __('messages.nav_validate_email_button') }}
            </button>
        </form>

        {{-- Feedback messages --}}
        <div id="validationMessage" class="mt-5 text-center hidden">
            <p class="text-warm-600 text-sm">{{ __('messages.nav_validation_success', ['company_name' => config('app.company_name')]) }}</p>
        </div>

        <div id="errorMessage" class="mt-5 text-center hidden">
            <p id="errorMessageText" class="text-red-600 text-sm">{{ __('messages.nav_validation_error') }}</p>
        </div>

        {{-- Divider --}}
        <div class="mt-12 pt-8 border-t border-cream-200 text-center">
            <p class="text-sm text-warm-400 mb-3">{{ __('messages.already_have_account') }}</p>
            <a href="{{ route('login') }}"
               class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-warm-700 bg-transparent border border-cream-300 rounded-xl hover:bg-cream-100 hover:border-cream-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-warm-500 transition-all duration-200">
                {{ __('messages.sign_in') }}
            </a>
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

            submitButton.disabled = true;
            submitButton.textContent = '{{ __('messages.nav_validate_email_sending') }}';

            fetch('{{ route('validate-email') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json().then(data => ({status: response.status, data})))
            .then(({status, data}) => {
                if (status === 200 && data.success) {
                    validationMessage.classList.remove('hidden');
                    errorMessage.classList.add('hidden');
                    this.reset();
                    setTimeout(() => {
                        validationMessage.classList.add('hidden');
                    }, 10000);
                } else {
                    const errorMessageText = document.getElementById('errorMessageText');
                    errorMessageText.textContent = data.message || '{{ __('messages.nav_validation_error') }}';
                    validationMessage.classList.add('hidden');
                    errorMessage.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMessageText = document.getElementById('errorMessageText');
                errorMessageText.textContent = '{{ __('messages.nav_validation_error') }}';
                validationMessage.classList.add('hidden');
                errorMessage.classList.remove('hidden');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = '{{ __('messages.nav_validate_email_button') }}';
            });
        });
    </script>
    @endpush
</x-guest-layout>
