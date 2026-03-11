@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-accent-500 text-start text-base font-medium text-warm-900 bg-accent-500/5 focus:outline-none transition duration-200 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-warm-600 hover:text-warm-800 hover:bg-cream-100 hover:border-cream-400 focus:outline-none focus:text-warm-800 focus:bg-cream-100 focus:border-cream-400 transition duration-200 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
