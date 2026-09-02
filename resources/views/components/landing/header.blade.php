@php
    $section = $landing->section('header');
@endphp

@if ($section)
<header class="landing-header">
    @php
        $navLinks = $landing->blocks('header', 'nav_link');
        $headerButtons = $landing->blocks('header', 'header_button');
        $heroSection = $landing->section('hero');
    @endphp

    <div class="landing-shell landing-header__shell">
        <a class="brand" href="{{ \App\Support\LandingLinks::resolve($heroSection?->anchorLink() ?? '#hero') }}">
            <x-landing.logo />
        </a>

        <nav class="landing-nav">
            @foreach ($navLinks as $link)
                <a href="{{ \App\Support\LandingLinks::resolve($link->link) }}">{{ $link->title }}</a>
            @endforeach
            @if (app(\App\Services\SiteSettingsService::class)->routeApiConfigured())
                <a href="{{ route('route-calculator.index') }}">Калькулятор маршрута</a>
            @endif
            {{--
            @if (app(\App\Services\SiteSettingsService::class)->communityEnabled())
                <a href="{{ route('community.index') }}">Сообщество</a>
            @endif
            --}}
        </nav>

        <div class="landing-header__actions">
            @foreach ($headerButtons as $button)
                @if ($button->button_style === 'primary')
                    <a class="btn btn--primary btn--sm" href="{{ \App\Support\LandingLinks::resolve($button->link) }}">{{ $button->title }}</a>
                @else
                    <a class="landing-header__login" href="{{ \App\Support\LandingLinks::resolve($button->link) }}">{{ $button->title }}</a>
                @endif
            @endforeach

            @if ($navLinks->isNotEmpty())
                <details class="landing-mobile-menu">
                    <summary class="landing-mobile-menu__toggle" aria-label="Открыть меню">
                        <span class="landing-mobile-menu__icon" aria-hidden="true"></span>
                    </summary>

                    <nav class="landing-mobile-menu__panel" aria-label="Мобильная навигация">
                        @foreach ($navLinks as $link)
                            <a href="{{ \App\Support\LandingLinks::resolve($link->link) }}">{{ $link->title }}</a>
                        @endforeach
                        @if (app(\App\Services\SiteSettingsService::class)->routeApiConfigured())
                            <a href="{{ route('route-calculator.index') }}">Калькулятор маршрута</a>
                        @endif
                        {{--
                        @if (app(\App\Services\SiteSettingsService::class)->communityEnabled())
                            <a href="{{ route('community.index') }}">Сообщество</a>
                        @endif
                        --}}
                    </nav>
                </details>
            @endif
        </div>
    </div>
</header>
@endif
