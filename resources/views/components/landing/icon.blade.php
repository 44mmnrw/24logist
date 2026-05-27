@props([
    'name' => null,
    'class' => '',
])

@php
    $icon = \App\Support\LandingIcons::resolve($name);
@endphp

@if ($icon)
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="{{ \App\Support\LandingIcons::viewBox($icon) }}"
        fill="none"
        aria-hidden="true"
        focusable="false"
        {{ $attributes->class(['landing-icon', $class]) }}
    >
        <use href="{{ \App\Support\LandingIcons::symbolHref($icon) }}" xlink:href="{{ \App\Support\LandingIcons::symbolHref($icon) }}" />
    </svg>
@endif
