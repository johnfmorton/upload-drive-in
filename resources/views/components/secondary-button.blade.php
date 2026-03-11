<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-5 py-2.5 bg-white border border-cream-300 rounded-full font-semibold text-sm text-warm-700 tracking-wide shadow-sm hover:bg-cream-50 hover:border-cream-400 focus:outline-none focus:ring-2 focus:ring-warm-500 focus:ring-offset-2 focus:ring-offset-cream-100 disabled:opacity-25 transition ease-in-out duration-200']) }}>
    {{ $slot }}
</button>
