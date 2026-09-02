@extends('community.layout')
@section('title', 'Создание профиля — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-auth-shell">
    <div class="community-auth-card">
        <span class="section-kicker">Последний шаг</span><h1>Создайте публичный профиль</h1>
        <form method="POST" action="{{ route('community.onboarding.store') }}" class="community-form">
            @csrf
            <label>Псевдоним<input name="username" value="{{ old('username') }}" minlength="3" maxlength="30" pattern="[A-Za-zА-Яа-яЁё0-9_-]+" required autocomplete="nickname"><small>3–30 символов: буквы, цифры, дефис или подчёркивание.</small></label>
            @foreach ($user->identities as $identity)
                @if ($identity->bot_access)
                    <label class="community-check"><input type="checkbox" name="{{ $identity->provider }}_notifications" value="1" @checked(old($identity->provider.'_notifications'))> Получать ответы через {{ $identity->provider === 'telegram' ? 'Telegram' : 'MAX' }}</label>
                @endif
            @endforeach
            <label class="community-check"><input type="checkbox" name="accept_terms" value="1" required @checked(old('accept_terms'))> Я принимаю <a href="{{ route('community.rules') }}" target="_blank">правила сообщества</a> и <a href="{{ route('community.privacy') }}" target="_blank">политику конфиденциальности</a></label>
            <button class="btn btn--primary" type="submit">Открыть сообщество</button>
        </form>
    </div>
</div>
@endsection
