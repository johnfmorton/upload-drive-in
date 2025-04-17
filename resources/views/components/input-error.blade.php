@props(['messages' => null, 'for' => null])

@php
    if ($for) {
        $messages = $errors->get($for);
    }
@endphp

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
