@php
    $settings = app(\App\Services\SiteSettingsService::class);
    $siteSettings = $settings->get();
    $loginEnabled = $settings->cabinetLoginConfigured();
    $registrationEnabled = $settings->cabinetRegistrationConfigured();
    $platformUrl = $settings->cabinetLoginUrl();
@endphp

@if ($loginEnabled || $registrationEnabled)
<div
    class="cabinet-login-modal"
    data-cabinet-login-modal
    @if ($loginEnabled) data-login-submit-url="{{ route('cabinet.login') }}" @endif
    @if ($loginEnabled) data-password-reset-submit-url="{{ route('cabinet.password-reset') }}" @endif
    @if ($registrationEnabled) data-registration-submit-url="{{ route('cabinet.register') }}" @endif
    @if ($registrationEnabled) data-party-suggestions-url="{{ route('cabinet.register.party-suggestions') }}" @endif
    data-handoff-url="{{ route('cabinet.handoff') }}"
    data-initial-mode="{{ $loginEnabled ? 'login' : 'registration' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="cabinet-auth-title"
    aria-describedby="cabinet-auth-description"
    aria-hidden="true"
    hidden
>
    <button class="cabinet-login-modal__backdrop" type="button" data-cabinet-login-close aria-label="Закрыть окно"></button>

    <div class="cabinet-login-modal__card" role="document">
        <button class="cabinet-login-modal__close" type="button" data-cabinet-login-close aria-label="Закрыть">
            <x-landing.icon name="x" class="cabinet-login-modal__close-icon" />
        </button>

        <x-landing.logo class="cabinet-login-modal__brand-logo" />
        <h2 id="cabinet-auth-title" data-cabinet-auth-title></h2>
        <p id="cabinet-auth-description" data-cabinet-auth-description></p>

        @if ($loginEnabled && $registrationEnabled)
            <div class="cabinet-login-modal__tabs" role="tablist" aria-label="Личный кабинет">
                <button type="button" role="tab" data-cabinet-auth-tab="registration">Регистрация</button>
                <button type="button" role="tab" data-cabinet-auth-tab="login">Вход</button>
            </div>
        @endif

        @if ($loginEnabled)
            <section
                data-cabinet-auth-panel="login"
                data-title="{{ $siteSettings->cabinet_login_modal_title ?: 'Вход в личный кабинет' }}"
                data-description="{{ $siteSettings->cabinet_login_modal_description ?: 'Введите email и пароль, указанные при регистрации в ЛогистРу.' }}"
            >
                <form class="cabinet-login-modal__form" data-cabinet-auth-form="login" novalidate>
                    <label class="cabinet-login-modal__field cabinet-login-modal__field--wide">
                        <span>{{ $siteSettings->cabinet_login_identifier_label ?: 'Email' }}</span>
                        <input type="email" name="email" autocomplete="username" inputmode="email" required maxlength="255">
                    </label>

                    <div class="cabinet-login-modal__field cabinet-login-modal__field--wide">
                        <label for="cabinet-login-password">{{ $siteSettings->cabinet_login_password_label ?: 'Пароль' }}</label>
                        <div class="cabinet-login-modal__password">
                            <input id="cabinet-login-password" type="password" name="password" autocomplete="current-password" required maxlength="1024" data-password-input>
                            <button class="cabinet-login-modal__password-toggle" type="button" data-password-toggle aria-label="Показать пароль" aria-pressed="false">
                                <x-landing.icon name="eye" class="cabinet-login-modal__password-icon cabinet-login-modal__password-icon--show" />
                                <x-landing.icon name="eye-off" class="cabinet-login-modal__password-icon cabinet-login-modal__password-icon--hide" />
                            </button>
                        </div>
                    </div>

                    <label class="cabinet-login-modal__check cabinet-login-modal__field--wide">
                        <input type="checkbox" name="remember" value="1">
                        <span>Запомнить меня</span>
                    </label>

                    <p class="cabinet-login-modal__error cabinet-login-modal__field--wide" data-cabinet-auth-error role="alert" hidden></p>

                    <button class="cabinet-login-modal__submit cabinet-login-modal__field--wide" type="submit" data-default-text="{{ $siteSettings->cabinet_login_submit_text ?: 'Войти' }}">
                        {{ $siteSettings->cabinet_login_submit_text ?: 'Войти' }}
                    </button>

                    <button class="cabinet-login-modal__forgot cabinet-login-modal__field--wide" type="button" data-cabinet-password-reset-open>
                        {{ $siteSettings->cabinet_login_forgot_text ?: 'Забыли пароль?' }}
                    </button>
                </form>
            </section>

            <section
                data-cabinet-auth-panel="password-reset"
                data-title="Восстановление пароля"
                data-description="Введите email, указанный при регистрации в ЛогистРу. Мы отправим ссылку для создания нового пароля."
                hidden
            >
                <form class="cabinet-login-modal__form" data-cabinet-auth-form="password-reset" novalidate>
                    <label class="cabinet-login-modal__field cabinet-login-modal__field--wide">
                        <span>Email</span>
                        <input type="email" name="email" autocomplete="email" inputmode="email" required maxlength="255">
                    </label>

                    <p class="cabinet-login-modal__error cabinet-login-modal__field--wide" data-cabinet-auth-error role="alert" hidden></p>
                    <p class="cabinet-login-modal__success cabinet-login-modal__field--wide" data-cabinet-auth-success role="status" hidden></p>

                    <button class="cabinet-login-modal__submit cabinet-login-modal__field--wide" type="submit" data-default-text="Отправить ссылку">
                        Отправить ссылку
                    </button>

                    <button class="cabinet-login-modal__forgot cabinet-login-modal__field--wide" type="button" data-cabinet-login-back>
                        Вернуться ко входу
                    </button>
                </form>
            </section>
        @endif

        @if ($registrationEnabled)
            <section
                data-cabinet-auth-panel="registration"
                data-title="Создание личного кабинета"
                data-description="Заполните форму для регистрации в системе. После регистрации проверьте почту."
            >
                <form class="cabinet-login-modal__form cabinet-login-modal__form--registration" data-cabinet-auth-form="registration" novalidate>
                    <label class="cabinet-login-modal__field">
                        <span>Ваше имя</span>
                        <input type="text" name="name" autocomplete="name" required maxlength="255">
                    </label>

                    <div class="cabinet-login-modal__field">
                        <label for="cabinet-registration-account-name">Название компании</label>
                        <div class="cabinet-login-modal__lookup">
                            <input id="cabinet-registration-account-name" type="text" name="account_name" autocomplete="organization" required maxlength="255" data-party-account-name>
                            <div class="cabinet-login-modal__suggestions" data-party-account-name-suggestions role="listbox" aria-label="Подсказки организаций" hidden></div>
                        </div>
                    </div>

                    <div class="cabinet-login-modal__field">
                        <label for="cabinet-registration-inn">ИНН</label>
                        <div class="cabinet-login-modal__lookup">
                            <input id="cabinet-registration-inn" type="text" name="inn" inputmode="numeric" autocomplete="off" required minlength="10" maxlength="12" pattern="[0-9]{10}([0-9]{2})?" data-party-inn>
                            <div class="cabinet-login-modal__suggestions" data-party-inn-suggestions role="listbox" aria-label="Подсказки по ИНН" hidden></div>
                        </div>
                        <p class="cabinet-login-modal__lookup-status" data-party-lookup-status role="status" aria-live="polite"></p>
                    </div>

                    <label class="cabinet-login-modal__field">
                        <span>Телефон</span>
                        <input type="tel" name="phone" autocomplete="tel" inputmode="numeric" required minlength="18" maxlength="18" pattern="\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}" placeholder="+7 (___) ___-__-__" data-phone-mask>
                    </label>

                    <label class="cabinet-login-modal__field cabinet-login-modal__field--wide">
                        <span>Email</span>
                        <input type="email" name="email" autocomplete="email" inputmode="email" required maxlength="255">
                    </label>

                    <fieldset class="cabinet-login-modal__fieldset cabinet-login-modal__field--wide">
                        <legend>Чем занимается компания</legend>
                        <label class="cabinet-login-modal__check">
                            <input type="checkbox" name="capabilities[]" value="cargo_owner">
                            <span>Заказывает перевозки (грузовладелец)</span>
                        </label>
                        <label class="cabinet-login-modal__check">
                            <input type="checkbox" name="capabilities[]" value="forwarder" checked>
                            <span>Организует перевозки (экспедитор)</span>
                        </label>
                        <label class="cabinet-login-modal__check cabinet-login-modal__check--disabled" title="Возможность находится в разработке">
                            <input type="checkbox" value="carrier" disabled>
                            <span>Выполняет перевозки (перевозчик) <small>В разработке</small></span>
                        </label>
                    </fieldset>

                    <div class="cabinet-login-modal__field">
                        <label for="cabinet-registration-password">Пароль</label>
                        <div class="cabinet-login-modal__password">
                            <input id="cabinet-registration-password" type="password" name="password" autocomplete="new-password" required minlength="8" maxlength="1024" data-password-input>
                            <button class="cabinet-login-modal__password-toggle" type="button" data-password-toggle aria-label="Показать пароль" aria-pressed="false">
                                <x-landing.icon name="eye" class="cabinet-login-modal__password-icon cabinet-login-modal__password-icon--show" />
                                <x-landing.icon name="eye-off" class="cabinet-login-modal__password-icon cabinet-login-modal__password-icon--hide" />
                            </button>
                        </div>
                    </div>

                    <div class="cabinet-login-modal__field">
                        <label for="cabinet-registration-password-confirmation">Повторите пароль</label>
                        <div class="cabinet-login-modal__password">
                            <input id="cabinet-registration-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" required minlength="8" maxlength="1024" data-password-input>
                            <button class="cabinet-login-modal__password-toggle" type="button" data-password-toggle aria-label="Показать пароль" aria-pressed="false">
                                <x-landing.icon name="eye" class="cabinet-login-modal__password-icon cabinet-login-modal__password-icon--show" />
                                <x-landing.icon name="eye-off" class="cabinet-login-modal__password-icon cabinet-login-modal__password-icon--hide" />
                            </button>
                        </div>
                    </div>

                    <div class="cabinet-login-modal__consents cabinet-login-modal__field--wide">
                        <label class="cabinet-login-modal__check">
                            <input type="checkbox" name="terms_accepted" value="1" required>
                            <span>Принимаю <a href="{{ $platformUrl }}/legal/terms" target="_blank" rel="noopener noreferrer">пользовательское соглашение</a></span>
                        </label>
                        <label class="cabinet-login-modal__check">
                            <input type="checkbox" name="privacy_policy_accepted" value="1" required>
                            <span>Соглашаюсь с <a href="{{ $platformUrl }}/legal/privacy" target="_blank" rel="noopener noreferrer">политикой обработки персональных данных</a></span>
                        </label>
                    </div>

                    <p class="cabinet-login-modal__error cabinet-login-modal__field--wide" data-cabinet-auth-error role="alert" hidden></p>

                    <button class="cabinet-login-modal__submit cabinet-login-modal__field--wide" type="submit" data-default-text="Создать личный кабинет" disabled>
                        Создать личный кабинет
                    </button>
                </form>
            </section>
        @endif
    </div>
</div>
@endif
