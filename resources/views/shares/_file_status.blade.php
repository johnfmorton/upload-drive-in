@php
    $styles = [
        'pending' => 'text-amber-700',
        'uploaded' => 'text-emerald-700',
        'failed' => 'text-red-700',
    ];
@endphp
<span class="text-xs font-medium {{ $styles[$status] ?? 'text-gray-500' }}">{{ __('messages.shares_file_status_' . $status) }}</span>
