@props([
    'variant' => 'default',
])

<img
    src="{{ asset('images/logo.svg') }}"
    alt="ЛогистРу"
    width="132"
    height="32"
    {{ $attributes->class(['brand-logo', 'brand-logo--' . $variant]) }}
/>
