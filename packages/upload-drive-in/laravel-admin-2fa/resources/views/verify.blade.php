@extends('laravel-admin-2fa::layouts.guest')

@section('content')
<div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-gray-900">
            {{ __('Two-Factor Authentication') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Please enter the code from your authenticator app to continue.') }}
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
            <x-input-label for="code" :value="__('Authentication Code')" />
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
            <x-primary-button class="w-full justify-center">
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>
</div>
@endsection
