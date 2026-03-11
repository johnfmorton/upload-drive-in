@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-warm-700']) }}>
    {{ $value ?? $slot }}
</label>
