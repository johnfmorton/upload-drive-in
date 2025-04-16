<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            @if (auth()->user()->isAdmin())
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <section>
                            <header>
                                <h2 class="text-lg font-medium text-gray-900">
                                    {{ __('messages.two_factor_authentication') }}
                                </h2>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ __('messages.two_factor_description') }}
                                </p>
                            </header>

                            <div class="mt-6">
                                @if (!auth()->user()->two_factor_enabled)
                                    <a href="{{ route('admin.2fa.setup') }}" class="inline-flex items-center px-4 py-2 bg-[var(--brand-color)] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-[var(--brand-color)] focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('messages.setup_2fa_button') }}
                                    </a>
                                @else
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-green-600">
                                            {{ __('messages.2fa_enabled_message') }}
                                        </div>
                                        <form method="POST" action="{{ route('admin.2fa.disable') }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                {{ __('messages.disable_2fa_button') }}
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>
                </div>
            @endif

            @if (!auth()->user()->isClient())
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
