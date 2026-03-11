<button
    {{ $attributes->class([
        'inline-flex items-center px-5 py-2.5 bg-warm-900 border border-transparent rounded-full font-semibold text-sm text-white tracking-wide hover:bg-warm-800 focus:bg-warm-800 active:bg-warm-700 focus:outline-none focus:ring-2 focus:ring-warm-500 focus:ring-offset-2 focus:ring-offset-cream-100 transition ease-in-out duration-200',
        $attributes->get('class'),
    ]) }}
>
    {{ $slot }}
</button>
