@props(['name', 'size' => 18])

{{-- SVG paths from Tabler Icons (MIT); see TABLER-LICENSE.txt in this directory. --}}
<svg {{ $attributes->class(['community-icon']) }} xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('arrow-up')
            <path d="M12 5l0 14" />
            <path d="M18 11l-6 -6" />
            <path d="M6 11l6 -6" />
            @break
        @case('arrow-down')
            <path d="M12 5l0 14" />
            <path d="M18 13l-6 6" />
            <path d="M6 13l6 6" />
            @break
        @case('arrow-left')
            <path d="M5 12l14 0" />
            <path d="M5 12l6 6" />
            <path d="M5 12l6 -6" />
            @break
        @case('external-link')
            <path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" />
            <path d="M11 13l9 -9" />
            <path d="M15 4h5v5" />
            @break
        @case('message-circle')
            <path d="M3 20l1.3 -3.9c-2.324 -3.437 -1.426 -7.872 2.1 -10.374c3.526 -2.501 8.59 -2.296 11.845 .48c3.255 2.777 3.695 7.266 1.029 10.501c-2.666 3.235 -7.615 4.215 -11.574 2.293l-4.7 1" />
            @break
        @case('share-3')
            <path d="M13 4v4c-6.575 1.028 -9.02 6.788 -10 12c-.037 .206 5.384 -5.962 10 -6v4l8 -7l-8 -7" />
            @break
        @case('flame')
            <path d="M12 10.941c2.333 -3.308 .167 -7.823 -1 -8.941c0 3.395 -2.235 5.299 -3.667 6.706c-1.43 1.408 -2.333 3.294 -2.333 5.588c0 3.704 3.134 6.706 7 6.706c3.866 0 7 -3.002 7 -6.706c0 -1.712 -1.232 -4.403 -2.333 -5.588c-2.084 3.353 -3.257 3.353 -4.667 2.235" />
            @break
        @case('sparkles')
            <path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2m0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2m-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6" />
            @break
        @case('x')
            <path d="M18 6l-12 12" />
            <path d="M6 6l12 12" />
            @break
        @case('trophy')
            <path d="M8 21l8 0" />
            <path d="M12 17l0 4" />
            <path d="M7 4l10 0" />
            <path d="M17 4v8a5 5 0 0 1 -10 0v-8" />
            <path d="M3 9a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
            <path d="M17 9a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
            @break
    @endswitch
</svg>
