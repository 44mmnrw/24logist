import { postJson } from './landing-forms.js';

const popup = document.querySelector('[data-epd-popup]');

if (popup) {
    const variant = popup.dataset.popupVariant || 'presentation';
    const dismissedStorageKey = variant === 'presentation'
        ? 'epd-presentation-popup-last-dismissed'
        : `epd-popup-${variant}-last-dismissed`;
    const completedStorageKey = variant === 'presentation'
        ? 'epd-presentation-popup-completed'
        : `epd-popup-${variant}-completed`;
    const delay = Math.max(0, Number(popup.dataset.showDelay) || 0) * 1000;
    const cooldownDays = Math.max(1, Number(popup.dataset.cooldownDays) || 3);
    const cooldownMs = cooldownDays * 24 * 60 * 60 * 1000;
    const form = popup.querySelector('[data-epd-form]');
    const formState = popup.querySelector('[data-epd-form-state]');
    const successState = popup.querySelector('[data-epd-success]');
    const successMessage = popup.querySelector('[data-epd-success-message]');
    const errorNode = popup.querySelector('[data-epd-error]');
    const submitButton = form?.querySelector('[type="submit"]');
    let previouslyFocusedElement = null;

    const readStorage = (key) => {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    };

    const writeStorage = (key, value) => {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Storage can be unavailable in strict privacy modes.
        }
    };

    const wasDismissedRecently = () => {
        const timestamp = Number(readStorage(dismissedStorageKey));

        return Number.isFinite(timestamp) && timestamp > 0 && Date.now() - timestamp < cooldownMs;
    };

    const showError = (message) => {
        if (!errorNode) {
            return;
        }

        errorNode.textContent = message;
        errorNode.hidden = !message;
    };

    const close = ({ remember = true } = {}) => {
        if (popup.hidden) {
            return;
        }

        popup.classList.remove('is-visible');
        popup.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('epd-popup-open');

        if (remember && !readStorage(completedStorageKey)) {
            writeStorage(dismissedStorageKey, String(Date.now()));
        }

        window.setTimeout(() => {
            popup.hidden = true;
            previouslyFocusedElement?.focus?.();
        }, 220);
    };

    const show = () => {
        if (readStorage(completedStorageKey) === '1' || wasDismissedRecently() || document.visibilityState !== 'visible') {
            return;
        }

        previouslyFocusedElement = document.activeElement;
        popup.hidden = false;
        popup.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('epd-popup-open');
        document.dispatchEvent(new CustomEvent('epd-popup:open'));

        window.requestAnimationFrame(() => {
            popup.classList.add('is-visible');
            popup.querySelector('.epd-popup__close')?.focus();
        });
    };

    popup.querySelectorAll('[data-epd-popup-close]').forEach((control) => {
        control.addEventListener('click', () => close());
    });

    popup.querySelector('[data-epd-registration-cta]')?.addEventListener('click', () => {
        writeStorage(completedStorageKey, '1');
    });

    document.addEventListener('keydown', (event) => {
        if (!popup.hidden && event.key === 'Escape') {
            close();
        }
    });

    form?.querySelector('[name="inn"]')?.addEventListener('input', (event) => {
        event.target.value = event.target.value.replace(/\D/g, '').slice(0, 12);
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        showError('');

        if (!form.checkValidity()) {
            form.reportValidity();

            return;
        }

        const formData = new FormData(form);
        const phone = String(formData.get('phone') ?? '').trim();

        if (phone.replace(/\D/g, '').length < 10) {
            showError('Укажите корректный телефон для связи.');

            return;
        }

        if (!popup.dataset.submitUrl) {
            showError('Форма не настроена. Пожалуйста, попробуйте позже.');

            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Отправляем…';
        }

        try {
            const payload = await postJson(popup.dataset.submitUrl, {
                company: String(formData.get('company') ?? '').trim(),
                inn: String(formData.get('inn') ?? '').trim(),
                role: String(formData.get('role') ?? ''),
                document_system: String(formData.get('document_system') ?? '').trim(),
                contact: String(formData.get('contact') ?? '').trim(),
                phone,
                website: String(formData.get('website') ?? ''),
            });

            form.reset();
            writeStorage(completedStorageKey, '1');
            if (formState) {
                formState.hidden = true;
            }

            if (successState) {
                successState.hidden = false;
            }

            if (payload.message && successMessage) {
                successMessage.textContent = payload.message;
            }
        } catch (error) {
            showError(error instanceof Error ? error.message : 'Не удалось отправить заявку. Попробуйте ещё раз.');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Оставить заявку';
            }
        }
    });

    if (readStorage(completedStorageKey) !== '1' && !wasDismissedRecently()) {
        window.setTimeout(show, delay);
    }
}

export {};
