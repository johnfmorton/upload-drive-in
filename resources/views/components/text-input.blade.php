@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-cream-300 bg-white text-warm-900 placeholder-warm-400 focus:border-warm-500 focus:ring-warm-500 rounded-xl shadow-sm transition duration-200']) !!}>
