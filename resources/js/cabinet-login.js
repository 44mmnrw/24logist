import { getCsrfToken, postJson } from './landing-forms.js';
import { initCabinetRegistrationPartySuggestions } from './cabinet-registration-party-suggestions.js';

const modal = document.querySelector('[data-cabinet-login-modal]');

if (modal) {
    const title = modal.querySelector('[data-cabinet-auth-title]');
    const description = modal.querySelector('[data-cabinet-auth-description]');
    const panels = [...modal.querySelectorAll('[data-cabinet-auth-panel]')];
    const tabs = [...modal.querySelectorAll('[data-cabinet-auth-tab]')];
    const tabsList = modal.querySelector('.cabinet-login-modal__tabs');
    const forms = [...modal.querySelectorAll('[data-cabinet-auth-form]')];
    let previouslyFocusedElement = null;
    let currentMode = modal.dataset.initialMode || 'login';

    const focusableSelector = 'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled])';

    const errorNodeFor = (form) => form?.querySelector('[data-cabinet-auth-error]');
    const successNodeFor = (form) => form?.querySelector('[data-cabinet-auth-success]');

    const showError = (form, message = '') => {
        const errorNode = errorNodeFor(form);
        if (!errorNode) return;
        errorNode.textContent = message;
        errorNode.hidden = message === '';
    };

    const showSuccess = (form, message = '') => {
        const successNode = successNodeFor(form);
        if (!successNode) return;
        successNode.textContent = message;
        successNode.hidden = message === '';
    };

    const resetPasswordFields = (form, clear = false) => {
        form?.querySelectorAll('[data-password-input]').forEach((input) => {
            input.type = 'password';
            if (clear) input.value = '';
        });
        form?.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            toggle.setAttribute('aria-pressed', 'false');
            toggle.setAttribute('aria-label', 'Показать пароль');
        });
    };

    modal.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const passwordInput = toggle.closest('.cabinet-login-modal__password')?.querySelector('[data-password-input]');
        if (!passwordInput) return;

        toggle.addEventListener('click', () => {
            const passwordVisible = passwordInput.type === 'text';
            passwordInput.type = passwordVisible ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', String(!passwordVisible));
            toggle.setAttribute('aria-label', passwordVisible ? 'Показать пароль' : 'Скрыть пароль');
            passwordInput.focus({ preventScroll: true });
        });
    });

    const phoneNationalDigits = (value) => {
        const digits = String(value ?? '').replace(/\D/g, '');
        if (digits === '') return '';

        return (digits.startsWith('7') || digits.startsWith('8') ? digits.slice(1) : digits).slice(0, 10);
    };

    const formatPhone = (value) => {
        const digits = phoneNationalDigits(value);
        if (digits === '') return '';

        let formatted = `+7 (${digits.slice(0, 3)}`;
        if (digits.length >= 3) formatted += ')';
        if (digits.length > 3) formatted += ` ${digits.slice(3, 6)}`;
        if (digits.length > 6) formatted += `-${digits.slice(6, 8)}`;
        if (digits.length > 8) formatted += `-${digits.slice(8, 10)}`;

        return formatted;
    };

    const normalizePhone = (value) => {
        const digits = phoneNationalDigits(value);

        return digits.length === 10 ? `+7${digits}` : '';
    };

    modal.querySelectorAll('[data-phone-mask]').forEach((input) => {
        const syncPhone = () => {
            const digits = phoneNationalDigits(input.value);
            input.value = formatPhone(input.value);
            input.setCustomValidity(digits.length === 10 ? '' : 'Введите номер полностью: +7 (999) 999-99-99.');
        };

        input.addEventListener('beforeinput', (event) => {
            if (event.inputType !== 'deleteContentBackward' || !event.cancelable) return;

            const selectionStart = input.selectionStart;
            const selectionEnd = input.selectionEnd;
            if (selectionStart === null || selectionEnd === null || selectionStart !== selectionEnd || selectionStart === 0) return;
            if (/\d/.test(input.value.charAt(selectionStart - 1))) return;

            let digitIndex = selectionStart - 1;
            while (digitIndex >= 0 && !/\d/.test(input.value.charAt(digitIndex))) digitIndex -= 1;
            if (digitIndex < 2) return;

            event.preventDefault();
            input.value = input.value.slice(0, digitIndex) + input.value.slice(digitIndex + 1);
            syncPhone();
            input.setSelectionRange(input.value.length, input.value.length);
        });

        input.addEventListener('input', syncPhone);
        input.addEventListener('blur', syncPhone);
        syncPhone();
    });

    const registrationForm = forms.find((form) => form.dataset.cabinetAuthForm === 'registration');
    const syncRegistrationSubmitState = () => {
        if (!registrationForm) return;

        const submitButton = registrationForm.querySelector('[type="submit"]');
        const termsAccepted = registrationForm.querySelector('[name="terms_accepted"]')?.checked === true;
        const privacyAccepted = registrationForm.querySelector('[name="privacy_policy_accepted"]')?.checked === true;
        const capabilitySelected = [...registrationForm.querySelectorAll('[name="capabilities[]"]')]
            .some((input) => input.checked);

        if (submitButton) {
            submitButton.disabled = registrationForm.dataset.submitting === 'true'
                || !termsAccepted
                || !privacyAccepted
                || !capabilitySelected;
        }
    };

    const setMode = (requestedMode) => {
        const mode = panels.some((panel) => panel.dataset.cabinetAuthPanel === requestedMode)
            ? requestedMode
            : modal.dataset.initialMode;
        const activePanel = panels.find((panel) => panel.dataset.cabinetAuthPanel === mode);

        currentMode = mode;
        panels.forEach((panel) => {
            const active = panel === activePanel;
            panel.hidden = !active;
            panel.setAttribute('aria-hidden', String(!active));
        });
        tabs.forEach((tab) => {
            const active = tab.dataset.cabinetAuthTab === mode;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        if (tabsList) {
            tabsList.hidden = mode === 'password-reset';
            tabsList.dataset.activeTab = mode;
        }

        if (title) title.textContent = activePanel?.dataset.title || '';
        if (description) description.textContent = activePanel?.dataset.description || '';

        forms.forEach((form) => {
            showError(form);
            showSuccess(form);
        });
    };

    const open = (mode) => {
        previouslyFocusedElement = document.activeElement;
        setMode(mode);
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('cabinet-login-open');
        document.dispatchEvent(new CustomEvent('cabinet-login:open'));

        window.requestAnimationFrame(() => {
            modal.classList.add('is-visible');
            modal.querySelector(`[data-cabinet-auth-panel="${currentMode}"] input:not([type="checkbox"])`)?.focus();
        });
    };

    const close = () => {
        if (modal.hidden) return;

        modal.classList.remove('is-visible');
        modal.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('cabinet-login-open');
        forms.forEach((form) => {
            showError(form);
            showSuccess(form);
            resetPasswordFields(form, true);
        });

        window.setTimeout(() => {
            modal.hidden = true;
            previouslyFocusedElement?.focus?.();
        }, 200);
    };

    document.querySelectorAll('[data-cabinet-login-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => open('login'));
    });

    document.querySelectorAll('[data-cabinet-registration-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => open('registration'));
    });

    modal.querySelectorAll('[data-cabinet-login-close]').forEach((control) => {
        control.addEventListener('click', close);
    });

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            setMode(tab.dataset.cabinetAuthTab);
            modal.querySelector(`[data-cabinet-auth-panel="${currentMode}"] input:not([type="checkbox"])`)?.focus();
        });
    });

    modal.querySelector('[data-cabinet-password-reset-open]')?.addEventListener('click', () => {
        const loginEmail = modal.querySelector('[data-cabinet-auth-form="login"] [name="email"]')?.value || '';
        setMode('password-reset');
        const resetEmail = modal.querySelector('[data-cabinet-auth-form="password-reset"] [name="email"]');
        if (resetEmail) resetEmail.value = loginEmail;
        resetEmail?.focus();
    });

    modal.querySelector('[data-cabinet-login-back]')?.addEventListener('click', () => {
        setMode('login');
        modal.querySelector('[data-cabinet-auth-form="login"] [name="email"]')?.focus();
    });

    document.addEventListener('keydown', (event) => {
        if (modal.hidden) return;

        if (event.key === 'Escape') {
            close();
            return;
        }

        if (event.key !== 'Tab') return;

        const focusable = [...modal.querySelectorAll(focusableSelector)]
            .filter((element) => !element.hidden && element.getClientRects().length > 0);

        if (focusable.length === 0) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    const submitHandoff = async (handoffUrl) => {
        const receivedUrl = new URL(String(handoffUrl || ''), window.location.origin);
        const expectedUrl = new URL(modal.dataset.handoffUrl, window.location.origin);

        if (receivedUrl.origin !== window.location.origin || receivedUrl.href !== expectedUrl.href) {
            throw new Error('Получен некорректный адрес перехода. Обновите страницу и попробуйте ещё раз.');
        }

        const token = await getCsrfToken();
        const handoffForm = document.createElement('form');
        const tokenInput = document.createElement('input');
        handoffForm.method = 'POST';
        handoffForm.action = receivedUrl.href;
        handoffForm.hidden = true;
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = token;
        handoffForm.append(tokenInput);
        document.body.append(handoffForm);
        handoffForm.requestSubmit();
    };

    forms.forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showError(form);
        showSuccess(form);

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const mode = form.dataset.cabinetAuthForm;
        const submitUrl = mode === 'registration'
            ? modal.dataset.registrationSubmitUrl
            : mode === 'password-reset'
                ? modal.dataset.passwordResetSubmitUrl
                : modal.dataset.loginSubmitUrl;

        if (!submitUrl) {
            showError(form, mode === 'registration'
                ? 'Регистрация пока не настроена.'
                : mode === 'password-reset'
                    ? 'Восстановление пароля пока не настроено.'
                    : 'Вход пока не настроен.');
            return;
        }

        const formData = new FormData(form);
        const submitButton = form.querySelector('[type="submit"]');
        const defaultText = submitButton?.dataset.defaultText || 'Войти';
        const requestBody = mode === 'registration'
            ? {
                name: String(formData.get('name') ?? '').trim(),
                account_name: String(formData.get('account_name') ?? '').trim(),
                inn: String(formData.get('inn') ?? '').replace(/\D/g, ''),
                phone: normalizePhone(formData.get('phone')),
                email: String(formData.get('email') ?? '').trim(),
                capabilities: formData.getAll('capabilities[]').map(String),
                password: String(formData.get('password') ?? ''),
                password_confirmation: String(formData.get('password_confirmation') ?? ''),
                terms_accepted: formData.has('terms_accepted'),
                privacy_policy_accepted: formData.has('privacy_policy_accepted'),
            }
            : mode === 'password-reset'
                ? {
                    email: String(formData.get('email') ?? '').trim(),
                }
                : {
                email: String(formData.get('email') ?? '').trim(),
                password: String(formData.get('password') ?? ''),
                remember: formData.has('remember'),
            };

        if (mode === 'registration' && requestBody.capabilities.length === 0) {
            showError(form, 'Выберите хотя бы один вариант работы компании.');
            return;
        }

        if (mode === 'registration' && requestBody.password !== requestBody.password_confirmation) {
            showError(form, 'Пароли не совпадают.');
            form.querySelector('[name="password_confirmation"]')?.focus();
            return;
        }

        if (submitButton) {
            if (mode === 'registration') form.dataset.submitting = 'true';
            submitButton.disabled = true;
            submitButton.textContent = mode === 'registration'
                ? 'Создаём кабинет…'
                : mode === 'password-reset'
                    ? 'Отправляем…'
                    : 'Входим…';
        }

        try {
            const payload = await postJson(submitUrl, requestBody);

            if (mode === 'password-reset') {
                showSuccess(form, payload.message || 'Если указанный email зарегистрирован, мы отправили ссылку для восстановления пароля.');
                return;
            }

            if (payload.method !== 'POST' || !payload.handoff_url) {
                throw new Error('Платформа не подготовила безопасный переход.');
            }

            await submitHandoff(payload.handoff_url);
        } catch (error) {
            showError(form, error instanceof Error ? error.message : 'Не удалось выполнить запрос. Попробуйте ещё раз.');
            if (mode !== 'password-reset') {
                const passwordInputs = [...form.querySelectorAll('[data-password-input]')];
                resetPasswordFields(form, true);
                passwordInputs[0]?.focus();
            }
        } finally {
            if (submitButton) {
                submitButton.textContent = defaultText;
                if (mode === 'registration') {
                    delete form.dataset.submitting;
                    syncRegistrationSubmitState();
                } else {
                    submitButton.disabled = false;
                }
            }
        }
    }));

    registrationForm?.addEventListener('change', syncRegistrationSubmitState);
    syncRegistrationSubmitState();
    setMode(currentMode);
    initCabinetRegistrationPartySuggestions(modal);
}

export {};
