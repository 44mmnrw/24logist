@php
    $section = $landing->section('header');
@endphp

@if ($section)
<header class="landing-header">
    @php
        $navLinks = $landing->blocks('header', 'nav_link');
        $headerButtons = $landing->blocks('header', 'header_button');
    @endphp

    <div class="landing-shell landing-header__shell">
        <a class="brand" href="{{ \App\Support\LandingLinks::resolve('#hero') }}">
            <x-landing.logo />
        </a>

        <nav class="landing-nav">
            @foreach ($navLinks as $link)
                <a href="{{ \App\Support\LandingLinks::resolve($link->link) }}">{{ $link->title }}</a>
            @endforeach
        </nav>

        <div class="landing-header__actions">
            @foreach ($headerButtons as $button)
                @if ($button->button_style === 'primary')
                    <a class="btn btn--primary btn--sm" href="{{ \App\Support\LandingLinks::resolve($button->link) }}">{{ $button->title }}</a>
                @else
                    <a class="landing-header__login" href="{{ \App\Support\LandingLinks::resolve($button->link) }}">{{ $button->title }}</a>
                @endif
            @endforeach
        </div>
    </div>
</header>
@endif
