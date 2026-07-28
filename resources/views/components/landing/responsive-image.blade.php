@props([
    'path',
    'alt' => '',
    'width',
    'height',
    'loading' => 'lazy',
    'decoding' => 'async',
    'fetchpriority' => null,
    'sizes' => null,
    'class' => null,
    'pictureClass' => null,
])

@php
    $image = \App\Support\ImageVariants::data($path);
@endphp

@if ($image['url'])
    <picture @if($pictureClass) class="{{ $pictureClass }}" @endif>
        @if ($image['avif_srcset'])
            <source
                type="image/avif"
                srcset="{{ $image['avif_srcset'] }}"
                @if($sizes) sizes="{{ $sizes }}" @endif
            >
        @endif
        @if ($image['webp_srcset'])
            <source
                type="image/webp"
                srcset="{{ $image['webp_srcset'] }}"
                @if($sizes) sizes="{{ $sizes }}" @endif
            >
        @endif
        <img
            src="{{ $image['url'] }}"
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            loading="{{ $loading }}"
            decoding="{{ $decoding }}"
            @if($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
            @if($sizes) sizes="{{ $sizes }}" @endif
            @if($class) class="{{ $class }}" @endif
        >
    </picture>
@endif
