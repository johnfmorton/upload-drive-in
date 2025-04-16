@extends('laravel-admin-2fa::layouts.guest')

@section('content')
<div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-gray-900">
            {{ __('Two-Factor Authentication Setup') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Use an authenticator app like Google Authenticator, Authy, or 1Password to scan this QR code.') }}
        </p>
    </div>

    @if (session('warning'))
        <div class="mb-4 text-sm text-yellow-600">
            {{ session('warning') }}
        </div>
    @endif

    <div class="mt-4 flex justify-center">
        <div class="bg-gray-100 p-4 rounded-lg">
            {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($qrCodeUrl) !!}
        </div>
    </div>

    {{-- Manual Entry Section --}}
    <div class="mt-4">
        <div class="text-sm font-medium text-gray-700 mb-2">{{ __('Manual Entry Code') }}</div>
        <div class="flex items-center space-x-2">
            <input type="text" value="{{ $secret }}" class="bg-gray-100 text-gray-800 text-sm py-2 px-3 rounded-md w-full font-mono" readonly>
            <button onclick="copyToClipboard(this)" data-secret="{{ $secret }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-md text-sm" type="button">
                <span class="copy-text">{{ __('Copy') }}</span>
            </button>
        </div>
        <p class="mt-1 text-xs text-gray-500">
            {{ __('If you can\'t scan the QR code, you can manually enter this code into your authenticator app.') }}
        </p>
    </div>

    {{-- Recovery Codes Section --}}
    <div class="mt-6">
        <div class="text-sm font-medium text-gray-700 mb-2">{{ __('Recovery Codes') }}</div>
        <div class="bg-gray-100 p-4 rounded-lg">
            <div class="space-y-1 font-mono text-sm">
                @foreach ($recoveryCodes as $code)
                    <div class="flex items-center justify-between">
                        <span>{{ $code }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-500">
            {{ __('Store these recovery codes in a secure location. They can be used to recover access to your account if you lose your 2FA device.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('admin.2fa.enable') }}" class="mt-6">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Verification Code')" />
            <x-text-input id="code"
                         type="text"
                         name="code"
                         class="mt-1 block w-full"
                         required
                         autocomplete="off" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ __('Enable Two-Factor Authentication') }}
            </x-primary-button>
        </div>
    </form>
</div>

{{-- Copy to Clipboard Script --}}
<script>
function copyToClipboard(button) {
    const secret = button.getAttribute('data-secret');
    navigator.clipboard.writeText(secret).then(() => {
        const span = button.querySelector('.copy-text');
        const originalText = span.textContent;
        span.textContent = '{{ __("Copied!") }}';
        setTimeout(() => {
            span.textContent = originalText;
        }, 2000);
    });
}
</script>
@endsection
