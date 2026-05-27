{{-- Inline SVG sprite (source: public/images/icons/sprite.svg) --}}
@php
    $sprite = file_get_contents(public_path('images/icons/sprite.svg'));
    $sprite = str_replace(
        '<svg xmlns="http://www.w3.org/2000/svg" style="display:none">',
        '<svg xmlns="http://www.w3.org/2000/svg" class="landing-icon-sprite" aria-hidden="true" focusable="false">',
        $sprite,
    );
@endphp

{!! $sprite !!}
