@php
    $section = $landing->section('header');
@endphp

@if ($section)
<header class="landing-header">
    @php
        $navLinks = $landing->blocks('header', 'nav_link');
        $headerButtons = $landing->blocks('header', 'header_button');
        $heroSection = $landing->section('hero');
        $siteSettingsService = app(\App\Services\SiteSettingsService::class);
        $siteSettings = $siteSettingsService->get();
        $cabinetLoginEnabled = $siteSettingsService->cabinetLoginConfigured();
        $cabinetRegistrationEnabled = $siteSettingsService->cabinetRegistrationConfigured();
        $cabinetLoginButtonClass = match ($siteSettings->cabinet_login_button_style) {
            'primary' => 'btn btn--primary btn--sm cabinet-login-trigger',
            'link' => 'landing-header__login cabinet-login-trigger',
            default => 'btn btn--ghost btn--sm cabinet-login-trigger',
        };
        $cabinetRegistrationButtonClass = match ($siteSettings->cabinet_registration_button_style) {
            'ghost' => 'btn btn--ghost btn--sm cabinet-login-trigger',
            'link' => 'landing-header__login cabinet-login-trigger',
            default => 'btn btn--primary btn--sm cabinet-login-trigger',
        };
        $cabinetAuthHeaderEnabled = $cabinetLoginEnabled || $cabinetRegistrationEnabled;
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
            @unless ($cabinetAuthHeaderEnabled)
                @foreach ($headerButtons as $button)
                    @if ($button->button_style === 'primary')
                        <a class="btn btn--primary btn--sm" href="{{ \App\Support\LandingLinks::resolve($button->link) }}">{{ $button->title }}</a>
                    @else
                        <a class="landing-header__login" href="{{ \App\Support\LandingLinks::resolve($button->link) }}">{{ $button->title }}</a>
                    @endif
                @endforeach
            @endunless

            @if ($cabinetRegistrationEnabled)
                <button class="{{ $cabinetRegistrationButtonClass }}" type="button" data-cabinet-registration-open>
                    {{ $siteSettings->cabinet_registration_button_text ?: 'Создать личный кабинет' }}
                </button>
            @endif

            @if ($cabinetLoginEnabled)
                <button class="{{ $cabinetLoginButtonClass }}" type="button" data-cabinet-login-open>
                    {{ $siteSettings->cabinet_login_button_text ?: 'Войти в личный кабинет' }}
                </button>
            @endif

            @if ($navLinks->isNotEmpty() || $cabinetLoginEnabled || $cabinetRegistrationEnabled)
                <details class="landing-mobile-menu">
                    <summary class="landing-mobile-menu__toggle" aria-label="Открыть меню">
                        <span class="landing-mobile-menu__icon" aria-hidden="true"></span>
                    </summary>

                    <nav class="landing-mobile-menu__panel" aria-label="Мобильная навигация">
                        @foreach ($navLinks as $link)
                            <a href="{{ \App\Support\LandingLinks::resolve($link->link) }}">{{ $link->title }}</a>
                        @endforeach
                        @if ($cabinetRegistrationEnabled)
                            <button type="button" data-cabinet-registration-open>{{ $siteSettings->cabinet_registration_button_text ?: 'Создать личный кабинет' }}</button>
                        @endif
                        @if ($cabinetLoginEnabled)
                            <button type="button" data-cabinet-login-open>{{ $siteSettings->cabinet_login_button_text ?: 'Войти в личный кабинет' }}</button>
                        @endif
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
