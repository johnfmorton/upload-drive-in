<x-share-public-layout :title="$share->title">
    <div class="bg-white border border-cream-200 rounded-3xl shadow-[0_2px_8px_rgba(28,25,23,0.04),0_12px_40px_rgba(28,25,23,0.06)] overflow-hidden">
        <div class="px-6 sm:px-8 pt-8 pb-6">
            <p class="text-sm text-gray-500">{{ __('messages.shares_public_sent_by', ['name' => $share->user?->name ?? config('app.name')]) }}</p>
            <h1 class="mt-1 font-display text-3xl leading-tight">{{ $share->title }}</h1>
            @if ($share->message)
                <p class="mt-4 text-warm-700 whitespace-pre-line">{{ $share->message }}</p>
            @endif
            <p class="mt-4 text-sm text-gray-500">
                {{ trans_choice('messages.shares_public_file_count', $files->total(), ['count' => $files->total()]) }}
                · {{ format_bytes($totalSize) }}
                · {{ __('messages.shares_public_available_until', ['date' => $share->expires_at->toFormattedDateString()]) }}
            </p>
        </div>

        <ul class="border-t border-cream-200 divide-y divide-cream-200">
            @foreach ($files as $file)
                <li class="px-6 sm:px-8 py-3 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="truncate" title="{{ $file->original_filename }}">{{ $file->original_filename }}</p>
                        <p class="text-xs text-gray-500">{{ format_bytes($file->file_size) }}</p>
                    </div>
                    <a href="{{ route('shares.public.download', ['token' => $token, 'fileId' => $file->id]) }}"
                       rel="nofollow"
                       class="shrink-0 inline-flex items-center px-4 py-2 bg-warm-900 rounded-full font-semibold text-sm text-white hover:bg-warm-800 focus:outline-none focus:ring-2 focus:ring-warm-500 focus:ring-offset-2 transition">
                        {{ __('messages.shares_public_download') }}
                        <span class="sr-only">{{ $file->original_filename }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($files->hasPages())
            <div class="px-6 sm:px-8 py-4 border-t border-cream-200">{{ $files->links() }}</div>
        @endif
    </div>
</x-share-public-layout>
