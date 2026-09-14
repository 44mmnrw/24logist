@props(['name', 'size' => 18, 'filled' => false])

{{-- Tabler Icons v3.46.0 (MIT); see public/icons/tabler/v3.46.0/LICENSE.txt. --}}
<svg {{ $attributes->class(['community-icon']) }} xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <use href="{{ '/icons/tabler/v3.46.0/tabler-sprite'.($filled ? '-filled' : '').'.svg#tabler-'.($filled ? 'filled-' : '').$name }}" />
</svg>
