<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5faff">
    <meta name="robots" content="noindex, nofollow">
    <title>Контакт для розыгрыша — логистРу</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="etrn-roulette-page">
    <main class="etrn-roulette-contact-page">
        <a class="etrn-roulette-rules-page__back" href="{{ route('etrn-roulette') }}"><x-community.icon name="arrow-left" size="16" />Вернуться к игре</a>
        <section class="etrn-roulette-contact-card">
            <h1>Контакт для розыгрыша</h1>
            <p>Подтвердите email, по которому мы сможем связаться с вами, если вы станете получателем подписки. Новые попытки начнут учитываться после подтверждения.</p>

            @if (session('status'))
                <p class="etrn-roulette-contact-card__status" role="status">{{ session('status') }}</p>
            @endif

            @if ($pendingEmail)
                <form method="POST" action="{{ route('etrn-roulette.prize.verify') }}" class="etrn-roulette-contact-card__form">
                    @csrf
                    <label for="etrn-contact-code">Код из письма на {{ $pendingEmail }}</label>
                    <input id="etrn-contact-code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus value="{{ old('code') }}">
                    @error('code') <span class="etrn-roulette-contact-card__error" role="alert">{{ $message }}</span> @enderror
                    <button type="submit">Подтвердить email</button>
                    <small>Код действует 10 минут. Его можно запросить повторно через минуту.</small>
                </form>
            @endif

            <form method="POST" action="{{ route('etrn-roulette.prize.contact') }}" class="etrn-roulette-contact-card__form">
                @csrf
                <label for="etrn-contact-email">{{ $pendingEmail ? 'Другой email или новый код' : 'Ваш email' }}</label>
                <input id="etrn-contact-email" name="email" type="email" maxlength="255" autocomplete="email" required value="{{ old('email', $pendingEmail) }}" placeholder="name@example.com">
                @error('email') <span class="etrn-roulette-contact-card__error" role="alert">{{ $message }}</span> @enderror
                <label class="etrn-roulette-contact-card__consent">
                    <input name="contact_consent" type="checkbox" value="1" required>
                    <span>Согласен на обработку указанного email для подтверждения участия и связи по поводу розыгрыша подписки. <a href="{{ route('community.privacy') }}" target="_blank" rel="noopener">О конфиденциальности</a>.</span>
                </label>
                @error('contact_consent') <span class="etrn-roulette-contact-card__error" role="alert">{{ $message }}</span> @enderror
                <button type="submit">Отправить код</button>
            </form>
            <a class="etrn-roulette-contact-card__rules" href="{{ route('etrn-roulette.rules') }}">Правила игры</a>
        </section>
    </main>
</body>
</html>
