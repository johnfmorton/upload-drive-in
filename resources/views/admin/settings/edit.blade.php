<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.app_settings') }} {{-- Use lang file --}}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('messages.branding_settings_title') }} {{-- Use lang file --}}
                            </h2>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('messages.branding_settings_description') }} {{-- Use lang file --}}
                            </p>
                        </header>

                        {{-- Form will go here --}}
                        <form method="post" action="{{ route('admin.settings.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
                            @csrf
                            @method('put')

                            {{-- Company Name --}}
                            <div>
                                <x-input-label for="company_name" :value="__('messages.company_name_label')" />
                                <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', $settings['company_name'])" required autofocus autocomplete="organization" />
                                <x-input-error class="mt-2" :messages="$errors->get('company_name')" />
                            </div>

                            {{-- Branding Color --}}
                            <div>
                                <x-input-label for="branding_color" :value="__('messages.branding_color_label')" />
                                <sl-color-picker class="mt-2 block" label="Select a color" id="branding_color" name="branding_color" value="{{ old('branding_color', $settings['branding_color']) }}"></sl-color-picker>
                                <x-input-error class="mt-2" :messages="$errors->get('branding_color')" />
                            </div>

                            {{-- Application Icon --}}
                            <div>
                                <x-input-label for="app_icon" :value="__('messages.app_icon_label')" />
                                @if($settings['has_icon'])
                                    <div class="mt-2 mb-2 flex items-center gap-4">
                                        <img src="{{ asset('images/app-icon.png') }}?v={{ md5_file(public_path('images/app-icon.png')) }}" alt="Current App Icon" class="w-16 h-16 object-contain block">
                                    </div>
                                @endif
                                <input id="app_icon" name="app_icon" type="file" class="mt-1 block w-full" accept="image/png, image/jpeg, image/svg+xml" />
                                <x-input-error class="mt-2" :messages="$errors->get('app_icon')" />
                                <p class="mt-1 text-sm text-gray-600">{{ __('messages.app_icon_hint') }}</p>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('messages.save_button') }}</x-primary-button>

                                @if (session('status') === 'settings-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600"
                                    >{{ __('messages.saved_confirmation') }}</p>
                                @endif
                            </div>
                        </form>

                        @if($settings['has_icon'])
                            {{-- Remove Logo Form (separate from main settings form) --}}
                            <form method="post" action="{{ route('admin.settings.icon.destroy') }}" class="mt-4">
                                @csrf
                                @method('delete')
                                <x-danger-button type="submit">{{ __('messages.remove_logo_button') }}</x-danger-button>
                            </form>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
