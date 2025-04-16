@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[var(--brand-color)] dark:border-[var(--brand-color)]/70 text-start text-base font-medium text-[var(--brand-color)] dark:text-[var(--brand-color)]/70 bg-[var(--brand-color)]/10 dark:bg-[var(--brand-color)]/20 focus:outline-none focus:text-[var(--brand-color)]/80 dark:focus:text-[var(--brand-color)]/60 focus:bg-[var(--brand-color)]/20 dark:focus:bg-[var(--brand-color)]/30 focus:border-[var(--brand-color)]/80 dark:focus:border-[var(--brand-color)]/60 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:text-gray-800 dark:focus:text-gray-200 focus:bg-gray-50 dark:focus:bg-gray-700 focus:border-gray-300 dark:focus:border-gray-600 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
