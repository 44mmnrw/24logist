<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Заявка принята — логистРу</title>
    @vite(['resources/css/app.css', 'resources/js/cookie-consent.js'])
</head>
<body>
<main class="referral-portal">
    <div class="referral-portal__shell" style="max-width: 760px">
        <section class="referral-portal__card">
            <p class="referral-portal__muted">логистРу</p>
            <h1>Заявка принята</h1>
            <p>Мы проверим, что компания является действующим клиентом, и свяжемся с контактным лицом. После одобрения станут доступны партнёрская ссылка, статистика и реквизиты для выплат.</p>
            <a href="{{ url('/') }}">Вернуться на главную</a>
        </section>
    </div>
</main>
<x-site.tracking />
</body>
</html>
