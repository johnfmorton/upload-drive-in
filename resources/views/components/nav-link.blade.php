@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-warm-900 text-sm font-semibold leading-5 text-warm-900 focus:outline-none transition duration-200 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-warm-500 hover:text-warm-800 hover:border-warm-300 focus:outline-none focus:text-warm-800 focus:border-warm-300 transition duration-200 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
