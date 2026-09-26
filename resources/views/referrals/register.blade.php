<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>Стать партнёром — логистРу</title>
    @vite(['resources/css/app.css', 'resources/js/cookie-consent.js'])
</head>
<body>
<main class="referral-portal">
    <div class="referral-portal__shell" style="max-width: 760px">
        <header class="referral-portal__header">
            <div><p class="referral-portal__muted">логистРу</p><h1>Стать партнёром</h1></div>
            <a href="{{ url('/') }}">На главную</a>
        </header>

        <section class="referral-portal__card">
            <h2>Заявка на участие</h2>
            <p class="referral-portal__muted">Программа предназначена для действующих платящих клиентов — организаций и ИП. После заявки мы проверим аккаунт компании и откроем партнёрский кабинет.</p>

            @if($errors->any())
                <div class="referral-portal__notice" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('referrals.partners.store') }}">
                @csrf
                <input class="landing-form-honeypot" type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
                <label>Название организации или ИП<input name="company_name" required maxlength="255" autocomplete="organization" value="{{ old('company_name') }}"></label>
                <label>ИНН<input name="inn" required minlength="10" maxlength="12" inputmode="numeric" pattern="[0-9]{10}([0-9]{2})?" value="{{ old('inn') }}"></label>
                <label>Контактное лицо<input name="contact_name" required maxlength="255" autocomplete="name" value="{{ old('contact_name') }}"></label>
                <label>Email<input type="email" name="contact_email" required maxlength="255" autocomplete="email" value="{{ old('contact_email') }}"></label>
                <label>Телефон<input type="tel" name="contact_phone" required maxlength="32" autocomplete="tel" value="{{ old('contact_phone') }}"></label>
                <label><span><input type="checkbox" name="terms_accepted" value="1" required style="width:auto"> Подтверждаю достоверность данных и право действовать от имени компании@if($settings->offer_url), ознакомился с <a href="{{ $settings->offer_url }}" target="_blank" rel="noopener">условиями программы</a>@endif.</span></label>
                <label><span><input type="checkbox" name="privacy_accepted" value="1" required style="width:auto"> Соглашаюсь с <a href="{{ route('legal.privacy_policy') }}" target="_blank" rel="noopener">политикой обработки персональных данных</a>.</span></label>
                <button type="submit">Отправить заявку</button>
            </form>
        </section>
    </div>
</main>
<x-site.tracking />
</body>
</html>
