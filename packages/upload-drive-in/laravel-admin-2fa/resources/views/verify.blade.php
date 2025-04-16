<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Two-Factor Authentication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Enter your authentication code') }}</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Please enter the code from your authenticator app to continue.') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.2fa.verify.store') }}" class="mt-6">
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
                            <x-primary-button>
                                {{ __('Verify') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
