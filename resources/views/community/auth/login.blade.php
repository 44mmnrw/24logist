@extends('community.layout')
@section('title', 'Вход — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-auth-shell">
    <div class="community-auth-card">
        <span class="section-kicker">Без пароля</span>
        <h1>Войти в сообщество</h1>
        <p>Выберите удобный способ входа. Публично будет виден только псевдоним, который вы зададите после входа.</p>
        <div class="community-auth-buttons">
            <a class="community-provider community-provider--telegram" href="{{ route('community.auth.telegram.redirect') }}">Войти через Telegram</a>
            @if (app(\App\Services\SiteSettingsService::class)->communityVkEnabled())
                <a class="community-provider community-provider--vk" href="{{ route('community.auth.vk.redirect') }}">Войти через VK ID</a>
            @else
                <span class="community-provider is-disabled">VK ID — скоро</span>
            @endif
            @if (app(\App\Services\SiteSettingsService::class)->communityMaxEnabled())
                <a class="community-provider community-provider--max" href="{{ route('community.auth.max.start') }}">Войти через MAX</a>
            @else
                <span class="community-provider is-disabled">MAX — скоро</span>
            @endif
        </div>
        <small>Продолжая, вы соглашаетесь с <a href="{{ route('community.rules') }}">правилами сообщества</a> и <a href="{{ route('community.privacy') }}">политикой конфиденциальности</a>.</small>
    </div>
</div>
@endsection
