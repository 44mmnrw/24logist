import { initCabinetRegistrationPartySuggestions } from './cabinet-registration-party-suggestions.js';
import { postJson } from './landing-forms.js';
import { createSmartCaptcha } from './smartcaptcha.js';
import { initPhoneMask, phoneDigits } from './phone-mask.js';

const modal = document.querySelector('[data-commercial-offer-modal]');

if (modal) {
    const form = modal.querySelector('[data-commercial-offer-form]');
    const formState = modal.querySelector('[data-commercial-offer-form-state]');
    const successState = modal.querySelector('[data-commercial-offer-success]');
    const successMessage = modal.querySelector('[data-commercial-offer-success-message]');
    const errorNode = modal.querySelector('[data-commercial-offer-error]');
    const submitButton = form?.querySelector('[type="submit"]');
    const submitWrap = form?.querySelector('[data-commercial-offer-submit-wrap]');
    const consentInput = form?.querySelector('[data-commercial-offer-consent]');
    const phoneInput = form?.querySelector('[name="phone"]');
    const captcha = createSmartCaptcha(form);
    let previouslyFocusedElement = null;
    let selectedUsers = 1;
    let selectedOptionIds = [];
    let selectedBillingPeriod = 'month';

    initCabinetRegistrationPartySuggestions(modal);

    const showError = (message = '') => {
        if (!errorNode) return;
        errorNode.textContent = message;
        errorNode.hidden = message === '';
    };

    const syncSubmitState = () => {
        if (!submitButton) return;

        const consentAccepted = consentInput?.checked === true;
        submitButton.disabled = form?.dataset.submitting === 'true' || !consentAccepted;
        if (submitWrap) {
            submitWrap.classList.toggle('is-consent-required', !consentAccepted);
            submitWrap.tabIndex = consentAccepted ? -1 : 0;
        }
    };

    consentInput?.addEventListener('change', syncSubmitState);
    syncSubmitState();

    initPhoneMask(phoneInput);

    const close = () => {
        if (modal.hidden) return;
        captcha.reset();
        modal.classList.remove('is-visible');
        modal.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('commercial-offer-open');

        window.setTimeout(() => {
            modal.hidden = true;
            previouslyFocusedElement?.focus?.();
        }, 200);
    };

    const open = (trigger) => {
        const calculator = trigger.closest('[data-wide-pricing]');
        const usersInput = calculator?.querySelector('[data-wide-pricing-users]');
        selectedUsers = Math.max(1, Number(usersInput?.value) || 1);
        selectedBillingPeriod = calculator?.dataset.widePricingPeriod === 'year' ? 'year' : 'month';
        selectedOptionIds = [...(calculator?.querySelectorAll('[data-wide-pricing-option]:checked') || [])]
            .map((option) => Number(option.dataset.optionId))
            .filter((id) => Number.isInteger(id) && id > 0);

        previouslyFocusedElement = trigger;
        showError();
        if (formState) formState.hidden = false;
        if (successState) successState.hidden = true;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('commercial-offer-open');
        document.dispatchEvent(new CustomEvent('commercial-offer:open'));

        window.requestAnimationFrame(() => {
            modal.classList.add('is-visible');
            form?.querySelector('[name="name"]')?.focus();
            captcha.load();
        });
    };

    document.querySelectorAll('[data-commercial-offer-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => open(trigger));
    });

    modal.querySelectorAll('[data-commercial-offer-close]').forEach((control) => {
        control.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (!modal.hidden && event.key === 'Escape') close();
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (form.dataset.submitting === 'true') return;
        showError();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const phone = String(formData.get('phone') || '').trim();
        if (phoneDigits(phone).length !== 10) {
            showError('Укажите корректный телефон.');
            return;
        }

        if (form) form.dataset.submitting = 'true';
        syncSubmitState();
        if (submitButton) {
            submitButton.textContent = 'Отправляем…';
        }

        try {
            const payload = await postJson(modal.dataset.submitUrl || '', {
                smart_token: await captcha.getToken(),
                name: String(formData.get('name') || '').trim(),
                company: String(formData.get('company') || '').trim(),
                inn: String(formData.get('inn') || '').replace(/\D/g, ''),
                email: String(formData.get('email') || '').trim(),
                phone,
                users: selectedUsers,
                billing_period: selectedBillingPeriod,
                option_ids: selectedOptionIds,
                privacy_accepted: consentInput?.checked === true,
                website: String(formData.get('website') || ''),
            });

            form.reset();
            syncSubmitState();
            if (formState) formState.hidden = true;
            if (successState) successState.hidden = false;
            if (payload.message && successMessage) successMessage.textContent = payload.message;
            successState?.querySelector('button')?.focus();
        } catch (error) {
            showError(error instanceof Error ? error.message : 'Не удалось отправить заявку. Попробуйте ещё раз.');
        } finally {
            captcha.reset();
            if (form) form.dataset.submitting = 'false';
            syncSubmitState();
            if (submitButton) {
                submitButton.textContent = submitButton.dataset.defaultText || 'Получить предложение';
            }
        }
    });
}

export {};
