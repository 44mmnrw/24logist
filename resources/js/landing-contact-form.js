import { postJson } from './landing-forms.js';

function initContactForm(form) {
    const submitUrl = form.dataset.submitUrl;

    if (!submitUrl) {
        return;
    }

    const errorNode = form.querySelector('[data-contact-error]');
    const successNode = form.querySelector('[data-contact-success]');
    const submitButton = form.querySelector('[type="submit"]');

    const showError = (message) => {
        if (!errorNode) {
            return;
        }

        errorNode.textContent = message;
        errorNode.hidden = !message;
    };

    const showSuccess = (message) => {
        if (!successNode) {
            return;
        }

        successNode.textContent = message;
        successNode.hidden = !message;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showError('');
        showSuccess('');

        const formData = new FormData(form);
        const name = String(formData.get('name') ?? '').trim();
        const phone = String(formData.get('phone') ?? '').trim();
        const email = String(formData.get('email') ?? '').trim();
        const message = String(formData.get('message') ?? '').trim();

        if (name === '' || phone === '') {
            showError('Укажите имя и телефон.');

            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const payload = await postJson(submitUrl, {
                name,
                phone,
                email: email || null,
                message: message || null,
                website: String(formData.get('website') ?? ''),
            });

            form.reset();
            const defaultSuccess = successNode?.dataset.defaultSuccess ?? 'Сообщение отправлено.';
            showSuccess(payload.message ?? defaultSuccess);
        } catch (error) {
            showError(error instanceof Error ? error.message : 'Не удалось отправить сообщение.');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
}

document.querySelectorAll('[data-landing-contact-form]').forEach(initContactForm);

export {};
