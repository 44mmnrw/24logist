@php
    $settings = app(\App\Services\SiteSettingsService::class)->get();
    $registrationVariant = (bool) ($settings->epd_popup_registration_enabled ?? false);
    $popupVariant = $registrationVariant ? 'registration' : 'presentation';
@endphp

@if ($settings->epd_popup_enabled)
<div
    class="epd-popup"
    data-epd-popup
    data-popup-variant="{{ $popupVariant }}"
    @unless ($registrationVariant)
        data-submit-url="{{ route('leads.epd-presentation.store') }}"
    @endunless
    data-show-delay="4"
    data-cooldown-days="3"
    role="dialog"
    aria-modal="true"
    aria-labelledby="epd-popup-title"
    aria-describedby="epd-popup-description"
    aria-hidden="true"
    hidden
>
    <div class="epd-popup__backdrop" aria-hidden="true"></div>

    <div class="epd-popup__card" role="document">
        <button class="epd-popup__close" type="button" data-epd-popup-close aria-label="Закрыть">×</button>

        <div class="epd-popup__visual">
            <img
                src="{{ asset($registrationVariant ? 'images/epd-test-access-14-days.png' : 'images/epd-announcement-27-08.png') }}"
                alt="{{ $registrationVariant ? 'Бесплатный тестовый доступ 14 дней и скидка 50% на пакет ЭПД' : '27.08 — открываем доступ к модулю ЭПД' }}"
                width="1254"
                height="1254"
                loading="lazy"
                decoding="async"
            >
        </div>

        <div class="epd-popup__content{{ $registrationVariant ? ' epd-popup__content--registration' : '' }}">
            @if ($registrationVariant)
                <div class="epd-popup__registration">
                    <span class="epd-popup__eyebrow">Специальное предложение</span>
                    <h2 id="epd-popup-title">14 дней бесплатно</h2>
                    <p id="epd-popup-description">Создайте личный кабинет и получите бесплатный тестовый доступ, а также скидку 50% на пакет ЭПД.</p>

                    <div class="epd-popup__registration-benefits" aria-label="Условия предложения">
                        <span><strong>14 дней</strong> бесплатного доступа</span>
                        <span><strong>50% скидка</strong> на пакет ЭПД</span>
                    </div>

                    <a
                        class="epd-popup__submit epd-popup__registration-cta"
                        href="https://logistsystem.ru/register"
                        data-epd-registration-cta
                    >Создать личный кабинет</a>
                </div>
            @else
            <div class="epd-popup__form-state" data-epd-form-state>
                <span class="epd-popup__eyebrow">Презентация модуля ЭПД</span>
                <h2 id="epd-popup-title">Оставить заявку</h2>
                <p id="epd-popup-description">Покажем процесс работы с электронными перевозочными документами и ответим на вопросы.</p>

                <form class="epd-popup__form" data-epd-form novalidate>
                    <input class="landing-form-honeypot" type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true">

                    <div class="epd-popup__row">
                        <label class="epd-popup__field">
                            <span>Компания</span>
                            <input type="text" name="company" autocomplete="organization" required placeholder="Название компании">
                        </label>
                        <label class="epd-popup__field">
                            <span>ИНН</span>
                            <input type="text" name="inn" inputmode="numeric" autocomplete="off" required maxlength="12" pattern="(?:\d{10}|\d{12})" placeholder="10 или 12 цифр">
                        </label>
                    </div>

                    <fieldset class="epd-popup__roles">
                        <legend>Кто вы</legend>
                        <div>
                            <label><input type="radio" name="role" value="expeditor" required><span>Экспедитор</span></label>
                            <label><input type="radio" name="role" value="carrier" required><span>Перевозчик</span></label>
                            <label><input type="radio" name="role" value="shipper" required><span>Грузоотправитель</span></label>
                        </div>
                    </fieldset>

                    <label class="epd-popup__field">
                        <span>В какой системе сейчас формируете документы?</span>
                        <input type="text" name="document_system" required placeholder="Например: 1С, Диадок, СБИС">
                    </label>

                    <div class="epd-popup__row">
                        <label class="epd-popup__field">
                            <span>Контактное лицо</span>
                            <input type="text" name="contact" autocomplete="name" required placeholder="Как к вам обращаться">
                        </label>
                        <label class="epd-popup__field">
                            <span>Телефон для связи</span>
                            <input type="tel" name="phone" autocomplete="tel" required placeholder="+7 (___) ___-__-__">
                        </label>
                    </div>

                    <p class="epd-popup__error" data-epd-error hidden></p>

                    <button class="epd-popup__submit" type="submit">Оставить заявку</button>
                    <small class="epd-popup__consent">Нажимая кнопку, вы соглашаетесь с <a href="{{ route('legal.privacy_policy') }}">политикой конфиденциальности</a>.</small>
                </form>
            </div>

            <div class="epd-popup__success" data-epd-success hidden role="status">
                <span class="epd-popup__success-icon">✓</span>
                <h2>Заявка отправлена</h2>
                <p data-epd-success-message>Мы свяжемся с вами для согласования времени презентации.</p>
                <button class="epd-popup__submit" type="button" data-epd-popup-close>Хорошо</button>
            </div>
            @endif
        </div>
    </div>
</div>
@endif
