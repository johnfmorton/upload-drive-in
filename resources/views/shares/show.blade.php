@php
    $hasFailedFiles = $share->files->contains('status', 'failed');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <h2 class="font-display text-2xl text-warm-900 leading-tight truncate">{{ $share->title }}</h2>
                @include('shares._status', ['status' => $share->status])
            </div>
            <a href="{{ route($routePrefix . '.index', $routeParams) }}" class="text-sm text-gray-600 hover:text-warm-900">&larr; {{ __('messages.shares_title') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl">{{ session('status') }}</div>
            @endif

            {{-- Link, or why there isn't a working one --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                @if ($share->isAvailable())
                    <div x-data="{ copied: false }">
                        <x-input-label for="share-link" :value="__('messages.shares_link')" />
                        <div class="mt-1 flex gap-2">
                            <input id="share-link" type="text" readonly value="{{ $share->publicUrl() }}"
                                   class="flex-1 min-w-0 border-cream-300 bg-cream-50 rounded-xl text-sm font-mono" x-ref="link" @focus="$el.select()">
                            <x-secondary-button type="button"
                                                @click="navigator.clipboard.writeText($refs.link.value).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                                <span x-show="!copied">{{ __('messages.shares_copy_link') }}</span>
                                <span x-show="copied" x-cloak>{{ __('messages.shares_copied') }}</span>
                            </x-secondary-button>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            {{ __('messages.shares_expires_on', ['date' => $share->expires_at->toDayDateTimeString()]) }}
                            @if ($share->hasPassword())
                                · {{ __('messages.shares_password_protected') }}
                            @endif
                        </p>
                    </div>
                @elseif ($share->status === 'draft')
                    <p class="text-warm-700">{{ __('messages.shares_draft_unfinished') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('messages.shares_draft_unfinished_hint') }}</p>
                @elseif ($hasFailedFiles)
                    <p class="text-red-800">{{ __('messages.shares_files_failed') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('messages.shares_files_failed_hint') }}</p>
                @elseif ($share->status === 'uploading')
                    <div x-data="shareStatusPoller('{{ route($routePrefix . '.status', $routeParams + ['share' => $share]) }}')">
                        <p class="text-warm-700">{{ __('messages.shares_still_uploading') }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ __('messages.shares_still_uploading_hint') }}</p>
                    </div>
                @else
                    <p class="text-warm-700">{{ __('messages.shares_ended_' . $share->status) }}</p>
                    @if ($share->purged_at)
                        <p class="mt-1 text-sm text-gray-500">{{ __('messages.shares_files_deleted_on', ['date' => $share->purged_at->toDayDateTimeString()]) }}</p>
                    @endif
                @endif
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Files --}}
                <div class="lg:col-span-2 lg:self-start bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-cream-200 flex items-baseline justify-between">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('messages.shares_col_files') }}</h3>
                        <span class="text-sm text-gray-500">{{ $share->files->count() }} · {{ format_bytes((int) $share->files->sum('file_size')) }}</span>
                    </div>
                    <ul class="divide-y divide-cream-200">
                        @foreach ($share->files as $file)
                            <li class="px-6 py-3 flex items-center justify-between gap-4 text-sm">
                                <div class="min-w-0">
                                    <p class="truncate text-warm-900" title="{{ $file->original_filename }}">{{ $file->original_filename }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ format_bytes($file->file_size) }}
                                        @if ($file->download_count)
                                            · {{ trans_choice('messages.shares_download_count', $file->download_count, ['count' => $file->download_count]) }}
                                        @endif
                                    </p>
                                    @if ($file->status === 'failed' && $file->last_error)
                                        <p class="text-xs text-red-700 mt-1">{{ Str::limit($file->last_error, 200) }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0 flex items-center gap-3">
                                    @if ($file->status === 'failed' && ! $share->isEnded())
                                        <form method="POST" action="{{ route($routePrefix . '.files.retry', $routeParams + ['share' => $share, 'shareFile' => $file]) }}">
                                            @csrf
                                            <button class="text-sm text-accent-600 hover:underline">{{ __('messages.shares_retry') }}</button>
                                        </form>
                                    @endif
                                    @if (! $share->isEnded())
                                        @include('shares._file_status', ['status' => $file->status])
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Details --}}
                <div class="space-y-6">
                    <div class="bg-white shadow sm:rounded-lg p-6 text-sm space-y-3">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('messages.shares_details') }}</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-gray-500">{{ __('messages.shares_col_sender') }}</dt>
                                <dd class="text-warm-900">{{ $share->user?->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('messages.shares_created') }}</dt>
                                <dd class="text-warm-900">{{ $share->created_at->toDayDateTimeString() }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('messages.shares_col_downloads') }}</dt>
                                <dd class="text-warm-900">
                                    {{ $share->download_count }}
                                    @if ($share->first_downloaded_at)
                                        <span class="text-gray-500">({{ __('messages.shares_first_download', ['date' => $share->first_downloaded_at->diffForHumans()]) }})</span>
                                    @endif
                                </dd>
                            </div>
                            @if ($share->recipient_emails)
                                <div>
                                    <dt class="text-gray-500">{{ __('messages.shares_field_recipients') }}</dt>
                                    <dd class="text-warm-900 break-words">{{ implode(', ', $share->recipient_emails) }}</dd>
                                </div>
                            @endif
                            @if ($share->message)
                                <div>
                                    <dt class="text-gray-500">{{ __('messages.shares_field_message') }}</dt>
                                    <dd class="text-warm-900 whitespace-pre-line">{{ $share->message }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    @unless ($share->isEnded())
                        <div x-data="{ confirming: false }" class="bg-white shadow sm:rounded-lg p-6 text-sm">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('messages.shares_revoke') }}</h3>
                            <p class="mt-1 text-gray-500">{{ __('messages.shares_revoke_hint') }}</p>
                            <x-danger-button type="button" class="mt-4" x-show="!confirming" @click="confirming = true">
                                {{ __('messages.shares_revoke') }}
                            </x-danger-button>
                            <form x-show="confirming" x-cloak method="POST" action="{{ route($routePrefix . '.revoke', $routeParams + ['share' => $share]) }}" class="mt-4 space-y-3">
                                @csrf
                                <p class="font-medium text-red-800">{{ __('messages.shares_revoke_confirm') }}</p>
                                <div class="flex gap-3">
                                    <x-danger-button>{{ __('messages.shares_revoke_confirm_button') }}</x-danger-button>
                                    <x-secondary-button type="button" @click="confirming = false">{{ __('messages.cancel') }}</x-secondary-button>
                                </div>
                            </form>
                        </div>
                    @endunless
                </div>
            </div>

            {{-- Recent activity --}}
            @if ($recentAccess->isNotEmpty())
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <h3 class="px-6 py-4 border-b border-cream-200 text-lg font-medium text-gray-900">{{ __('messages.shares_activity') }}</h3>
                    <ul class="divide-y divide-cream-200 text-sm">
                        @foreach ($recentAccess as $entry)
                            <li class="px-6 py-3 flex justify-between gap-4">
                                <span class="text-warm-900">
                                    {{ __('messages.shares_event_' . $entry->event, ['file' => $entry->shareFile?->original_filename ?? '']) }}
                                </span>
                                <span class="text-gray-500 whitespace-nowrap" title="{{ $entry->ip }}">{{ $entry->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    @if ($share->status === 'uploading' && ! $hasFailedFiles)
        @push('scripts')
            <script @cspNonce>
                // Reload once the files reach cloud storage and the link is ready
                document.addEventListener('alpine:init', () => {
                    Alpine.data('shareStatusPoller', (url) => ({
                        init() {
                            this.timer = setInterval(async () => {
                                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                                if (!response.ok) return;
                                const { status, files } = await response.json();
                                const anyFailed = Object.values(files).includes('failed');
                                if (status !== 'draft' && status !== 'uploading' || anyFailed) {
                                    clearInterval(this.timer);
                                    window.location.reload();
                                }
                            }, 5000);
                        },
                        destroy() {
                            clearInterval(this.timer);
                        },
                    }));
                });
            </script>
        @endpush
    @endif
</x-app-layout>
