<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-red-600 border border-transparent rounded-full font-semibold text-sm text-white tracking-wide hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-cream-100 transition ease-in-out duration-200']) }}>
    {{ $slot }}
</button>
