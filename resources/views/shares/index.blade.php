<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-display text-2xl text-warm-900 leading-tight">
                {{ __('messages.shares_title') }}
            </h2>
            <a href="{{ route($routePrefix . '.create', $routeParams) }}"
               class="inline-flex items-center px-5 py-2.5 bg-warm-900 border border-transparent rounded-full font-semibold text-sm text-white tracking-wide hover:bg-warm-800 focus:outline-none focus:ring-2 focus:ring-warm-500 focus:ring-offset-2 focus:ring-offset-cream-100 transition ease-in-out duration-200">
                {{ __('messages.shares_send_files') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                @if ($shares->isEmpty())
                    <div class="p-12 text-center">
                        <p class="text-warm-700">{{ __('messages.shares_empty') }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ __('messages.shares_empty_hint') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-cream-200 text-sm">
                            <thead class="bg-cream-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-3">{{ __('messages.shares_col_title') }}</th>
                                    @if ($showSender)
                                        <th class="px-6 py-3">{{ __('messages.shares_col_sender') }}</th>
                                    @endif
                                    <th class="px-6 py-3">{{ __('messages.shares_col_status') }}</th>
                                    <th class="px-6 py-3">{{ __('messages.shares_col_files') }}</th>
                                    <th class="px-6 py-3">{{ __('messages.shares_col_downloads') }}</th>
                                    <th class="px-6 py-3">{{ __('messages.shares_col_expires') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-cream-200">
                                @foreach ($shares as $share)
                                    <tr class="hover:bg-cream-50">
                                        <td class="px-6 py-4">
                                            <a href="{{ route($routePrefix . '.show', $routeParams + ['share' => $share]) }}" class="font-medium text-warm-900 hover:underline">
                                                {{ $share->title }}
                                            </a>
                                            <div class="text-xs text-gray-500">{{ $share->created_at->diffForHumans() }}</div>
                                        </td>
                                        @if ($showSender)
                                            <td class="px-6 py-4 text-gray-600">{{ $share->user?->name }}</td>
                                        @endif
                                        <td class="px-6 py-4">@include('shares._status', ['status' => $share->status])</td>
                                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                            {{ $share->files_count }} · {{ format_bytes((int) $share->files_sum_file_size) }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">{{ $share->download_count }}</td>
                                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                            @if ($share->isEnded())
                                                —
                                            @else
                                                <time datetime="{{ $share->expires_at->toIso8601String() }}" title="{{ $share->expires_at->toDayDateTimeString() }}">{{ $share->expires_at->diffForHumans() }}</time>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4">{{ $shares->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
