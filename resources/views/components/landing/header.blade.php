@php
    $section = $landing->section('header');
@endphp

@if ($section)
<header class="landing-header">
    @php
        $extra = $section?->extra ?? [];
        $navLinks = $landing->blocks('header', 'nav_link');
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
            <a class="landing-header__login" href="{{ url('/admin/login') }}">Войти</a>
            <button type="button" class="btn btn--primary btn--sm">{{ $extra['demo_button_text'] ?? 'Получить демо' }}</button>
        </div>
    </div>
</header>
@endif
