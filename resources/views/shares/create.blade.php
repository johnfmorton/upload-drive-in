<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl text-warm-900 leading-tight">
            {{ __('messages.shares_send_files') }}
        </h2>
    </x-slot>

    @php
        $strings = [
            'drop' => __('messages.shares_drop_files'),
            'remove' => __('messages.shares_remove_file'),
            'cancel' => __('messages.shares_cancel_upload'),
            'fileTooLarge' => __('messages.shares_file_too_large', ['max' => format_bytes($maxFileSize)]),
            'shareTooLarge' => __('messages.shares_share_too_large', ['max' => format_bytes($maxShareSize)]),
            'summary' => __('messages.shares_selected_summary'),
            'progress' => __('messages.shares_upload_progress'),
            'uploading' => __('messages.shares_uploading'),
            'retry' => __('messages.shares_retry_failed'),
            'someFailed' => __('messages.shares_some_failed'),
            'addFiles' => __('messages.shares_add_a_file'),
            'uploadFailed' => __('messages.shares_upload_failed'),
        ];
    @endphp

    <div class="py-12">
        <div id="share-create"
             class="max-w-3xl mx-auto sm:px-6 lg:px-8"
             data-max-file-size="{{ $maxFileSize }}"
             data-max-share-size="{{ $maxShareSize }}"
             data-strings="{{ json_encode($strings) }}">

            <form method="POST" action="{{ route($routePrefix . '.store', $routeParams) }}" class="bg-white shadow sm:rounded-lg p-6 sm:p-8 space-y-6" novalidate>
                <div>
                    <x-input-label for="share-title" :value="__('messages.shares_field_title')" />
                    <x-text-input id="share-title" name="title" type="text" class="mt-1 block w-full" maxlength="120" required autofocus
                                  placeholder="{{ __('messages.shares_field_title_placeholder') }}" />
                </div>

                <div>
                    <x-input-label :value="__('messages.shares_field_files')" />
                    <div data-dropzone class="dropzone mt-1 border-2! border-dashed! border-cream-300! rounded-xl! bg-cream-50! hover:border-accent-500! transition-colors"></div>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ __('messages.shares_limits_hint', ['file' => format_bytes($maxFileSize), 'share' => format_bytes($maxShareSize)]) }}
                        <span data-summary class="font-medium text-warm-700"></span>
                    </p>
                </div>

                <div>
                    <x-input-label for="share-message" :value="__('messages.shares_field_message')" />
                    <textarea id="share-message" name="message" rows="3" maxlength="2000"
                              class="mt-1 block w-full border-cream-300 focus:border-accent-500 focus:ring-accent-500 rounded-xl shadow-sm"></textarea>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="share-expiry" :value="__('messages.shares_field_expiry')" />
                        <select id="share-expiry" name="expiry_days"
                                class="mt-1 block w-full border-cream-300 focus:border-accent-500 focus:ring-accent-500 rounded-xl shadow-sm">
                            @foreach ($expiryChoices as $days)
                                <option value="{{ $days }}" @selected($days === $defaultExpiry)>{{ trans_choice('messages.shares_expiry_days', $days, ['count' => $days]) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="share-password" :value="__('messages.shares_field_password')" />
                        <x-text-input id="share-password" name="password" type="password" class="mt-1 block w-full" minlength="6" autocomplete="new-password" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('messages.shares_field_password_hint') }}</p>
                    </div>
                </div>

                <div>
                    <x-input-label for="share-recipients" :value="__('messages.shares_field_recipients')" />
                    <textarea id="share-recipients" name="recipient_emails" rows="2"
                              class="mt-1 block w-full border-cream-300 focus:border-accent-500 focus:ring-accent-500 rounded-xl shadow-sm"
                              placeholder="name@example.com, other@example.com"></textarea>
                    <p class="mt-1 text-xs text-gray-500">{{ __('messages.shares_field_recipients_hint') }}</p>
                </div>

                <p data-error hidden role="alert" class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm"></p>

                <div data-progress hidden>
                    <div class="h-2 bg-cream-200 rounded-full overflow-hidden">
                        <div data-progress-bar class="h-full bg-accent-500 transition-[width] duration-300" style="width: 0"></div>
                    </div>
                    <p data-progress-text class="mt-1 text-xs text-gray-500" aria-live="polite"></p>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route($routePrefix . '.index', $routeParams) }}" class="text-sm text-gray-600 hover:text-warm-900">{{ __('messages.cancel') }}</a>
                    <x-primary-button data-submit>{{ __('messages.shares_upload_and_create') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/share-upload.js')
    @endpush
</x-app-layout>
