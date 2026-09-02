@extends('community.layout')
@section('title', 'Вход через MAX — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-auth-shell">
    <div class="community-auth-card" data-max-auth
         data-approve-url="{{ route('community.auth.max.approve') }}"
         data-session-url="{{ route('community.auth.max.session') }}"
         data-status-url="{{ route('community.auth.max.status', $challenge) }}">
        <span class="section-kicker">MAX</span><h1>Подтвердите вход</h1>
        <p>Отсканируйте QR-код телефоном или откройте MAX на этом устройстве, затем нажмите «Запустить» в чате с ботом. Ссылка действует 5 минут.</p>
        <img class="community-qr" src="{{ $qr }}" alt="QR-код для входа через MAX" width="260" height="260">
        <a class="community-provider community-provider--max" href="{{ $deepLink }}">Продолжить в MAX</a>
        <p data-max-status class="community-auth-status">Ожидаем подтверждение…</p>
    </div>
</div>
<script src="https://st.max.ru/js/max-web-app.js"></script>
@endsection
