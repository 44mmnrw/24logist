@props([
    'variant' => 'default',
])

<img
    src="{{ app(\App\Services\SiteSettingsService::class)->logoUrl() }}"
    alt="логистРу"
    width="132"
    height="32"
    {{ $attributes->class(['brand-logo', 'brand-logo--' . $variant]) }}
/>
