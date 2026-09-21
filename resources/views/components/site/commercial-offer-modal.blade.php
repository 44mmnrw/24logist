<div
    class="cabinet-login-modal commercial-offer-modal"
    data-commercial-offer-modal
    data-submit-url="{{ route('leads.commercial-offer.store') }}"
    data-party-suggestions-url="{{ route('leads.party-suggestions') }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="commercial-offer-title"
    aria-describedby="commercial-offer-description"
    aria-hidden="true"
    hidden
>
    <button class="cabinet-login-modal__backdrop" type="button" data-commercial-offer-close aria-label="Закрыть окно"></button>

    <div class="cabinet-login-modal__card commercial-offer-modal__card" role="document">
        <button class="cabinet-login-modal__close" type="button" data-commercial-offer-close aria-label="Закрыть">
            <x-landing.icon name="x" class="cabinet-login-modal__close-icon" />
        </button>

        <span class="cabinet-login-modal__eyebrow">Коммерческое предложение</span>
        <h2 id="commercial-offer-title">Получить предложение</h2>
        <p id="commercial-offer-description">Оставьте контакты — подготовим предложение под выбранный состав тарифа.</p>

        <div data-commercial-offer-form-state>
            <form class="cabinet-login-modal__form commercial-offer-modal__form" data-commercial-offer-form data-cabinet-auth-form="registration" novalidate>
                <label class="cabinet-login-modal__field cabinet-login-modal__field--wide">
                    <span>Имя</span>
                    <input type="text" name="name" autocomplete="name" required maxlength="255">
                </label>

                <div class="cabinet-login-modal__field cabinet-login-modal__field--wide">
                    <label for="commercial-offer-inn">ИНН</label>
                    <div class="cabinet-login-modal__lookup">
                        <input id="commercial-offer-inn" type="text" name="inn" inputmode="numeric" autocomplete="off" required minlength="10" maxlength="12" pattern="[0-9]{10}([0-9]{2})?" data-party-inn>
                        <div class="cabinet-login-modal__suggestions" data-party-inn-suggestions role="listbox" aria-label="Подсказки по ИНН" hidden></div>
                    </div>
                </div>

                <div class="cabinet-login-modal__field cabinet-login-modal__field--wide">
                    <label for="commercial-offer-company">Название компании</label>
                    <div class="cabinet-login-modal__lookup">
                        <input id="commercial-offer-company" type="text" name="company" autocomplete="organization" required maxlength="255" data-party-account-name>
                        <div class="cabinet-login-modal__suggestions" data-party-account-name-suggestions role="listbox" aria-label="Подсказки организаций" hidden></div>
                    </div>
                    <p class="cabinet-login-modal__lookup-status" data-party-lookup-status role="status" aria-live="polite"></p>
                </div>

                <label class="cabinet-login-modal__field">
                    <span>Email</span>
                    <input type="email" name="email" autocomplete="email" inputmode="email" required maxlength="255">
                </label>

                <label class="cabinet-login-modal__field">
                    <span>Телефон</span>
                    <input type="tel" name="phone" autocomplete="tel" inputmode="tel" required maxlength="64" placeholder="+7 (___) ___-__-__">
                </label>

                <input class="landing-form-honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">

                <p class="cabinet-login-modal__error cabinet-login-modal__field--wide" data-commercial-offer-error role="alert" hidden></p>

                <label class="cabinet-login-modal__check commercial-offer-modal__consent cabinet-login-modal__field--wide">
                    <input type="checkbox" name="privacy_accepted" value="1" required data-commercial-offer-consent>
                    <span>Соглашаюсь с <a href="{{ route('legal.privacy_policy') }}" target="_blank" rel="noopener noreferrer">политикой конфиденциальности</a></span>
                </label>

                <div
                    class="commercial-offer-modal__submit-wrap cabinet-login-modal__field--wide"
                    data-commercial-offer-submit-wrap
                    tabindex="0"
                    aria-describedby="commercial-offer-consent-hint"
                >
                    <button class="cabinet-login-modal__submit" type="submit" data-default-text="Получить предложение" disabled>
                        Получить предложение
                    </button>
                    <span class="commercial-offer-modal__submit-hint" id="commercial-offer-consent-hint" role="tooltip">
                        Чтобы получить предложение, согласитесь с политикой конфиденциальности
                    </span>
                </div>
            </form>
        </div>

        <div class="commercial-offer-modal__success" data-commercial-offer-success hidden role="status">
            <span class="commercial-offer-modal__success-icon">✓</span>
            <h2>Заявка отправлена</h2>
            <p data-commercial-offer-success-message>Мы подготовим коммерческое предложение и свяжемся с вами.</p>
            <button class="cabinet-login-modal__submit" type="button" data-commercial-offer-close>Хорошо</button>
        </div>
    </div>
</div>
