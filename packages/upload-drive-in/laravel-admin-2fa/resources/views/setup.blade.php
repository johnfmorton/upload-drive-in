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
@endsection
