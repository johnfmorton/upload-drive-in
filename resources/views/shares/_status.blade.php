@php
    $styles = [
        'draft' => 'bg-cream-100 text-warm-700 border-cream-300',
        'uploading' => 'bg-amber-50 text-amber-800 border-amber-200',
        'ready' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'expired' => 'bg-cream-100 text-warm-600 border-cream-300',
        'revoked' => 'bg-red-50 text-red-800 border-red-200',
        'failed' => 'bg-red-50 text-red-800 border-red-200',
    ];
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $styles[$status] ?? $styles['draft'] }}">
    {{ __('messages.shares_status_' . $status) }}
</span>
