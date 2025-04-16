@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-[var(--brand-color)] dark:focus:border-[var(--brand-color)] focus:ring-[var(--brand-color)] dark:focus:ring-[var(--brand-color)] rounded-md shadow-sm']) !!}>
