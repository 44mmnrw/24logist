<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <x-analytics.google-analytics />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-csrf-meta />
    @php
        $og = \App\Support\OpenGraph::forPage($page);
    @endphp
    <title>{{ $og['title'] }}</title>
    <x-seo.open-graph :page="$page" />
    <x-seo.structured-data :page="$page" />
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-landing.sprite />

    @php
        $extra = is_array($page->extra ?? null) ? $page->extra : [];
        $managers = collect($extra['managers'] ?? [])->filter(fn ($row) => is_array($row))->values();
        $heroSubtitle = (string) ($extra['contacts_hero_subtitle'] ?? 'Выберите менеджера или заполните форму — ответим в течение 30 минут в рабочее время.');
        $managersTitle = (string) ($extra['contacts_managers_title'] ?? 'Наши менеджеры');
        $managersSubtitle = (string) ($extra['contacts_managers_subtitle'] ?? 'Каждый специализируется на своей зоне — обратитесь напрямую.');
        $callButtonText = (string) ($extra['contacts_call_button_text'] ?? 'Позвонить');
        $emptyManagersTitle = (string) ($extra['contacts_empty_managers_title'] ?? 'Карточки менеджеров не заполнены');
        $emptyManagersText = (string) ($extra['contacts_empty_managers_text'] ?? 'Добавьте их в админке: Страницы -> contacts -> Карточки менеджеров.');
        $formTitle = (string) ($extra['contacts_form_title'] ?? 'Написать нам');
        $formSubtitle = (string) ($extra['contacts_form_subtitle'] ?? 'Заполните форму — менеджер свяжется с вами.');
        $nameLabel = (string) ($extra['contacts_name_label'] ?? 'Ваше имя');
        $namePlaceholder = (string) ($extra['contacts_name_placeholder'] ?? 'Иван Петров');
        $phoneLabel = (string) ($extra['contacts_phone_label'] ?? 'Телефон');
        $phonePlaceholder = (string) ($extra['contacts_phone_placeholder'] ?? '+7 (___) ___-__-__');
        $emailLabel = (string) ($extra['contacts_email_label'] ?? 'Электронная почта');
        $emailPlaceholder = (string) ($extra['contacts_email_placeholder'] ?? 'ivan@company.ru');
        $messageLabel = (string) ($extra['contacts_message_label'] ?? 'Сообщение');
        $messagePlaceholder = (string) ($extra['contacts_message_placeholder'] ?? 'Опишите ваш вопрос или задачу...');
        $submitText = (string) ($extra['contacts_submit_text'] ?? 'Отправить сообщение');
        $privacyPrefix = (string) ($extra['contacts_privacy_prefix'] ?? 'Нажимая кнопку, вы соглашаетесь с');
        $privacyLinkText = (string) ($extra['contacts_privacy_link_text'] ?? 'политикой конфиденциальности');
        $successMessage = (string) ($extra['contacts_success_message'] ?? 'Сообщение отправлено. Мы свяжемся с вами в ближайшее время.');
    @endphp

    <div class="landing-page contacts-page-v2">
        <x-landing.header />

        <section class="contacts-v2-hero">
            <div class="contacts-v2-shell">
                <h1>{{ $page->title ?: 'Свяжитесь с нашей командой' }}</h1>
                <p>{{ $heroSubtitle }}</p>
            </div>
        </section>

        <main class="contacts-v2-main">
            <div class="contacts-v2-shell contacts-v2-grid">
                <section class="contacts-v2-managers">
                    <header>
                        <h2>{{ $managersTitle }}</h2>
                        <p>{{ $managersSubtitle }}</p>
                    </header>

                    @forelse ($managers as $manager)
                        @php
                            $name = trim((string) ($manager['name'] ?? ''));
                            if ($name === '') {
                                continue;
                            }
                            $position = trim((string) ($manager['position'] ?? ''));
                            $phone = trim((string) ($manager['phone'] ?? ''));
                            $email = trim((string) ($manager['email'] ?? ''));
                            $color = in_array(($manager['color'] ?? 'blue'), ['blue', 'dark', 'green'], true) ? $manager['color'] : 'blue';
                            $tel = preg_replace('/[^0-9+]/', '', $phone ?? '');
                        @endphp
                        <article class="contacts-v2-manager">
                            <div class="contacts-v2-manager__avatar contacts-v2-manager__avatar--{{ $color }}"><x-landing.icon name="icon:manager-avatar" /></div>
                            <div class="contacts-v2-manager__body">
                                <h3>{{ $name }}</h3>
                                @if ($position !== '')
                                    <p class="contacts-v2-manager__role">{{ $position }}</p>
                                @endif
                                @if ($phone !== '')
                                    <a href="{{ filled($tel) ? 'tel:'.$tel : '#' }}"><x-landing.icon name="icon:phone" />{{ $phone }}</a>
                                @endif
                                @if ($email !== '')
                                    <a href="mailto:{{ $email }}"><x-landing.icon name="icon:mail" />{{ $email }}</a>
                                @endif
                            </div>
                            @if ($phone !== '')
                                <a class="contacts-v2-call" href="{{ filled($tel) ? 'tel:'.$tel : '#' }}">{{ $callButtonText }}</a>
                            @endif
                        </article>
                    @empty
                        <article class="contacts-v2-manager">
                            <div class="contacts-v2-manager__body">
                                <h3>{{ $emptyManagersTitle }}</h3>
                                <p class="contacts-v2-manager__role">{{ $emptyManagersText }}</p>
                            </div>
                        </article>
                    @endforelse

                </section>

                <section class="contacts-v2-form">
                    <h2>{{ $formTitle }}</h2>
                    <p>{{ $formSubtitle }}</p>
                    <form
                        data-landing-contact-form
                        data-submit-url="{{ route('leads.contact.store') }}"
                        novalidate
                    >
                        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="landing-form-honeypot" aria-hidden="true">
                        <label>{{ $nameLabel }}<input type="text" name="name" autocomplete="name" required placeholder="{{ $namePlaceholder }}"></label>
                        <label>{{ $phoneLabel }}<input type="tel" name="phone" autocomplete="tel" required placeholder="{{ $phonePlaceholder }}"></label>
                        <label>{{ $emailLabel }}<input type="email" name="email" autocomplete="email" placeholder="{{ $emailPlaceholder }}"></label>
                        <label>{{ $messageLabel }}<textarea name="message" rows="4" placeholder="{{ $messagePlaceholder }}"></textarea></label>
                        <p class="contacts-v2-form__error" data-contact-error hidden></p>
                        <p class="contacts-v2-form__success" data-contact-success hidden data-default-success="{{ $successMessage }}"></p>
                        <button type="submit" class="btn btn--primary btn--full"><x-landing.icon name="icon:arrow-right" />{{ $submitText }}</button>
                    </form>
                    <small>{{ $privacyPrefix }} <a href="/pages/privacy-policy">{{ $privacyLinkText }}</a></small>
                </section>
            </div>
        </main>

        <x-landing.footer />
    </div>
    <x-site.telegram-popup />
    <x-site.cookie-consent />
    <x-analytics.yandex-metrika />
</body>
</html>
