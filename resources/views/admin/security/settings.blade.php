<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.security_and_access_settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Public Registration Settings -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('messages.public_registration_settings') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('messages.public_registration_description') }}
                            </p>
                        </header>

                        <form method="post" action="{{ route('admin.security.update-registration') }}" class="mt-6">
                            @csrf
                            @method('put')

                            <div class="flex items-center gap-4">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="allow_public_registration" class="sr-only peer"
                                           {{ $settings['allow_public_registration'] ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4
                                                peer-focus:ring-[var(--brand-color)]/50 rounded-full peer
                                                peer-checked:after:translate-x-full peer-checked:after:border-white
                                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:border-gray-300 after:border after:rounded-full
                                                after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--brand-color)]">
                                    </div>
                                    <span class="ml-3 text-sm font-medium text-gray-900">
                                        {{ __('messages.allow_public_registration') }}
                                    </span>
                                </label>
                            </div>

                            <div class="flex items-center gap-4 mt-4">
                                <x-primary-button>{{ __('messages.save_button') }}</x-primary-button>

                                @if (session('status') && str_contains(session('status'), 'registration'))
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 4000)"
                                        class="text-sm font-medium text-green-600"
                                    >{{ session('status') }}</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            <!-- Domain Access Control -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('messages.domain_access_control') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('messages.domain_access_description') }}
                            </p>
                        </header>

                        <form method="post" action="{{ route('admin.security.update-domain-rules') }}" class="mt-6">
                            @csrf
                            @method('put')

                            <!-- Access Control Mode -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">{{ __('messages.access_control_mode') }}</label>
                                <div class="mt-2 space-y-4">
                                    <div class="flex items-center">
                                        <input type="radio" id="mode_blacklist" name="access_control_mode" value="blacklist"
                                               {{ $settings['mode'] === 'blacklist' ? 'checked' : '' }}
                                               class="h-4 w-4 text-[var(--brand-color)] focus:ring-[var(--brand-color)]">
                                        <label for="mode_blacklist" class="ml-2 block text-sm text-gray-900">
                                            {{ __('messages.blacklist_mode') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="mode_whitelist" name="access_control_mode" value="whitelist"
                                               {{ $settings['mode'] === 'whitelist' ? 'checked' : '' }}
                                               class="h-4 w-4 text-[var(--brand-color)] focus:ring-[var(--brand-color)]">
                                        <label for="mode_whitelist" class="ml-2 block text-sm text-gray-900">
                                            {{ __('messages.whitelist_mode') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Domain Rules -->
                            <div class="mt-6">
                                <label for="domain_rules" class="block text-sm font-medium text-gray-700">
                                    {{ __('messages.domain_rules') }}
                                </label>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ __('messages.domain_rules_hint') }}
                                </p>
                                <textarea id="domain_rules" name="domain_rules" rows="5"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                                 focus:border-[var(--brand-color)] focus:ring-[var(--brand-color)] sm:text-sm"
                                          placeholder="*.example.com&#10;user@domain.com&#10;*.co.uk">{{ implode("\n", $settings['rules'] ?? []) }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('domain_rules')" />
                                <x-input-error class="mt-2" :messages="$errors->get('access_control_mode')" />
                            </div>

                            <div class="flex items-center gap-4 mt-4">
                                <x-primary-button>{{ __('messages.save_button') }}</x-primary-button>

                                @if (session('status') && str_contains(session('status'), 'access control'))
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 4000)"
                                        class="text-sm font-medium text-green-600"
                                    >{{ session('status') }}</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>